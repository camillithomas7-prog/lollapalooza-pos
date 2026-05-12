<?php layout_head('Impostazioni'); layout_sidebar('settings'); layout_topbar('Impostazioni', 'Configurazione locale');
$tn = db()->prepare('SELECT * FROM tenants WHERE id=?'); $tn->execute([tenant_id()]); $tenant = $tn->fetch();
$keyCol = DB_DRIVER === 'mysql' ? '`key`' : 'key';

// Helper per leggere/scrivere setting tenant
function _settings_save(int $tid, array $kv): void {
    $keyCol = DB_DRIVER === 'mysql' ? '`key`' : 'key';
    foreach ($kv as $k => $v) {
        $exists = db()->prepare("SELECT 1 FROM settings WHERE tenant_id=? AND $keyCol=?");
        $exists->execute([$tid, $k]);
        if ($exists->fetch()) {
            db()->prepare("UPDATE settings SET value=? WHERE tenant_id=? AND $keyCol=?")->execute([$v, $tid, $k]);
        } else {
            db()->prepare("INSERT INTO settings (tenant_id, $keyCol, value) VALUES (?,?,?)")->execute([$tid, $k, $v]);
        }
    }
}
function _settings_get(int $tid, string $k, string $def = ''): string {
    $keyCol = DB_DRIVER === 'mysql' ? '`key`' : 'key';
    $st = db()->prepare("SELECT value FROM settings WHERE tenant_id=? AND $keyCol=?");
    $st->execute([$tid, $k]);
    $r = $st->fetch();
    return $r ? (string)$r['value'] : $def;
}

if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (($_POST['form'] ?? '') === 'printer') {
        $cp   = $_POST['printer_codepage'] ?? 'cp858';
        if (!in_array($cp, ['cp858','cp1252','cp864','cp1256'], true)) $cp = 'cp858';
        $pl   = $_POST['printer_lang'] ?? 'it';
        if (!in_array($pl, ['it','en','ar','es','fr','de'], true)) $pl = 'it';
        $cols = ($_POST['printer_cols'] ?? '32') === '48' ? '48' : '32';
        _settings_save(tenant_id(), [
            'printer_codepage' => $cp,
            'printer_lang'     => $pl,
            'printer_cols'     => $cols,
        ]);
        flash('success', 'Impostazioni stampante salvate'); header('Location: /index.php?p=settings#stampante'); exit;
    } else {
        db()->prepare('UPDATE tenants SET name=?, address=?, phone=?, vat=?, currency=?, locale=?, color_primary=? WHERE id=?')
            ->execute([$_POST['name'], $_POST['address'], $_POST['phone'], $_POST['vat'], $_POST['currency'], $_POST['locale'], $_POST['color_primary'], tenant_id()]);
        flash('success', 'Impostazioni salvate'); header('Location: /index.php?p=settings'); exit;
    }
}

