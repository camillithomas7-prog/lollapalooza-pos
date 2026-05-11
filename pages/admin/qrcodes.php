<?php layout_head('QR Tavoli'); layout_sidebar('qrcodes'); layout_topbar('QR Tavoli', 'Stampa i QR code da mettere sui tavoli');

$tables = db()->prepare('SELECT t.*, r.name AS room_name FROM tables t LEFT JOIN rooms r ON r.id=t.room_id WHERE t.tenant_id=? ORDER BY r.sort, t.code');
$tables->execute([tenant_id()]);
$tables = $tables->fetchAll();

$baseUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
?>
<main class="md:ml-64 lg:ml-72 p-4 md:p-8 pb-24 md:pb-8">
    <div class="card p-5 mb-4">
        <div class="flex items-center justify-between gap-2 flex-wrap">
            <div>
                <h2 class="font-bold text-lg">📱 QR Menu per Tavoli</h2>
                <p class="text-sm text-slate-400">I clienti scansionano e vedono il menu sul telefono. Stampa e attacca al tavolo.</p>
            </div>
            <button onclick="window.print()" class="btn-primary px-4 py-2 rounded-xl font-semibold text-sm">🖨 Stampa tutti</button>
        </div>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 print:grid-cols-2">
        <?php foreach ($tables as $t):
            $url = $baseUrl . '/index.php?p=qr&t=' . urlencode($t['qr_token']);
            $qrApi = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&margin=2&data=' . urlencode($url);
        ?>
        <div class="card p-4 text-center qr-card">
            <div class="bg-white rounded-xl p-3 mb-2 inline-block">
                <img src="<?= e($qrApi) ?>" alt="QR <?= e($t['code']) ?>" class="w-full max-w-[180px] mx-auto" loading="lazy">
            </div>
            <div class="font-bold text-xl"><?= e($t['code']) ?></div>
            <div class="text-xs text-slate-400"><?= e($t['room_name'] ?? '') ?> · <?= (int)$t['seats'] ?> coperti</div>
            <div class="mt-2 flex gap-1 print:hidden">
                <a href="<?= e($url) ?>" target="_blank" class="flex-1 text-xs px-2 py-1.5 rounded-lg bg-white/5 hover:bg-white/10">👁 Anteprima</a>
                <a href="<?= e($qrApi) ?>&download=1" download="qr-<?= e($t['code']) ?>.png" class="flex-1 text-xs px-2 py-1.5 rounded-lg bg-white/5 hover:bg-white/10">⬇ PNG</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if (!$tables): ?>
        <div class="card p-12 text-center text-slate-400">
            Nessun tavolo. <a href="/index.php?p=tables_editor" class="text-brand-400">Crea tavoli →</a>
        </div>
    <?php endif; ?>
</main>

<style>
@media print {
    aside, header, nav, button { display: none !important; }
    main { margin-left: 0 !important; padding: 0 !important; }
    .card { border: 1px solid #ddd !important; page-break-inside: avoid; background: white !important; color: black !important; box-shadow: none !important; }
    body { background: white !important; }
    .qr-card { color: black !important; }
}
</style>
<?php layout_mobile_nav('qrcodes'); layout_foot(); ?>
