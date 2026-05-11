<?php layout_head('QR'); layout_sidebar('qrcodes'); layout_topbar('QR Brandizzati', 'Menu e prenotazioni con un QR ciascuno');

$baseUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];

$menuUrl = $baseUrl . '/index.php?p=carta';
$bookUrl = $baseUrl . '/index.php?p=prenota';

$qrApiMenu = 'https://api.qrserver.com/v1/create-qr-code/?size=600x600&margin=0&format=png&data=' . urlencode($menuUrl);
$qrApiBook = 'https://api.qrserver.com/v1/create-qr-code/?size=600x600&margin=0&format=png&data=' . urlencode($bookUrl);

$tn = db()->prepare('SELECT * FROM tenants WHERE id=?');
$tn->execute([tenant_id()]);
$tenant = $tn->fetch();
?>
<main class="md:ml-64 lg:ml-72 p-4 md:p-8 pb-24 md:pb-8" x-data="qrPoster()">
    <!-- Tab selector tipo/contenuto -->
    <div class="flex gap-2 mb-5">
        <button @click="tab='menu'" :class="tab==='menu'?'btn-primary':'bg-white/5'" class="px-5 py-2.5 rounded-xl font-semibold text-sm flex items-center gap-2">📖 QR Menu</button>
        <button @click="tab='book'" :class="tab==='book'?'btn-primary':'bg-white/5'" class="px-5 py-2.5 rounded-xl font-semibold text-sm flex items-center gap-2">🗓 QR Prenotazioni</button>
    </div>

    <div class="grid lg:grid-cols-[1fr,360px] gap-6">
        <!-- Poster preview -->
        <div>
            <div class="flex items-center gap-2 mb-3 flex-wrap">
                <span class="text-sm text-slate-400">Stile:</span>
                <button @click="style='dark'" :class="style==='dark'?'btn-primary':'bg-white/5'" class="px-3 py-1.5 rounded-lg text-xs font-semibold">🌙 Scuro</button>
                <button @click="style='light'" :class="style==='light'?'btn-primary':'bg-white/5'" class="px-3 py-1.5 rounded-lg text-xs font-semibold">☀️ Chiaro</button>
                <button @click="style='gold'" :class="style==='gold'?'btn-primary':'bg-white/5'" class="px-3 py-1.5 rounded-lg text-xs font-semibold">✨ Gold</button>
                <button @click="style='silver'" :class="style==='silver'?'btn-primary':'bg-white/5'" class="px-3 py-1.5 rounded-lg text-xs font-semibold">🪙 Silver</button>
            </div>

            <div class="flex justify-center">
                <div id="poster" :class="`poster poster-${style}`">
                    <div class="poster-header">
                        <img src="/assets/img/logo.jpeg" alt="Logo" crossorigin="anonymous" class="poster-logo">
                        <h1 class="poster-title"><?= e($tenant['name'] ?? 'Lollapalooza') ?></h1>
                        <div class="poster-divider"></div>
                        <p class="poster-subtitle" x-text="tab==='menu'?'MENU':'PRENOTA'"></p>
                    </div>
                    <div class="poster-qr-wrap">
                        <div class="poster-qr-box">
                            <img id="qrImg" :src="tab==='menu' ? '<?= e($qrApiMenu) ?>' : '<?= e($qrApiBook) ?>'" alt="QR Code" crossorigin="anonymous" class="poster-qr">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Side controls -->
        <div class="space-y-3 print:hidden">
            <div class="card p-5">
                <h3 class="font-bold mb-3">⬇ Scarica e stampa</h3>
                <div class="space-y-2">
                    <button @click="downloadPng" class="w-full btn-primary py-3 rounded-xl font-semibold flex items-center justify-center gap-2">
                        <span x-show="!loading">🖼 Scarica PNG alta qualità</span>
                        <span x-show="loading">⏳ Genero immagine...</span>
                    </button>
                    <button onclick="window.print()" class="w-full py-3 rounded-xl bg-white/5 hover:bg-white/10 font-semibold">🖨 Stampa diretta</button>
                    <a :href="`https://api.qrserver.com/v1/create-qr-code/?size=1500x1500&margin=0&format=png&data=${encodeURIComponent(activeUrl())}&download=1`" :download="`qr-${tab}.png`" class="block w-full py-2.5 text-center rounded-xl bg-white/5 hover:bg-white/10 text-sm">Solo QR code 1500×1500 ⬇</a>
                </div>
            </div>

            <div class="card p-5">
                <h3 class="font-bold mb-2" x-text="tab==='menu'?'🔗 URL del menu':'🔗 URL prenotazioni'"></h3>
                <p class="text-xs text-slate-400 mb-2" x-text="tab==='menu'?'Apre il menu sempre aggiornato.':'Form prenotazione, salva direttamente in agenda.'"></p>
                <div class="flex gap-2">
                    <input type="text" readonly :value="activeUrl()" class="flex-1 px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-xs font-mono min-w-0" onclick="this.select()">
                    <button @click="copyUrl" class="px-3 py-2 rounded-lg bg-brand-500/20 text-brand-300 text-sm whitespace-nowrap" x-text="copied?'✓':'📋'"></button>
                </div>
                <a :href="activeUrl()" target="_blank" class="block mt-3 text-sm text-brand-400">👁 Anteprima →</a>
            </div>

            <div class="card p-5">
                <h3 class="font-bold mb-2">📲 Condividi</h3>
                <div class="grid grid-cols-2 gap-2">
                    <a :href="`https://wa.me/?text=${encodeURIComponent((tab==='menu'?'Il nostro menu: ':'Prenota un tavolo da noi: ') + activeUrl())}`" target="_blank" class="px-3 py-2 rounded-xl bg-emerald-500/20 text-emerald-400 text-sm font-semibold text-center">WhatsApp</a>
                    <a :href="`mailto:?subject=${encodeURIComponent(tab==='menu'?'Menu':'Prenotazione')}&body=${encodeURIComponent(activeUrl())}`" class="px-3 py-2 rounded-xl bg-sky-500/20 text-sky-400 text-sm font-semibold text-center">Email</a>
                </div>
            </div>

            <div class="card p-4 text-xs text-slate-400">
                💡 <strong class="text-slate-300">Tip</strong>: 2 QR distinti — uno per consultare il menu, uno per prenotare. Stampali entrambi e affiancali sull'ingresso/vetrofania. Le prenotazioni arrivano in <a href="/index.php?p=reservations" class="text-brand-400">Prenotazioni</a> con stato "pending".
            </div>
        </div>
    </div>
