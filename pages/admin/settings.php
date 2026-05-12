<?php layout_head('Impostazioni'); layout_sidebar('settings'); layout_topbar('Impostazioni', 'Configurazione locale');
$tn = db()->prepare('SELECT * FROM tenants WHERE id=?'); $tn->execute([tenant_id()]); $tenant = $tn->fetch();
if ($_SERVER['REQUEST_METHOD']==='POST') {
    db()->prepare('UPDATE tenants SET name=?, address=?, phone=?, vat=?, currency=?, locale=?, color_primary=? WHERE id=?')
        ->execute([$_POST['name'], $_POST['address'], $_POST['phone'], $_POST['vat'], $_POST['currency'], $_POST['locale'], $_POST['color_primary'], tenant_id()]);
    flash('success', 'Impostazioni salvate'); header('Location: /index.php?p=settings'); exit;
} ?>
<main class="md:ml-64 lg:ml-72 p-4 md:p-8 pb-24 md:pb-8">
    <?php layout_flashes(); ?>
    <div class="card p-6 max-w-3xl">
        <form method="post" class="grid grid-cols-2 gap-3">
            <div class="col-span-2"><label class="text-xs text-slate-400">Nome locale</label><input name="name" value="<?= e($tenant['name']) ?>" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10"></div>
            <div class="col-span-2"><label class="text-xs text-slate-400">Indirizzo</label><input name="address" value="<?= e($tenant['address']) ?>" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10"></div>
            <div><label class="text-xs text-slate-400">Telefono</label><input name="phone" value="<?= e($tenant['phone']) ?>" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10"></div>
            <div><label class="text-xs text-slate-400">P.IVA</label><input name="vat" value="<?= e($tenant['vat']) ?>" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10"></div>
            <div><label class="text-xs text-slate-400">Valuta</label><select name="currency" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10">
              <option value="EGP" <?= $tenant['currency']==='EGP'?'selected':'' ?>>EGP LE (Lira Egiziana)</option>
              <option value="EUR" <?= $tenant['currency']==='EUR'?'selected':'' ?>>EUR € (Euro)</option>
              <option value="USD" <?= $tenant['currency']==='USD'?'selected':'' ?>>USD $</option>
              <option value="GBP" <?= $tenant['currency']==='GBP'?'selected':'' ?>>GBP £</option>
            </select></div>
            <div><label class="text-xs text-slate-400">Lingua</label><select name="locale" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10"><option value="it">Italiano</option><option value="en">English</option></select></div>
            <div><label class="text-xs text-slate-400">Colore primario</label><input type="color" name="color_primary" value="<?= e($tenant['color_primary']) ?>" class="w-full h-10 rounded-lg bg-white/5 border border-white/10"></div>
            <div class="col-span-2 mt-4"><button class="btn-primary py-3 px-6 rounded-lg font-semibold">💾 Salva impostazioni</button></div>
        </form>
    </div>

    <div class="card p-6 max-w-3xl mt-4">
        <h3 class="font-bold mb-3">🔧 Stato sistema</h3>
        <div class="grid grid-cols-2 gap-3 text-sm">
            <div><div class="text-slate-400">Versione</div><div class="font-bold"><?= APP_VER ?></div></div>
            <div><div class="text-slate-400">Database</div><div class="font-bold text-emerald-400">SQLite</div></div>
            <div><div class="text-slate-400">PHP</div><div class="font-bold"><?= PHP_VERSION ?></div></div>
            <div><div class="text-slate-400">Tenant ID</div><div class="font-bold"><?= tenant_id() ?></div></div>
        </div>
    </div>
</main>
<?php layout_mobile_nav('settings'); layout_foot(); ?>
