<?php layout_head('QR Menu'); layout_sidebar('qrcodes'); layout_topbar('QR Menu', 'Poster QR brandizzato per il tuo locale');

$baseUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
$menuUrl = $baseUrl . '/index.php?p=carta';
$qrApi = 'https://api.qrserver.com/v1/create-qr-code/?size=600x600&margin=0&format=png&data=' . urlencode($menuUrl);

$tn = db()->prepare('SELECT * FROM tenants WHERE id=?');
$tn->execute([tenant_id()]);
$tenant = $tn->fetch();
?>
<main class="md:ml-64 lg:ml-72 p-4 md:p-8 pb-24 md:pb-8" x-data="qrPoster()">
    <div class="grid lg:grid-cols-[1fr,360px] gap-6">
        <!-- Poster preview -->
        <div>
            <div class="flex items-center gap-2 mb-3 flex-wrap">
                <span class="text-sm text-slate-400">Stile:</span>
                <button @click="style='dark'" :class="style==='dark'?'btn-primary':'bg-white/5'" class="px-3 py-1.5 rounded-lg text-xs font-semibold">🌙 Scuro</button>
                <button @click="style='light'" :class="style==='light'?'btn-primary':'bg-white/5'" class="px-3 py-1.5 rounded-lg text-xs font-semibold">☀️ Chiaro</button>
                <button @click="style='gold'" :class="style==='gold'?'btn-primary':'bg-white/5'" class="px-3 py-1.5 rounded-lg text-xs font-semibold">✨ Gold</button>
                <button @click="style='minimal'" :class="style==='minimal'?'btn-primary':'bg-white/5'" class="px-3 py-1.5 rounded-lg text-xs font-semibold">⚪ Minimal</button>
            </div>

            <div class="flex justify-center">
                <div id="poster" :class="`poster poster-${style}`">
                    <!-- TOP: logo + nome -->
                    <div class="poster-header">
                        <img src="/assets/img/logo.jpeg" alt="Logo" crossorigin="anonymous" class="poster-logo">
                        <h1 class="poster-title"><?= e($tenant['name'] ?? 'Lollapalooza') ?></h1>
                        <div class="poster-divider"></div>
                        <p class="poster-subtitle">Menu Digitale</p>
                    </div>

                    <!-- CENTER: QR + claim -->
                    <div class="poster-qr-wrap">
                        <div class="poster-qr-box">
                            <img src="<?= e($qrApi) ?>" alt="QR Code" crossorigin="anonymous" id="qrImg" class="poster-qr">
                        </div>
                    </div>

                    <!-- CALL-TO-ACTION -->
                    <div class="poster-cta">
                        <div class="poster-cta-title">Scansiona con il telefono</div>
                        <div class="poster-cta-sub">📷 Apri la fotocamera · inquadra il codice</div>
                    </div>

                    <!-- BENEFITS -->
                    <div class="poster-benefits">
                        <div class="poster-benefit"><span>📖</span> Menu completo</div>
                        <div class="poster-benefit"><span>🌿</span> Allergeni</div>
                        <div class="poster-benefit"><span>💰</span> Prezzi</div>
                    </div>

                    <!-- FOOTER -->
                    <div class="poster-footer">
                        <?php if (!empty($tenant['phone'])): ?><div>📞 <?= e($tenant['phone']) ?></div><?php endif; ?>
                        <?php if (!empty($tenant['address'])): ?><div>📍 <?= e($tenant['address']) ?></div><?php endif; ?>
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
                    <a :href="`${qrUrl}&size=1500x1500&format=png&download=1`" download="qr-only.png" class="block w-full py-2.5 text-center rounded-xl bg-white/5 hover:bg-white/10 text-sm">Solo QR code 1500×1500 ⬇</a>
                </div>
            </div>

            <div class="card p-5">
                <h3 class="font-bold mb-2">🔗 URL del menu</h3>
                <p class="text-xs text-slate-400 mb-2">Punta al menu sempre aggiornato — il QR non cambia mai.</p>
                <div class="flex gap-2">
                    <input type="text" readonly :value="menuUrl" id="urlInput" class="flex-1 px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-xs font-mono min-w-0" onclick="this.select()">
                    <button @click="copyUrl" class="px-3 py-2 rounded-lg bg-brand-500/20 text-brand-300 text-sm whitespace-nowrap" x-text="copied?'✓':'📋'"></button>
                </div>
                <a :href="menuUrl" target="_blank" class="block mt-3 text-sm text-brand-400">👁 Anteprima menu →</a>
            </div>

            <div class="card p-5">
                <h3 class="font-bold mb-2">📲 Condividi</h3>
                <div class="grid grid-cols-2 gap-2">
                    <a :href="`https://wa.me/?text=${encodeURIComponent('Ecco il nostro menu: ' + menuUrl)}`" target="_blank" class="px-3 py-2 rounded-xl bg-emerald-500/20 text-emerald-400 text-sm font-semibold text-center">WhatsApp</a>
                    <a :href="`mailto:?subject=Menu&body=${encodeURIComponent(menuUrl)}`" class="px-3 py-2 rounded-xl bg-sky-500/20 text-sky-400 text-sm font-semibold text-center">Email</a>
                </div>
            </div>

            <div class="card p-4 text-xs text-slate-400">
                💡 <strong class="text-slate-300">Suggerimento</strong>: stampa il poster A4 ed esponilo all'ingresso, su vetrofania, sui tavoli. Quando aggiorni il menu nel pannello admin, il QR resta lo stesso, niente da ristampare.
            </div>
        </div>
    </div>