</main>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
function qrPoster(){
    return {
        tab: 'menu',
        style: 'dark',
        loading: false,
        copied: false,
        menuUrl: <?= json_encode($menuUrl) ?>,
        bookUrl: <?= json_encode($bookUrl) ?>,
        activeUrl(){ return this.tab === 'menu' ? this.menuUrl : this.bookUrl; },
        copyUrl(){
            navigator.clipboard.writeText(this.activeUrl());
            this.copied = true;
            setTimeout(()=>this.copied=false, 1500);
        },
        async downloadPng(){
            this.loading = true;
            await new Promise(r => setTimeout(r, 100));
            try {
                const el = document.getElementById('poster');
                const canvas = await html2canvas(el, { scale: 2, backgroundColor: null, useCORS: true, logging: false });
                const link = document.createElement('a');
                link.download = `poster-qr-${this.tab}-<?= e($tenant['slug'] ?? 'lollapalooza') ?>.png`;
                link.href = canvas.toDataURL('image/png');
                link.click();
            } catch(e) { alert('Errore generazione: ' + e.message); }
            this.loading = false;
        }
    }
}
</script>

<style>
.poster { width:520px; aspect-ratio:4/5; max-width:100%; padding:56px 48px; border-radius:32px; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:36px; box-shadow:0 20px 60px -10px rgba(0,0,0,0.3); position:relative; overflow:hidden; font-family:'Inter',sans-serif; }
.poster-dark { background:linear-gradient(160deg,#1a0a3e 0%,#0a0a13 60%,#1a1a2e 100%); color:#f1f5f9; }
.poster-dark::before { content:''; position:absolute; inset:0; background:radial-gradient(circle at 30% 20%,rgba(139,92,246,0.25),transparent 50%),radial-gradient(circle at 70% 80%,rgba(14,165,233,0.15),transparent 50%); pointer-events:none; }
.poster-dark .poster-divider { background:linear-gradient(90deg,transparent,#8b5cf6,transparent); }
.poster-dark .poster-qr-box { background:#fff; box-shadow:0 8px 32px rgba(139,92,246,0.4); }
.poster-dark .poster-subtitle { color:#a78bfa; }
.poster-light { background:linear-gradient(160deg,#fafafa 0%,#f5f5f7 100%); color:#0f172a; }
.poster-light::before { content:''; position:absolute; inset:0; background:radial-gradient(circle at 30% 20%,rgba(139,92,246,0.08),transparent 50%); pointer-events:none; }
.poster-light .poster-divider { background:linear-gradient(90deg,transparent,#7c3aed,transparent); }
.poster-light .poster-qr-box { background:#fff; border:1px solid rgba(15,23,42,0.08); box-shadow:0 8px 32px rgba(15,23,42,0.08); }
.poster-light .poster-subtitle { color:#7c3aed; }
.poster-gold { background:linear-gradient(160deg,#1a1208 0%,#0a0703 60%,#2a1b08 100%); color:#fef3c7; }
.poster-gold::before { content:''; position:absolute; inset:0; background:radial-gradient(circle at 30% 20%,rgba(234,179,8,0.2),transparent 50%),radial-gradient(circle at 70% 80%,rgba(217,119,6,0.15),transparent 50%); pointer-events:none; }
.poster-gold .poster-divider { background:linear-gradient(90deg,transparent,#eab308,transparent); }
.poster-gold .poster-qr-box { background:#fff; box-shadow:0 8px 32px rgba(234,179,8,0.4); }
.poster-gold .poster-subtitle { color:#fbbf24; letter-spacing:0.32em; }
.poster-gold .poster-title { background:linear-gradient(180deg,#fef3c7,#eab308); -webkit-background-clip:text; background-clip:text; -webkit-text-fill-color:transparent; }
.poster-silver { background:linear-gradient(160deg,#2a2a35 0%,#0e0e15 55%,#1e1e2a 100%); color:#f1f5f9; }
.poster-silver::before { content:''; position:absolute; inset:0; background:radial-gradient(circle at 25% 18%,rgba(226,232,240,0.22),transparent 55%),radial-gradient(circle at 75% 82%,rgba(148,163,184,0.18),transparent 55%); pointer-events:none; }
.poster-silver::after { content:''; position:absolute; inset:0; background:linear-gradient(115deg,transparent 30%,rgba(255,255,255,0.06) 50%,transparent 70%); pointer-events:none; }
.poster-silver .poster-divider { background:linear-gradient(90deg,transparent,#cbd5e1,transparent); }
.poster-silver .poster-qr-box { background:#fff; box-shadow:0 8px 32px rgba(203,213,225,0.35),0 0 0 1px rgba(255,255,255,0.1); }
.poster-silver .poster-subtitle { background:linear-gradient(180deg,#f8fafc,#94a3b8); -webkit-background-clip:text; background-clip:text; -webkit-text-fill-color:transparent; letter-spacing:0.32em; }
.poster-silver .poster-title { background:linear-gradient(180deg,#ffffff 0%,#e2e8f0 45%,#94a3b8 100%); -webkit-background-clip:text; background-clip:text; -webkit-text-fill-color:transparent; }
.poster-header { text-align:center; position:relative; z-index:1; width:100%; }
.poster-logo { width:68px; height:68px; border-radius:16px; object-fit:cover; margin:0 auto 14px; display:block; box-shadow:0 4px 16px rgba(0,0,0,0.2); }
.poster-title { font-size:32px; font-weight:800; letter-spacing:-0.02em; margin:0; line-height:1; }
.poster-divider { width:48px; height:2px; margin:12px auto 10px; border-radius:1px; }
.poster-subtitle { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.32em; margin:0; }
.poster-qr-wrap { display:flex; align-items:center; justify-content:center; position:relative; z-index:1; }
.poster-qr-box { display:inline-block; padding:14px; border-radius:16px; }
.poster-qr { display:block; width:280px; height:280px; }
@media (max-width:600px) {
    .poster { padding:36px 28px; gap:24px; }
    .poster-logo { width:56px; height:56px; }
    .poster-title { font-size:26px; }
    .poster-qr { width:220px; height:220px; }
}
@media print {
    aside,header,nav,.print\:hidden { display:none !important; }
    main { margin-left:0 !important; padding:0 !important; }
    body,html { background:white !important; }
    .poster { box-shadow:none; page-break-inside:avoid; }
}
</style>
<?php layout_mobile_nav('qrcodes'); layout_foot(); ?>
