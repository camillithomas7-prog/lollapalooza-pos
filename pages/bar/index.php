<?php layout_head('Bar'); ?>
<div class="min-h-screen p-4" x-data="kdsBoard('bar')" x-init="load(); setInterval(load,3000); initPrinter()">
    <header class="flex items-center justify-between mb-4 flex-wrap gap-2">
        <div class="flex items-center gap-3">
            <img src="/assets/img/logo.jpeg" class="w-10 h-10 rounded-xl object-cover" alt="">
            <div>
                <div class="text-2xl font-bold">🍸 Bar</div>
                <div class="text-xs text-slate-400" x-text="`${items.length} drink in coda · ${new Date().toLocaleTimeString('it-IT')}`"></div>
            </div>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <div class="flex items-center gap-2 px-3 py-2 rounded-xl bg-white/5 text-sm">
                <span class="w-2.5 h-2.5 rounded-full"
                      :class="printerStatus === 'ready' ? 'bg-emerald-400 animate-pulse' : (printerStatus === 'connecting' ? 'bg-amber-400 animate-pulse' : 'bg-rose-500')"></span>
                <span x-show="printerStatus === 'ready'" class="text-emerald-300">🖨️ <span x-text="printerName || 'connessa'"></span></span>
                <span x-show="printerStatus === 'connecting'" class="text-amber-300">Collegamento…</span>
                <span x-show="printerStatus === 'disconnected' || printerStatus === 'error'" class="text-rose-300">Stampante off</span>
                <template x-if="printerStatus !== 'ready'">
                    <div class="flex gap-1">
                        <button @click="connectPrinter('bluetooth')" class="px-2 py-1 rounded-lg bg-sky-500/20 text-sky-300 text-xs">📶 BT</button>
                        <button @click="connectPrinter('usb')" class="px-2 py-1 rounded-lg bg-sky-500/20 text-sky-300 text-xs">🔌 USB</button>
                    </div>
                </template>
                <template x-if="printerStatus === 'ready'">
                    <button @click="testPrint()" class="px-2 py-1 rounded-lg bg-white/10 text-xs">Test</button>
                </template>
            </div>
            <?= theme_switcher() ?>
            <button onclick="document.documentElement.requestFullscreen?.()" class="px-3 py-2 rounded-xl bg-white/5 text-sm">⛶</button>
            <a href="/index.php?p=logout" class="px-3 py-2 rounded-xl bg-white/5 text-sm">⏻</a>
        </div>
    </header>

    <div x-show="printerError" x-cloak class="mb-3 px-4 py-2 rounded-xl bg-rose-500/15 border border-rose-500/30 text-rose-200 text-sm">
        ⚠ <span x-text="printerError"></span>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        <template x-for="i in items" :key="i.id">
            <div class="card p-4" :class="ageClass(i.sent_at)">
                <div class="flex items-start justify-between mb-2">
                    <div>
                        <div class="text-xs text-slate-400">Tavolo</div>
                        <div class="text-2xl font-bold" x-text="i.table_code"></div>
                    </div>
                    <div class="text-right">
                        <div class="text-xs text-slate-400">Tempo</div>
                        <div class="text-lg font-bold tabular-nums" x-text="elapsed(i.sent_at)"></div>
                    </div>
                </div>
                <div class="border-t border-white/10 pt-3 mb-3">
                    <div class="flex items-baseline gap-2">
                        <span class="text-3xl font-bold text-violet-400" x-text="parseFloat(i.qty).toFixed(0)+'x'"></span>
                        <span class="text-xl font-semibold flex-1" x-text="i.name"></span>
                    </div>
                    <div x-show="i.notes" class="mt-2 p-2 rounded-lg bg-amber-500/10 text-amber-300 text-sm">
                        <span class="font-bold">⚠</span> <span x-text="i.notes"></span>
                    </div>
                </div>
                <div class="flex items-center justify-end mb-2">
                    <button @click="reprintTicket(i.order_id)" class="text-xs px-2 py-1 rounded-lg bg-white/5 hover:bg-white/10" title="Ristampa">🖨️</button>
                </div>
                <div class="flex gap-2">
                    <button x-show="i.status==='sent'" @click="setStatus(i,'preparing')" class="flex-1 py-3 rounded-xl bg-orange-500 text-white font-bold text-sm">▶ In prep.</button>
                    <button x-show="i.status==='preparing'" @click="setStatus(i,'ready')" class="flex-1 py-3 rounded-xl bg-emerald-500 text-white font-bold text-sm">✓ PRONTO</button>
                </div>
            </div>
        </template>
    </div>

    <div x-show="!items.length" class="text-center py-32 text-slate-400 text-xl">
        <div class="text-6xl mb-4">🍹</div>
        <div>Nessun drink in coda</div>
    </div>
