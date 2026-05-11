<?php layout_head('QR'); layout_sidebar('qrcodes'); layout_topbar('QR Brandizzati', 'Menu vetrina · Prenotazioni · Ordini self-service');

$baseUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];

$menuUrl = $baseUrl . '/index.php?p=carta';
$bookUrl = $baseUrl . '/index.php?p=prenota';

$qrApiMenu = 'https://api.qrserver.com/v1/create-qr-code/?size=600x600&margin=0&format=png&data=' . urlencode($menuUrl);
$qrApiBook = 'https://api.qrserver.com/v1/create-qr-code/?size=600x600&margin=0&format=png&data=' . urlencode($bookUrl);

$tn = db()->prepare('SELECT * FROM tenants WHERE id=?');
$tn->execute([tenant_id()]);
$tenant = $tn->fetch();

// Tavoli
$tables = db()->prepare('SELECT t.*, r.name AS room_name FROM `tables` t LEFT JOIN rooms r ON r.id=t.room_id WHERE t.tenant_id=? ORDER BY r.sort, t.code');
$tables->execute([tenant_id()]);
$tables = $tables->fetchAll();
?>
<main class="md:ml-64 lg:ml-72 p-4 md:p-8 pb-24 md:pb-8" x-data="qrPoster()">
    <!-- Tab selector -->
    <div class="flex gap-2 mb-5 overflow-x-auto" style="scrollbar-width:thin">
        <button @click="tab='menu'" :class="tab==='menu'?'btn-primary':'bg-white/5'" class="px-4 py-2.5 rounded-xl font-semibold text-sm flex items-center gap-2 whitespace-nowrap">📖 Menu</button>
        <button @click="tab='book'" :class="tab==='book'?'btn-primary':'bg-white/5'" class="px-4 py-2.5 rounded-xl font-semibold text-sm flex items-center gap-2 whitespace-nowrap">🗓 Prenotazioni</button>
        <button @click="tab='tables'" :class="tab==='tables'?'btn-primary':'bg-white/5'" class="px-4 py-2.5 rounded-xl font-semibold text-sm flex items-center gap-2 whitespace-nowrap">🪑 QR Tavoli (<?= count($tables) ?>)</button>
    </div>

    <!-- TAB Menu/Prenotazioni (poster singolo) -->
    <div x-show="tab==='menu' || tab==='book'" class="grid lg:grid-cols-[1fr,360px] gap-6">
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
                            <img :src="tab==='menu' ? '<?= e($qrApiMenu) ?>' : '<?= e($qrApiBook) ?>'" alt="QR" crossorigin="anonymous" class="poster-qr">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="space-y-3 print:hidden">
            <div class="card p-5">
                <h3 class="font-bold mb-3">⬇ Scarica e stampa</h3>
                <div class="space-y-2">
                    <button @click="downloadPng('poster', `qr-${tab}`)" class="w-full btn-primary py-3 rounded-xl font-semibold" x-text="loading?'⏳...':'🖼 PNG alta qualità'"></button>
                    <button onclick="window.print()" class="w-full py-3 rounded-xl bg-white/5 hover:bg-white/10 font-semibold">🖨 Stampa</button>
                </div>
            </div>
            <div class="card p-5">
                <h3 class="font-bold mb-2" x-text="tab==='menu'?'🔗 URL menu':'🔗 URL prenotazioni'"></h3>
                <div class="flex gap-2">
                    <input type="text" readonly :value="tab==='menu'?menuUrl:bookUrl" class="flex-1 px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-xs font-mono min-w-0" onclick="this.select()">
                    <button @click="copyUrl(tab==='menu'?menuUrl:bookUrl)" class="px-3 py-2 rounded-lg bg-brand-500/20 text-brand-300 text-sm" x-text="copied?'✓':'📋'"></button>
                </div>
                <a :href="tab==='menu'?menuUrl:bookUrl" target="_blank" class="block mt-3 text-sm text-brand-400">👁 Anteprima →</a>
            </div>
            <div class="card p-4 text-xs text-slate-400" x-show="tab==='menu'">💡 Menu vetrina: i clienti consultano il menu sul telefono. Non possono ordinare. Stampalo all'ingresso o vetrofania.</div>
            <div class="card p-4 text-xs text-slate-400" x-show="tab==='book'">💡 Prenotazione: i clienti scelgono data/ora/coperti. Arriva in <a href="/index.php?p=reservations" class="text-brand-400">Prenotazioni</a> con stato pending.</div>
        </div>
    </div>

    <!-- TAB Tavoli (multi-QR ordine self-service) -->
    <div x-show="tab==='tables'" x-cloak>
        <div class="card p-4 mb-4 flex items-center justify-between gap-3 flex-wrap">
            <div>
                <h3 class="font-bold">🛎 Ordini self-service dal tavolo</h3>
                <p class="text-xs text-slate-400 mt-0.5">Ogni tavolo ha il suo QR. Il cliente scansiona, ordina, l'ordine arriva in cucina/bar/al cameriere col tavolo già associato.</p>
            </div>
            <button onclick="window.print()" class="btn-primary px-4 py-2 rounded-xl text-sm font-semibold whitespace-nowrap">🖨 Stampa tutti</button>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 print:grid-cols-2">
            <?php foreach ($tables as $t):
                $tableMenuUrl = $baseUrl . '/index.php?p=carta&t=' . urlencode($t['qr_token']);
                $tQr = 'https://api.qrserver.com/v1/create-qr-code/?size=400x400&margin=0&data=' . urlencode($tableMenuUrl);
            ?>
            <div class="table-poster qr-card">
                <div class="table-poster-header">
                    <img src="/assets/img/logo.jpeg" class="tp-logo" alt="" crossorigin="anonymous">
                    <div class="tp-name"><?= e($tenant['name'] ?? 'Lollapalooza') ?></div>
                </div>
                <div class="tp-table"><?= e($t['code']) ?></div>
                <div class="tp-room"><?= e($t['room_name']) ?> · <?= (int)$t['seats'] ?> coperti</div>
                <div class="tp-qr-wrap">
                    <img src="<?= e($tQr) ?>" alt="QR" crossorigin="anonymous" class="tp-qr" loading="lazy">
                </div>
                <div class="tp-cta">Scansiona e ordina</div>
                <div class="tp-actions print:hidden">
                    <a href="<?= e($tableMenuUrl) ?>" target="_blank" class="tp-btn">👁</a>
                    <a href="<?= e($tQr) ?>&size=1500x1500&download=1" download="qr-tavolo-<?= e($t['code']) ?>.png" class="tp-btn">⬇</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (!$tables): ?>
            <div class="card p-12 text-center text-slate-400">Nessun tavolo. <a href="/index.php?p=tables_editor" class="text-brand-400">Crea tavoli →</a></div>
        <?php endif; ?>
    </div>