</main>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
function qrPoster(){
    return {
        style: 'dark',
        loading: false,
        copied: false,
        menuUrl: <?= json_encode($menuUrl) ?>,
        qrUrl: 'https://api.qrserver.com/v1/create-qr-code/?data=' + encodeURIComponent(<?= json_encode($menuUrl) ?>),
        copyUrl(){
            navigator.clipboard.writeText(this.menuUrl);
            this.copied = true;
            setTimeout(()=>this.copied=false, 1500);
        },
        async downloadPng(){
            this.loading = true;
            await new Promise(r => setTimeout(r, 100));
            try {
                const el = document.getElementById('poster');
                const canvas = await html2canvas(el, {
                    scale: 2,
                    backgroundColor: null,
                    useCORS: true,
                    logging: false,
                });
                const link = document.createElement('a');
                link.download = 'poster-qr-menu-<?= e($tenant['slug'] ?? 'lollapalooza') ?>.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
            } catch(e) {
                alert('Errore generazione: ' + e.message);
            }
            this.loading = false;
        }
    }
}
</script>

<style>
/* Poster: formato verticale 600x900 (~A4 ratio) */
.poster {
    width: 600px;
    aspect-ratio: 2/3;
    max-width: 100%;
    padding: 50px 40px;
    border-radius: 24px;
    display: flex;
    flex-direction: column;
    align-items: center;
    box-shadow: 0 20px 60px -10px rgba(0,0,0,0.3);
    position: relative;
    overflow: hidden;
    font-family: 'Inter', sans-serif;
}

