<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/i18n.php';

$lang = current_lang();

$token = $_GET['t'] ?? '';
$tenant = null;
$table = null;

if ($token) {
    $st = db()->prepare('SELECT t.*, tn.name AS tenant_name FROM `tables` t LEFT JOIN tenants tn ON tn.id=t.tenant_id WHERE t.qr_token=?');
    $st->execute([$token]);
    $table = $st->fetch();
    if ($table) {
        $tenantStmt = db()->prepare('SELECT * FROM tenants WHERE id=?');
        $tenantStmt->execute([$table['tenant_id']]);
        $tenant = $tenantStmt->fetch();
    }
}
if (!$tenant) {
    $tenantStmt = db()->prepare('SELECT * FROM tenants WHERE active=1 ORDER BY id LIMIT 1');
    $tenantStmt->execute();
    $tenant = $tenantStmt->fetch();
}
if (!$tenant) { http_response_code(404); echo '<h1>Menu non disponibile</h1>'; exit; }

$cats = db()->prepare('SELECT * FROM categories WHERE tenant_id=? AND active=1 ORDER BY sort');
$cats->execute([$tenant['id']]);
$cats = $cats->fetchAll();

$prods = db()->prepare('SELECT * FROM products WHERE tenant_id=? AND available=1 ORDER BY sort, name');
$prods->execute([$tenant['id']]);
$prods = $prods->fetchAll();

$canOrder = (bool)$table;