</main>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
function qrPoster(){
    return {
        tab: 'menu', style: 'dark', loading: false, copied: false,
        menuUrl: <?= json_encode($menuUrl) ?>,
        bookUrl: <?= json_encode($bookUrl) ?>,
        copyUrl(url){ navigator.clipboard.writeText(url); this.copied=true; setTimeout(()=>this.copied=false,1500); },
        async downloadPng(elId, filename){
            this.loading=true; await new Promise(r=>setTimeout(r,100));
            try {
                const canvas = await html2canvas(document.getElementById(elId), {scale:2, backgroundColor:null, useCORS:true, logging:false});
                const link = document.createElement('a');
                link.download = `${filename}.png`; link.href = canvas.toDataURL('image/png'); link.click();
            } catch(e){ alert('Errore: '+e.message); }
            this.loading=false;
        }
    }
}
</script>

<style>
/* Poster grande (menu/prenotazioni) */
.poster { width:520px; aspect-ratio:4/5; max-width:100%; padding:56px 48px; border-radius:32px; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:36px; box-shadow:0 20px 60px -10px rgba(0,0,0,0.3); position:relative; overflow:hidden; font-family:'Inter',sans-serif; }
.poster-dark { background:linear-gradient(160deg,#1a0a3e 0%,#0a0a13 60%,#1a1a2e 100%); color:#f1f5f9; }
.poster-dark::before { content:''; position:absolute; inset:0; background:radial-gradient(circle at 30% 20%,rgba(139,92,246,0.25),transparent 50%),radial-gradient(circle at 70% 80%,rgba(14,165,233,0.15),transparent 50%); pointer-events:none; }
.poster-dark .poster-divider { background:linear-gradient(90deg,transparent,#8b5cf6,transparent); }
.poster-dark .poster-qr-box { background:#fff; box-shadow:0 8px 32px rgba(139,92,246,0.4); }
.poster-dark .poster-subtitle { color:#a78bfa; }
.poster-light { background:linear-gradient(160deg,#fafafa,#f5f5f7); color:#0f172a; }
.poster-light::before { content:''; position:absolute; inset:0; background:radial-gradient(circle at 30% 20%,rgba(139,92,246,0.08),transparent 50%); pointer-events:none; }
.poster-light .poster-divider { background:linear-gradient(90deg,transparent,#7c3aed,transparent); }
.poster-light .poster-qr-box { background:#fff; border:1px solid rgba(15,23,42,0.08); box-shadow:0 8px 32px rgba(15,23,42,0.08); }
.poster-light .poster-subtitle { color:#7c3aed; }
.poster-gold { background:linear-gradient(160deg,#1a1208,#0a0703 60%,#2a1b08); color:#fef3c7; }
.poster-gold::before { content:''; position:absolute; inset:0; background:radial-gradient(circle at 30% 20%,rgba(234,179,8,0.2),transparent 50%),radial-gradient(circle at 70% 80%,rgba(217,119,6,0.15),transparent 50%); pointer-events:none; }
.poster-gold .poster-divider { background:linear-gradient(90deg,transparent,#eab308,transparent); }
.poster-gold .poster-qr-box { background:#fff; box-shadow:0 8px 32px rgba(234,179,8,0.4); }
.poster-gold .poster-subtitle { color:#fbbf24; letter-spacing:0.32em; }
.poster-gold .poster-title { background:linear-gradient(180deg,#fef3c7,#eab308); -webkit-background-clip:text; background-clip:text; -webkit-text-fill-color:transparent; }
.poster-silver { background:linear-gradient(160deg,#2a2a35 0%,#0e0e15 55%,#1e1e2a); color:#f1f5f9; }
.poster-silver::before { content:''; position:absolute; inset:0; background:radial-gradient(circle at 25% 18%,rgba(226,232,240,0.22),transparent 55%),radial-gradient(circle at 75% 82%,rgba(148,163,184,0.18),transparent 55%); pointer-events:none; }
.poster-silver::after { content:''; position:absolute; inset:0; background:linear-gradient(115deg,transparent 30%,rgba(255,255,255,0.06) 50%,transparent 70%); pointer-events:none; }
.poster-silver .poster-divider { background:linear-gradient(90deg,transparent,#cbd5e1,transparent); }
.poster-silver .poster-qr-box { background:#fff; box-shadow:0 8px 32px rgba(203,213,225,0.35),0 0 0 1px rgba(255,255,255,0.1); }
.poster-silver .poster-subtitle { background:linear-gradient(180deg,#f8fafc,#94a3b8); -webkit-background-clip:text; background-clip:text; -webkit-text-fill-color:transparent; letter-spacing:0.32em; }
.poster-silver .poster-title { background:linear-gradient(180deg,#ffffff,#e2e8f0 45%,#94a3b8); -webkit-background-clip:text; background-clip:text; -webkit-text-fill-color:transparent; }
.poster-header { text-align:center; position:relative; z-index:1; width:100%; }
.poster-logo { width:68px; height:68px; border-radius:16px; object-fit:cover; margin:0 auto 14px; display:block; box-shadow:0 4px 16px rgba(0,0,0,0.2); }
.poster-title { font-size:32px; font-weight:800; letter-spacing:-0.02em; margin:0; line-height:1; }
.poster-divider { width:48px; height:2px; margin:12px auto 10px; border-radius:1px; }
.poster-subtitle { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.32em; margin:0; }
.poster-qr-wrap { display:flex; align-items:center; justify-content:center; position:relative; z-index:1; }
.poster-qr-box { display:inline-block; padding:14px; border-radius:16px; }
.poster-qr { display:block; width:280px; height:280px; }

/* Poster tavolo (mini) */
.table-poster { background:linear-gradient(160deg,#1a0a3e 0%,#0a0a13 60%,#1a1a2e 100%); border-radius:20px; padding:20px 14px; text-align:center; color:#f1f5f9; position:relative; overflow:hidden; box-shadow:0 8px 24px -8px rgba(0,0,0,0.3); }
.table-poster::before { content:''; position:absolute; inset:0; background:radial-gradient(circle at 50% 0%,rgba(139,92,246,0.25),transparent 60%); pointer-events:none; }
.table-poster-header { display:flex; align-items:center; justify-content:center; gap:6px; margin-bottom:8px; position:relative; z-index:1; }
.tp-logo { width:24px; height:24px; border-radius:6px; object-fit:cover; }
.tp-name { font-size:11px; font-weight:700; opacity:0.85; }
.tp-table { font-size:32px; font-weight:800; letter-spacing:-0.02em; line-height:1; position:relative; z-index:1; }
.tp-room { font-size:10px; color:#a78bfa; font-weight:600; text-transform:uppercase; letter-spacing:0.1em; margin-bottom:12px; position:relative; z-index:1; }
.tp-qr-wrap { background:#fff; border-radius:10px; padding:8px; margin:0 auto; display:inline-block; position:relative; z-index:1; }
.tp-qr { display:block; width:140px; height:140px; }
.tp-cta { font-size:11px; font-weight:700; margin-top:10px; opacity:0.9; position:relative; z-index:1; letter-spacing:0.05em; }
.tp-actions { display:flex; gap:6px; justify-content:center; margin-top:10px; position:relative; z-index:1; }
.tp-btn { padding:6px 12px; background:rgba(255,255,255,0.1); border-radius:8px; font-size:12px; }

@media (max-width:600px) {
    .poster { padding:36px 28px; gap:24px; }
    .poster-logo { width:56px; height:56px; }
    .poster-title { font-size:26px; }
    .poster-qr { width:220px; height:220px; }
    .tp-qr { width:120px; height:120px; }
}
@media print {
    aside,header,nav,.print\:hidden { display:none !important; }
    main { margin-left:0 !important; padding:0 !important; }
    body,html { background:white !important; }
    .table-poster, .poster { page-break-inside:avoid; box-shadow:none; }
}
</style>
<?php layout_mobile_nav('qrcodes'); layout_foot(); ?>
