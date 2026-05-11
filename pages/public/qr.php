<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/layout.php';

// Token opzionale (backward compat); se assente, mostra menu del primo tenant attivo
$token = $_GET['t'] ?? '';
$tenant = null;
$table = null;

if ($token) {
    $st = db()->prepare('SELECT t.*, tn.name AS tenant_name, tn.address, tn.phone FROM tables t LEFT JOIN tenants tn ON tn.id=t.tenant_id WHERE t.qr_token=?');
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
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Menu — <?= e($tenant['name']) ?></title>
<link rel="icon" type="image/jpeg" href="/assets/img/logo.jpeg">
<script>document.documentElement.setAttribute('data-theme','light');</script>
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config={darkMode:'class'}</script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  html,body{overflow-x:hidden;max-width:100vw;}
  :root[data-theme="dark"]{--bg:#0a0a13;--text:#e2e8f0;--muted:#94a3b8;--surface:rgba(255,255,255,0.05);--border:rgba(255,255,255,0.08);}
  :root[data-theme="light"]{--bg:#f5f7fb;--text:#0f172a;--muted:#475569;--surface:#ffffff;--border:rgba(15,23,42,0.08);}
  body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);}
  :root[data-theme="light"] .bg-white\/5{background-color:var(--surface)!important;border:1px solid var(--border);}
  :root[data-theme="light"] .bg-black\/60{background-color:rgba(255,255,255,0.85)!important;}
  :root[data-theme="light"] .border-white\/10{border-color:var(--border)!important;}
  :root[data-theme="light"] .border-white\/5{border-color:var(--border)!important;}
  :root[data-theme="light"] .text-slate-400{color:var(--muted)!important;}
  :root[data-theme="light"] .text-slate-500,:root[data-theme="light"] .text-slate-600{color:#64748b!important;}
  .theme-toggle{display:inline-flex;gap:2px;padding:3px;border-radius:999px;background:var(--surface);border:1px solid var(--border);}
  .theme-toggle button{width:28px;height:28px;border-radius:999px;display:flex;align-items:center;justify-content:center;font-size:13px;color:var(--muted);}
  .theme-toggle button.active{background:linear-gradient(135deg,#7c3aed,#8b5cf6);color:#fff;}
</style>
</head>
<body>
<div class="min-h-screen pb-20">
    <header class="sticky top-0 z-30 backdrop-blur bg-black/60 border-b border-white/10 p-4 text-center">
        <img src="/assets/img/logo.jpeg" class="w-14 h-14 mx-auto rounded-2xl object-cover">
        <h1 class="font-bold text-xl mt-2"><?= e($tenant['name']) ?></h1>
        <p class="text-xs text-slate-400">Menu</p>
    </header>

    <div class="p-4">
        <?php foreach ($cats as $cat): $items = array_filter($prods, fn($p)=>$p['category_id']==$cat['id']); if(!$items)continue; ?>
        <section class="mb-8">
            <h2 class="text-2xl font-bold mb-3"><?= e($cat['icon']) ?> <?= e($cat['name']) ?></h2>
            <div class="space-y-2">
                <?php foreach ($items as $p): ?>
                <div class="rounded-xl bg-white/5 p-3 flex items-center gap-3">
                    <?php if (!empty($p['image'])): ?>
                    <img src="<?= e($p['image']) ?>" alt="<?= e($p['name']) ?>" class="w-20 h-20 sm:w-24 sm:h-24 rounded-lg object-cover flex-shrink-0" loading="lazy">
                    <?php endif; ?>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-2">
                            <h3 class="font-semibold"><?= e($p['name']) ?></h3>
                            <div class="font-bold text-emerald-400 whitespace-nowrap">€ <?= number_format($p['price'],2,',','.') ?></div>
                        </div>
                        <?php if ($p['description']): ?><p class="text-sm text-slate-400 mt-0.5"><?= e($p['description']) ?></p><?php endif; ?>
                        <?php if ($p['allergens']): ?><p class="text-xs text-amber-300 mt-1">⚠ <?= e($p['allergens']) ?></p><?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endforeach; ?>
    </div>

    <footer class="text-center text-xs text-slate-500 p-4 border-t border-white/5">
        <p><?= e($tenant['address'] ?? '') ?></p>
        <p>📞 <?= e($tenant['phone'] ?? '') ?></p>
        <p class="mt-2 text-slate-600">Powered by Lollapalooza POS</p>
    </footer>
</div>
</body></html>