/* Dark — sfondo scuro elegante */
.poster-dark {
    background: linear-gradient(160deg, #1a0a3e 0%, #0a0a13 60%, #1a1a2e 100%);
    color: #f1f5f9;
}
.poster-dark::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at 30% 20%, rgba(139,92,246,0.25), transparent 50%),
                radial-gradient(circle at 70% 80%, rgba(14,165,233,0.15), transparent 50%);
    pointer-events: none;
}
.poster-dark .poster-divider { background: linear-gradient(90deg, transparent, #8b5cf6, transparent); }
.poster-dark .poster-qr-box { background: #fff; padding: 18px; border-radius: 20px; box-shadow: 0 8px 32px rgba(139,92,246,0.4); }
.poster-dark .poster-subtitle { color: #a78bfa; }
.poster-dark .poster-benefit { background: rgba(139,92,246,0.15); border: 1px solid rgba(139,92,246,0.3); color: #e2e8f0; }
.poster-dark .poster-cta-sub { color: #94a3b8; }

/* Light — minimal pulito */
.poster-light {
    background: linear-gradient(160deg, #fafafa 0%, #f5f5f7 100%);
    color: #0f172a;
}
.poster-light::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at 30% 20%, rgba(139,92,246,0.08), transparent 50%);
    pointer-events: none;
}
.poster-light .poster-divider { background: linear-gradient(90deg, transparent, #7c3aed, transparent); }
.poster-light .poster-qr-box { background: #fff; padding: 18px; border-radius: 20px; border: 1px solid rgba(15,23,42,0.08); box-shadow: 0 8px 32px rgba(15,23,42,0.08); }
.poster-light .poster-subtitle { color: #7c3aed; }
.poster-light .poster-benefit { background: #fff; border: 1px solid rgba(15,23,42,0.08); color: #475569; box-shadow: 0 2px 8px rgba(15,23,42,0.04); }
.poster-light .poster-cta-sub { color: #64748b; }

/* Gold — premium */
.poster-gold {
    background: linear-gradient(160deg, #1a1208 0%, #0a0703 60%, #2a1b08 100%);
    color: #fef3c7;
}
.poster-gold::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at 30% 20%, rgba(234,179,8,0.2), transparent 50%),
                radial-gradient(circle at 70% 80%, rgba(217,119,6,0.15), transparent 50%);
    pointer-events: none;
}
.poster-gold .poster-divider { background: linear-gradient(90deg, transparent, #eab308, transparent); }
.poster-gold .poster-qr-box { background: #fff; padding: 18px; border-radius: 20px; box-shadow: 0 8px 32px rgba(234,179,8,0.4); }
.poster-gold .poster-subtitle { color: #fbbf24; letter-spacing: 0.2em; }
.poster-gold .poster-benefit { background: rgba(234,179,8,0.15); border: 1px solid rgba(234,179,8,0.3); color: #fef3c7; }
.poster-gold .poster-cta-sub { color: #d6d3d1; }
.poster-gold .poster-title { background: linear-gradient(180deg, #fef3c7, #eab308); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; }

/* Minimal — bianco totale, zero distrazioni */
.poster-minimal {
    background: #fff;
    color: #18181b;
    border: 1px solid #e4e4e7;
}
.poster-minimal .poster-divider { background: #18181b; height: 1px; }
.poster-minimal .poster-qr-box { background: #fff; padding: 8px; border-radius: 12px; border: 2px solid #18181b; }
.poster-minimal .poster-subtitle { color: #71717a; letter-spacing: 0.3em; }
.poster-minimal .poster-benefit { background: #fafafa; border: 1px solid #e4e4e7; color: #3f3f46; }
.poster-minimal .poster-cta-sub { color: #71717a; }

/* Common */
.poster-header { text-align: center; position: relative; z-index: 1; width: 100%; }
.poster-logo { width: 80px; height: 80px; border-radius: 20px; object-fit: cover; margin: 0 auto 16px; display: block; box-shadow: 0 4px 16px rgba(0,0,0,0.2); }
.poster-title { font-size: 42px; font-weight: 800; letter-spacing: -0.02em; margin: 0; line-height: 1.05; }
.poster-divider { width: 60px; height: 2px; margin: 16px auto; border-radius: 1px; }
.poster-subtitle { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.25em; margin: 0; }
.poster-qr-wrap { flex: 1; display: flex; align-items: center; justify-content: center; padding: 24px 0; position: relative; z-index: 1; width: 100%; }
.poster-qr-box { display: inline-block; }
.poster-qr { display: block; width: 280px; height: 280px; }
.poster-cta { text-align: center; position: relative; z-index: 1; }
.poster-cta-title { font-size: 22px; font-weight: 700; margin-bottom: 4px; letter-spacing: -0.01em; }
.poster-cta-sub { font-size: 13px; font-weight: 500; }
.poster-benefits { display: flex; gap: 8px; margin-top: 24px; flex-wrap: wrap; justify-content: center; position: relative; z-index: 1; }
.poster-benefit { padding: 6px 14px; border-radius: 999px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
.poster-footer { margin-top: 20px; text-align: center; font-size: 11px; opacity: 0.7; position: relative; z-index: 1; display: flex; flex-direction: column; gap: 4px; }

@media (max-width: 700px) {
    .poster { width: 100%; padding: 30px 24px; aspect-ratio: 3/4; }
    .poster-title { font-size: 32px; }
    .poster-qr { width: 220px; height: 220px; }
    .poster-cta-title { font-size: 18px; }
}

@media print {
    aside, header, nav, .print\:hidden { display: none !important; }
    main { margin-left: 0 !important; padding: 0 !important; }
    body, html { background: white !important; }
    .poster { box-shadow: none; page-break-inside: avoid; }
}
</style>
<?php layout_mobile_nav('qrcodes'); layout_foot(); ?>