function lang_url(string $newLang): string {
    $params = $_GET;
    $params['lang'] = $newLang;
    return '?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= $canOrder ? t('order_table') . ' ' . e($table['code']) : t('menu') ?> — <?= e($tenant['name']) ?></title>
<link rel="icon" type="image/jpeg" href="/assets/img/logo.jpeg">
<script>document.documentElement.setAttribute('data-theme','light');</script>
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config={darkMode:'class'}</script>
<script src="https://unpkg.com/alpinejs@3.13.5/dist/cdn.min.js" defer></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  html,body{overflow-x:hidden;max-width:100vw;}
  body{font-family:'Inter',sans-serif;background:#f5f7fb;color:#0f172a;}
  .menu-card{background:#fff;border:1px solid rgba(15,23,42,0.06);box-shadow:0 4px 16px -4px rgba(15,23,42,0.04);}
  .btn-primary{background:linear-gradient(135deg,#7c3aed,#8b5cf6);color:white;}
  .btn-primary:hover{box-shadow:0 8px 24px -4px rgba(139,92,246,0.4);}
  .qty-btn{width:32px;height:32px;border-radius:8px;background:#f1f5f9;color:#475569;font-weight:700;}
  .qty-btn:active{transform:scale(0.95);}
  .add-btn{background:linear-gradient(135deg,#7c3aed,#8b5cf6);color:#fff;width:36px;height:36px;border-radius:10px;font-size:20px;font-weight:700;box-shadow:0 4px 12px -2px rgba(139,92,246,0.4);}
  .add-btn:active{transform:scale(0.95);}
  .lang-pill{padding:6px 8px;border-radius:999px;font-size:13px;line-height:1;}
  .lang-pill.active{background:#7c3aed;color:white;}
  .lang-menu{position:absolute;top:100%;right:0;margin-top:6px;background:white;border:1px solid #e5e7eb;border-radius:12px;padding:4px;box-shadow:0 8px 24px -4px rgba(15,23,42,0.12);min-width:160px;z-index:50;}
  .lang-menu a{display:block;padding:8px 12px;border-radius:8px;font-size:14px;color:#0f172a;text-decoration:none;}
  .lang-menu a:hover{background:#f1f5f9;}
  .lang-menu a.active{background:linear-gradient(135deg,#7c3aed,#8b5cf6);color:white;}
</style>
</head>
<body>

<?php
function tr_prod(array $p, string $field, string $lang): string {
    if ($lang === 'it' || empty($p['translations'])) return $p[$field] ?? '';
    $tr = json_decode($p['translations'], true);
    if (!is_array($tr)) return $p[$field] ?? '';
    return $tr[$lang][$field] ?? ($p[$field] ?? '');
}
function tr_cat(array $c, string $lang): string {
    if ($lang === 'it' || empty($c['translations'])) return $c['name'];
    $tr = json_decode($c['translations'], true);
    return $tr[$lang]['name'] ?? $c['name'];
}
?>

<?php if ($canOrder): /* ============= MODALITÀ ORDINE TAVOLO ============= */ ?>
<div x-data="orderApp(<?= (int)$table['id'] ?>, '<?= e($table['code']) ?>', '<?= e($token) ?>')" x-init="loadOpenOrder()">
    <header class="sticky top-0 z-30 bg-white/95 backdrop-blur border-b border-slate-200 px-4 py-3 flex items-center gap-3">
        <img src="/assets/img/logo.jpeg" class="w-10 h-10 rounded-lg object-cover flex-shrink-0" alt="Logo">
        <div class="flex-1 min-w-0">
            <div class="font-bold text-base truncate"><?= e($tenant['name']) ?></div>
            <div class="text-xs text-violet-600 font-semibold">🪑 <?= e(t('order_table')) ?> <?= e($table['code']) ?></div>
        </div>
        <div class="relative" x-data="{open:false}" @click.outside="open=false">
            <button @click="open=!open" class="lang-pill bg-slate-100"><?= explode(' ', LANG_LABELS[$lang])[0] ?></button>
            <div x-show="open" x-cloak class="lang-menu">
                <?php foreach (LANG_LABELS as $code => $label): ?>
                    <a href="<?= e(lang_url($code)) ?>" class="<?= $code===$lang?'active':'' ?>"><?= e($label) ?></a>
                <?php endforeach; ?>
            </div>
        </div>
        <button @click="showCart=true" class="relative btn-primary px-3 py-2 rounded-xl text-sm font-bold flex items-center gap-1.5">
            🛒 <span x-text="cartCount()"></span>
        </button>
    </header>

    <div class="sticky top-[60px] bg-slate-50/95 backdrop-blur z-20 px-3 py-2 flex gap-2 overflow-x-auto" style="scrollbar-width:thin">
        <button @click="currentCat=null" :class="!currentCat?'btn-primary':'bg-white border border-slate-200'" class="px-3 py-1.5 rounded-lg text-sm font-semibold whitespace-nowrap"><?= e(t('all_categories')) ?></button>
        <?php foreach ($cats as $c): ?>
        <button @click="currentCat=<?= (int)$c['id'] ?>" :class="currentCat===<?= (int)$c['id'] ?>?'btn-primary':'bg-white border border-slate-200'" class="px-3 py-1.5 rounded-lg text-sm font-semibold whitespace-nowrap">
            <?= e($c['icon']) ?> <?= e(tr_cat($c, $lang)) ?>
        </button>
        <?php endforeach; ?>
    </div>

    <div x-show="sentItems.length" x-cloak class="mx-3 mt-3 p-3 rounded-xl bg-emerald-50 border border-emerald-200">
        <div class="text-xs font-bold text-emerald-700 uppercase tracking-wider mb-2">✓ <?= e(t('sent_items')) ?></div>
        <template x-for="i in sentItems" :key="i.id">
            <div class="flex justify-between text-sm py-0.5">
                <span><span class="font-bold text-emerald-700" x-text="parseFloat(i.qty).toFixed(0)+'×'"></span> <span x-text="i.name"></span></span>
                <span class="text-emerald-700 font-semibold" x-text="'€'+(i.qty*i.price).toFixed(2)"></span>
            </div>
        </template>
    </div>

    <div class="p-3 pb-32">
        <?php foreach ($cats as $cat):
            $items = array_filter($prods, fn($p)=>$p['category_id']==$cat['id']);
            if(!$items) continue; ?>
        <section x-show="!currentCat || currentCat===<?= (int)$cat['id'] ?>" class="mb-6">
            <h2 class="text-xl font-bold mb-2 px-1"><?= e($cat['icon']) ?> <?= e(tr_cat($cat, $lang)) ?></h2>
            <div class="space-y-2">
                <?php foreach ($items as $p):
                    $name = tr_prod($p, 'name', $lang);
                    $desc = tr_prod($p, 'description', $lang);
                    $allerg = tr_prod($p, 'allergens', $lang);
                ?>
                <div class="menu-card rounded-xl p-3 flex items-center gap-3">
                    <?php if (!empty($p['image'])): ?>
                    <img src="<?= e($p['image']) ?>" alt="" class="w-20 h-20 rounded-lg object-cover flex-shrink-0" loading="lazy">
                    <?php endif; ?>
                    <div class="flex-1 min-w-0">
                        <div class="font-bold text-sm leading-tight"><?= e($name) ?></div>
                        <?php if ($desc): ?><div class="text-xs text-slate-500 mt-0.5 line-clamp-2"><?= e($desc) ?></div><?php endif; ?>
                        <?php if ($allerg): ?><div class="text-[11px] text-amber-600 mt-0.5">⚠ <?= e($allerg) ?></div><?php endif; ?>
                        <div class="flex items-center justify-between mt-1.5 gap-2">
                            <span class="font-bold text-violet-600">€ <?= number_format($p['price'],2,',','.') ?></span>
                            <template x-if="(cart[<?= (int)$p['id'] ?>]||0) === 0">
                                <button @click="addItem(<?= (int)$p['id'] ?>, '<?= e(addslashes($name)) ?>', <?= (float)$p['price'] ?>)" class="add-btn">+</button>
                            </template>
                            <template x-if="(cart[<?= (int)$p['id'] ?>]||0) > 0">
                                <div class="flex items-center gap-1.5">
                                    <button @click="decItem(<?= (int)$p['id'] ?>)" class="qty-btn">−</button>
                                    <span class="font-bold w-6 text-center" x-text="cart[<?= (int)$p['id'] ?>]"></span>
                                    <button @click="incItem(<?= (int)$p['id'] ?>)" class="qty-btn">+</button>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endforeach; ?>
    </div>

    <div x-show="cartCount()>0" x-cloak class="fixed bottom-0 inset-x-0 bg-white border-t border-slate-200 shadow-2xl p-3 z-40">
        <div class="flex gap-2 max-w-md mx-auto">
            <button @click="showCart=true" class="flex-1 py-3 rounded-xl bg-slate-100 font-semibold text-sm">
                <span x-text="cartCount()+' <?= e(t('products')) ?>'"></span> · <span x-text="'€ '+cartTotal().toFixed(2)"></span>
            </button>
            <button @click="sendOrder" :disabled="sending" class="px-5 py-3 rounded-xl btn-primary font-bold disabled:opacity-60" x-text="sending?'⏳':'<?= e(t('send_order')) ?>'"></button>
        </div>
    </div>

    <div x-show="showCart" x-cloak @click.self="showCart=false" class="fixed inset-0 z-50 bg-black/50 flex items-end">
        <div class="bg-white w-full max-w-md mx-auto rounded-t-3xl p-5 max-h-[85vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold"><?= e(t('your_order')) ?></h3>
                <button @click="showCart=false" class="text-slate-400 text-2xl">✕</button>
            </div>
            <div class="space-y-2 mb-4">
                <template x-for="(p,id) in cart" :key="id">
                    <div class="flex items-center gap-2 p-2.5 rounded-xl bg-slate-50" x-show="p>0">
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-sm" x-text="cartNames[id]"></div>
                            <input type="text" x-model="cartNotes[id]" placeholder="<?= e(t('note_placeholder')) ?>" class="text-xs bg-transparent border-b border-slate-200 w-full mt-1 outline-none">
                        </div>
                        <div class="flex items-center gap-1.5">
                            <button @click="decItem(id)" class="qty-btn">−</button>
                            <span class="font-bold w-6 text-center" x-text="p"></span>
                            <button @click="incItem(id)" class="qty-btn">+</button>
                        </div>
                        <div class="font-bold text-sm w-16 text-right" x-text="'€'+(p*cartPrices[id]).toFixed(2)"></div>
                    </div>
                </template>
            </div>
            <div class="flex justify-between text-lg font-bold pt-3 border-t border-slate-200 mb-4">
                <span><?= e(t('total')) ?></span>
                <span class="text-violet-600" x-text="'€ '+cartTotal().toFixed(2)"></span>
            </div>
            <button @click="sendOrder" :disabled="sending || cartCount()===0" class="w-full btn-primary py-4 rounded-xl font-bold text-base disabled:opacity-50" x-text="sending?'⏳ <?= e(t('sending')) ?>':'📤 <?= e(t('send_order')) ?>'"></button>
            <p class="text-xs text-slate-400 text-center mt-3"><?= e(t('pay_at_end')) ?></p>
        </div>
    </div>

    <div x-show="confirmed" x-cloak class="fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl p-8 max-w-sm w-full text-center">
            <div class="text-6xl mb-3">✅</div>
            <h2 class="text-2xl font-bold mb-2"><?= e(t('order_sent')) ?></h2>
            <p class="text-sm text-slate-500 mb-1"><?= e(t('sent_to_kitchen')) ?></p>
            <p class="text-sm text-slate-500 mb-6"><?= e(t('waiter_will_serve')) ?> <strong class="text-violet-600"><?= e(t('order_table')) ?> <?= e($table['code']) ?></strong>.</p>
            <button @click="confirmed=false;loadOpenOrder()" class="btn-primary px-6 py-3 rounded-xl font-bold w-full"><?= e(t('add_more')) ?></button>
        </div>
    </div>
</div>

<script>
function orderApp(tableId, tableCode, token) {
    return {
        tableId, tableCode, token,
        currentCat: null,
        cart: {}, cartNames: {}, cartPrices: {}, cartNotes: {},
        sentItems: [],
        showCart: false, sending: false, confirmed: false,
        cartCount(){ return Object.values(this.cart).reduce((s,v)=>s+(parseInt(v)||0),0); },
        cartTotal(){ let t=0; for(let id in this.cart) t += (this.cart[id]||0) * (this.cartPrices[id]||0); return t; },
        addItem(id, name, price){
            this.cart[id] = (this.cart[id] || 0) + 1;
            this.cartNames[id] = name; this.cartPrices[id] = price;
            if (navigator.vibrate) navigator.vibrate(15);
        },
        incItem(id){ this.cart[id] = (this.cart[id]||0) + 1; },
        decItem(id){ this.cart[id] = Math.max(0, (this.cart[id]||0) - 1); if(this.cart[id]===0) delete this.cart[id]; },
        async loadOpenOrder(){
            try {
                const r = await fetch('/api/public_order.php?action=get&t='+encodeURIComponent(this.token));
                const d = await r.json();
                this.sentItems = d.items || [];
            } catch(e){}
        },
        async sendOrder(){
            if (this.cartCount()===0 || this.sending) return;
            this.sending = true;
            const items = [];
            for (let id in this.cart) {
                if (this.cart[id]>0) items.push({product_id: parseInt(id), qty: this.cart[id], notes: this.cartNotes[id]||''});
            }
            try {
                const r = await fetch('/api/public_order.php?action=submit', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({token: this.token, items})});
                const d = await r.json();
                if (d.error) alert('Errore: '+d.error);
                else { this.cart={}; this.cartNotes={}; this.showCart=false; this.confirmed=true;
                    if (navigator.vibrate) navigator.vibrate([50,30,50]); }
            } catch(e){ alert('Errore di rete'); }
            this.sending = false;
        }
    }
}
</script>

<?php else: /* ============= MENU READONLY ============= */ ?>

<div class="min-h-screen pb-20">
    <header class="sticky top-0 z-30 bg-white/95 backdrop-blur border-b border-slate-200 p-4">
        <div class="absolute top-4 right-4" x-data="{open:false}" @click.outside="open=false">
            <button @click="open=!open" class="lang-pill bg-slate-100"><?= explode(' ', LANG_LABELS[$lang])[0] ?></button>
            <div x-show="open" x-cloak class="lang-menu">
                <?php foreach (LANG_LABELS as $code => $label): ?>
                    <a href="<?= e(lang_url($code)) ?>" class="<?= $code===$lang?'active':'' ?>"><?= e($label) ?></a>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="text-center">
            <img src="/assets/img/logo.jpeg" class="w-14 h-14 mx-auto rounded-2xl object-cover">
            <h1 class="font-bold text-xl mt-2"><?= e($tenant['name']) ?></h1>
            <p class="text-xs text-slate-500"><?= e(t('menu')) ?></p>
        </div>
    </header>

    <div class="p-4">
        <?php foreach ($cats as $cat): $items = array_filter($prods, fn($p)=>$p['category_id']==$cat['id']); if(!$items)continue; ?>
        <section class="mb-8">
            <h2 class="text-2xl font-bold mb-3"><?= e($cat['icon']) ?> <?= e(tr_cat($cat, $lang)) ?></h2>
            <div class="space-y-2">
                <?php foreach ($items as $p):
                    $name = tr_prod($p, 'name', $lang);
                    $desc = tr_prod($p, 'description', $lang);
                    $allerg = tr_prod($p, 'allergens', $lang);
                ?>
                <div class="rounded-xl bg-white border border-slate-200 p-3 flex items-center gap-3">
                    <?php if (!empty($p['image'])): ?>
                    <img src="<?= e($p['image']) ?>" alt="<?= e($name) ?>" class="w-20 h-20 sm:w-24 sm:h-24 rounded-lg object-cover flex-shrink-0" loading="lazy">
                    <?php endif; ?>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-2">
                            <h3 class="font-semibold"><?= e($name) ?></h3>
                            <div class="font-bold text-violet-600 whitespace-nowrap">€ <?= number_format($p['price'],2,',','.') ?></div>
                        </div>
                        <?php if ($desc): ?><p class="text-sm text-slate-500 mt-0.5"><?= e($desc) ?></p><?php endif; ?>
                        <?php if ($allerg): ?><p class="text-xs text-amber-600 mt-1">⚠ <?= e($allerg) ?></p><?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endforeach; ?>
    </div>
</div>

<?php endif; ?>
</body></html>