</div>

<script src="/assets/js/escpos-printer.js"></script>
<script>
function kdsBoard(dest){return {
    items: [], lastCount: 0, dest,
    printerStatus: 'disconnected', printerName: '', printerError: '',
    printedIds: new Set(), printPollTimer: null,

    async load(){
        const r = await fetch('/api/orders.php?action=kitchen_queue&dest='+this.dest);
        const d = await r.json();
        if ((d.items||[]).length > this.lastCount && this.lastCount > 0 && navigator.vibrate) navigator.vibrate(200);
        this.items = d.items||[]; this.lastCount = this.items.length;
    },
    async setStatus(i, s){
        await fetch('/api/orders.php?action=item_status',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:i.id,status:s})});
        this.load();
    },
    elapsed(d){if(!d)return'—';const m=Math.floor((Date.now()-new Date(d).getTime())/60000);const s=Math.floor(((Date.now()-new Date(d).getTime())/1000)%60);return m+':'+String(s).padStart(2,'0');},
    ageClass(d){if(!d)return'';const m=(Date.now()-new Date(d).getTime())/60000;if(m>10)return'border-rose-500/50 bg-rose-500/5';if(m>5)return'border-amber-500/50 bg-amber-500/5';return'';},

    async initPrinter() {
        if (!window.EscPosPrinter) return;
        EscPosPrinter.on('change', (st) => {
            this.printerStatus = st.status;
            this.printerName = EscPosPrinter.getDeviceName() || '';
            this.printerError = (st.status === 'error' || st.status === 'disconnected') ? (st.lastError || '') : '';
        });
        try { await EscPosPrinter.reconnect(); } catch(e){}
        this.startPrintPolling();
    },
    async connectPrinter(method) {
        this.printerError = '';
        try {
            if (method === 'bluetooth') await EscPosPrinter.connectBluetooth();
            else                        await EscPosPrinter.connectUSB();
        } catch (e) { this.printerError = e.message || 'Errore connessione'; }
    },
    async testPrint() {
        try { await fetch('/api/print_jobs.php?action=test&dest=' + this.dest, {method: 'POST'}); }
        catch(e) { this.printerError = 'Errore test: ' + e.message; }
    },
    async reprintTicket(orderId) {
        try {
            await fetch('/api/print_jobs.php?action=reprint', {
                method: 'POST', headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({order_id: orderId, dest: this.dest})
            });
        } catch(e) { this.printerError = 'Errore ristampa: ' + e.message; }
    },
    startPrintPolling() {
        if (this.printPollTimer) clearInterval(this.printPollTimer);
        const tick = async () => {
            if (!EscPosPrinter.isConnected()) return;
            try {
                const r = await fetch('/api/print_jobs.php?action=pending&dest=' + this.dest);
                const data = await r.json();
                for (const job of (data.jobs || [])) {
                    if (this.printedIds.has(job.id)) continue;
                    this.printedIds.add(job.id);
                    try {
                        await EscPosPrinter.sendBytes(job.payload);
                        await fetch('/api/print_jobs.php?action=ack', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({id: job.id})});
                    } catch (e) {
                        this.printedIds.delete(job.id);
                        await fetch('/api/print_jobs.php?action=fail', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({id: job.id, error: e.message})});
                        this.printerError = 'Errore stampa: ' + (e.message || 'sconosciuto');
                    }
                }
            } catch(e){}
        };
        this.printPollTimer = setInterval(tick, 2000);
        tick();
    },
}}
</script>
<?php layout_foot(); ?>