$cur_codepage = _settings_get(tenant_id(), 'printer_codepage', 'cp858');
$cur_printlang = _settings_get(tenant_id(), 'printer_lang', 'it');
$cur_cols      = _settings_get(tenant_id(), 'printer_cols', '32');
?>
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
            <div><label class="text-xs text-slate-400">Lingua</label><select name="locale" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10">
              <option value="it" <?= ($tenant['locale']??'it')==='it'?'selected':'' ?>>🇮🇹 Italiano</option>
              <option value="en" <?= ($tenant['locale']??'')==='en'?'selected':'' ?>>🇬🇧 English</option>
              <option value="ar" <?= ($tenant['locale']??'')==='ar'?'selected':'' ?>>🇸🇦 العربية</option>
              <option value="es" <?= ($tenant['locale']??'')==='es'?'selected':'' ?>>🇪🇸 Español</option>
              <option value="fr" <?= ($tenant['locale']??'')==='fr'?'selected':'' ?>>🇫🇷 Français</option>
              <option value="de" <?= ($tenant['locale']??'')==='de'?'selected':'' ?>>🇩🇪 Deutsch</option>
            </select></div>
            <div><label class="text-xs text-slate-400">Colore primario</label><input type="color" name="color_primary" value="<?= e($tenant['color_primary']) ?>" class="w-full h-10 rounded-lg bg-white/5 border border-white/10"></div>
            <div class="col-span-2 mt-4"><button class="btn-primary py-3 px-6 rounded-lg font-semibold">💾 Salva impostazioni</button></div>
        </form>
    </div>

    <div id="stampante" class="card p-6 max-w-3xl mt-4">
        <h3 class="font-bold mb-1">🖨️ Stampante termica comande</h3>
        <p class="text-xs text-slate-400 mb-4">Lingua, codepage e larghezza carta usati per stampare le comande in cucina/bar.</p>
        <form method="post" class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <input type="hidden" name="form" value="printer">
            <div>
                <label class="text-xs text-slate-400">Lingua di stampa</label>
                <select name="printer_lang" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10">
                    <option value="it" <?= $cur_printlang==='it'?'selected':'' ?>>🇮🇹 Italiano</option>
                    <option value="en" <?= $cur_printlang==='en'?'selected':'' ?>>🇬🇧 English</option>
                    <option value="ar" <?= $cur_printlang==='ar'?'selected':'' ?>>🇸🇦 العربية</option>
                    <option value="es" <?= $cur_printlang==='es'?'selected':'' ?>>🇪🇸 Español</option>
                    <option value="fr" <?= $cur_printlang==='fr'?'selected':'' ?>>🇫🇷 Français</option>
                    <option value="de" <?= $cur_printlang==='de'?'selected':'' ?>>🇩🇪 Deutsch</option>
                </select>
                <span class="text-[11px] text-slate-500 mt-1 block">Lingua dei nomi piatti, etichette (Tavolo, Cucina), note. Le traduzioni vanno inserite per ogni piatto in Menu.</span>
            </div>
            <div>
                <label class="text-xs text-slate-400">Codepage</label>
                <select name="printer_codepage" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10">
                    <option value="cp858" <?= $cur_codepage==='cp858'?'selected':'' ?>>Latino CP858 (default)</option>
                    <option value="cp1252" <?= $cur_codepage==='cp1252'?'selected':'' ?>>Latino CP1252</option>
                    <option value="cp864" <?= $cur_codepage==='cp864'?'selected':'' ?>>Arabo CP864 (Egitto)</option>
                    <option value="cp1256" <?= $cur_codepage==='cp1256'?'selected':'' ?>>Arabo CP1256</option>
                </select>
                <span class="text-[11px] text-slate-500 mt-1 block">Per scontrini in arabo prova prima CP864 (più diffuso sulle stampanti egiziane), poi CP1256.</span>
            </div>
            <div>
                <label class="text-xs text-slate-400">Larghezza carta</label>
                <select name="printer_cols" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10">
                    <option value="32" <?= $cur_cols==='32'?'selected':'' ?>>58 mm (32 colonne)</option>
                    <option value="48" <?= $cur_cols==='48'?'selected':'' ?>>80 mm (48 colonne)</option>
                </select>
            </div>
            <div class="col-span-1 md:col-span-3 flex items-center gap-3 flex-wrap mt-2">
                <button class="btn-primary py-2 px-5 rounded-lg font-semibold text-sm">💾 Salva</button>
                <a href="/api/print_jobs.php?action=test_codepages&dest=kitchen" target="_blank"
                   onclick="event.preventDefault(); fetch(this.href, {method:'POST'}).then(()=>alert('Pagina test accodata. Apri la pagina Cucina con la stampante collegata per stamparla.'));"
                   class="px-4 py-2 rounded-lg bg-amber-500/20 text-amber-300 hover:bg-amber-500/30 text-sm font-semibold">
                   🧪 Stampa test codepage
                </a>
                <span class="text-[11px] text-slate-500">Stampa la parola "مرحبا" in entrambi i codepage arabi. Imposta quello che esce leggibile.</span>
            </div>
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
