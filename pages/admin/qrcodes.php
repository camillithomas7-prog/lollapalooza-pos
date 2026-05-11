<?php layout_head('QR Menu'); layout_sidebar('qrcodes'); layout_topbar('QR Menu', 'Genera, stampa e condividi il QR del menu pubblico');

$baseUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
$menuUrl = $baseUrl . '/index.php?p=carta';

$tn = db()->prepare('SELECT * FROM tenants WHERE id=?');
$tn->execute([tenant_id()]);
$tenant = $tn->fetch();
?>
<main class="md:ml-64 lg:ml-72 p-4 md:p-8 pb-24 md:pb-8">
    <div class="grid lg:grid-cols-2 gap-4">
        <!-- QR grande -->
        <div class="card p-6 text-center qr-card">
            <div class="bg-white rounded-2xl p-6 mb-4 inline-block">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=500x500&margin=2&data=<?= urlencode($menuUrl) ?>"
                     alt="QR Menu" class="w-full max-w-[300px] mx-auto" id="qrImg">
            </div>
            <div class="font-bold text-2xl mb-1"><?= e($tenant['name'] ?? 'Menu') ?></div>
            <div class="text-sm text-slate-400 mb-4">Scansiona per vedere il menu</div>
            <div class="flex gap-2 justify-center flex-wrap print:hidden">
                <button onclick="window.print()" class="btn-primary px-5 py-2.5 rounded-xl font-semibold text-sm">🖨 Stampa</button>
                <a href="https://api.qrserver.com/v1/create-qr-code/?size=1000x1000&margin=2&data=<?= urlencode($menuUrl) ?>&download=1"
                   download="qr-menu-<?= e($tenant['slug'] ?? 'lollapalooza') ?>.png"
                   class="px-5 py-2.5 rounded-xl bg-white/5 hover:bg-white/10 text-sm font-semibold">⬇ PNG (alta qualità)</a>
            </div>
        </div>

        <!-- Info & azioni -->
        <div class="space-y-4 print:hidden">
            <div class="card p-5">
                <h3 class="font-bold mb-2">🔗 URL pubblico</h3>
                <p class="text-sm text-slate-400 mb-3">Questo è il link che i clienti aprono scansionando il QR:</p>
                <div class="flex gap-2">
                    <input type="text" readonly value="<?= e($menuUrl) ?>" id="urlInput"
                           class="flex-1 px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-xs font-mono" onclick="this.select()">
                    <button onclick="navigator.clipboard.writeText(document.getElementById('urlInput').value).then(()=>{this.textContent='✓ Copiato';setTimeout(()=>this.textContent='📋 Copia',1500)})"
                            class="px-3 py-2 rounded-lg bg-brand-500/20 text-brand-300 text-sm whitespace-nowrap">📋 Copia</button>
                </div>
                <a href="<?= e($menuUrl) ?>" target="_blank" class="block mt-3 text-sm text-brand-400 hover:text-brand-300">👁 Anteprima menu →</a>
            </div>

            <div class="card p-5">
                <h3 class="font-bold mb-2">💡 Come usarlo</h3>
                <ol class="text-sm text-slate-400 space-y-2 list-decimal list-inside">
                    <li>Stampa il QR (o scarica il PNG e usalo come vuoi)</li>
                    <li>Mettilo sui tavoli, al bancone, sulla vetrina, sul menu cartaceo, ecc.</li>
                    <li>I clienti scansionano con il telefono</li>
                    <li>Vedono il menu in tempo reale (categorie, prezzi, allergeni)</li>
                    <li>Quando il menu cambia, il QR resta sempre lo stesso — niente da ristampare</li>
                </ol>
            </div>

            <div class="card p-5">
                <h3 class="font-bold mb-2">📱 Condividi</h3>
                <div class="grid grid-cols-2 gap-2">
                    <a href="https://wa.me/?text=<?= urlencode('Ecco il nostro menu: ' . $menuUrl) ?>" target="_blank"
                       class="px-4 py-2 rounded-xl bg-emerald-500/20 text-emerald-400 text-sm font-semibold text-center">WhatsApp</a>
                    <a href="mailto:?subject=Menu&body=<?= urlencode($menuUrl) ?>"
                       class="px-4 py-2 rounded-xl bg-sky-500/20 text-sky-400 text-sm font-semibold text-center">Email</a>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
@media print {
    aside, header, nav, button, .print\:hidden { display: none !important; }
    main { margin-left: 0 !important; padding: 0 !important; }
    .card { border: 1px solid #ddd !important; background: white !important; color: black !important; box-shadow: none !important; }
    body, html { background: white !important; }
    .qr-card { color: black !important; }
    .qr-card .text-slate-400 { color: #666 !important; }
}
</style>
<?php layout_mobile_nav('qrcodes'); layout_foot(); ?>
