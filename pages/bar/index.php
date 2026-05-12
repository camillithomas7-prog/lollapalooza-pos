<?php
require_once __DIR__ . '/../../includes/i18n.php';
layout_head(t('bar'));
?>
<div class="min-h-screen p-4" x-data="kdsBoard('bar')" x-init="load(); setInterval(load,3000); initPrinter()">
    <header class="flex items-center justify-between mb-4 flex-wrap gap-2">
        <div class="flex items-center gap-3">
            <img src="/assets/img/logo.jpeg" class="w-10 h-10 rounded-xl object-cover" alt="">
            <div>
                <div class="text-2xl font-bold">🍸 <?= e(t('bar')) ?></div>
                <div class="text-xs text-slate-400" x-text="`${orders.length} <?= e(t('drinks_in_queue')) ?> · ${new Date().toLocaleTimeString()}`"></div>
            </div>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <div class="flex items-center gap-2 px-3 py-2 rounded-xl bg-slate-100 dark:bg-white/5 text-sm font-medium">
                <span class="w-2.5 h-2.5 rounded-full"
                      :class="printerStatus === 'ready' ? 'bg-emerald-500 animate-pulse' : (printerStatus === 'connecting' ? 'bg-amber-500 animate-pulse' : 'bg-rose-500')"></span>
                <span x-show="printerStatus === 'ready'" class="text-emerald-700 dark:text-emerald-300">🖨️ <span x-text="printerName || '<?= e(t('printer_connected')) ?>'"></span></span>
                <span x-show="printerStatus === 'connecting'" class="text-amber-700 dark:text-amber-300"><?= e(t('printer_connecting')) ?></span>
                <span x-show="printerStatus === 'disconnected' || printerStatus === 'error'" class="text-rose-700 dark:text-rose-300"><?= e(t('printer_off')) ?></span>
                <template x-if="printerStatus !== 'ready'">
                    <div class="flex gap-1">
                        <button @click="connectPrinter('bluetooth')" class="px-2 py-1 rounded-lg bg-sky-500 hover:bg-sky-600 text-white text-xs font-semibold">📶 BT</button>
                        <button @click="connectPrinter('usb')" class="px-2 py-1 rounded-lg bg-sky-500 hover:bg-sky-600 text-white text-xs font-semibold">🔌 USB</button>
                    </div>
                </template>
                <template x-if="printerStatus === 'ready'">
                    <button @click="testPrint()" class="px-2 py-1 rounded-lg bg-slate-200 dark:bg-white/10 text-slate-700 dark:text-slate-200 text-xs font-semibold"><?= e(t('printer_test')) ?></button>
                </template>
            </div>
            <?= lang_switcher(['it','en','ar']) ?>
            <?= theme_switcher() ?>
            <button onclick="document.documentElement.requestFullscreen?.()" class="px-3 py-2 rounded-xl bg-white/5 text-sm">⛶</button>
            <a href="/index.php?p=logout" class="px-3 py-2 rounded-xl bg-white/5 text-sm">⏻</a>
        </div>
    </header>

    <div x-show="printerError" x-cloak class="mb-3 px-4 py-2 rounded-xl bg-rose-500/15 border border-rose-500/40 text-rose-900 dark:text-rose-200 text-sm font-medium">
        ⚠ <span x-text="printerError"></span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-4">
        <template x-for="o in orders" :key="o.order_id">
            <div class="card overflow-hidden" :class="cardAgeClass(o)">
                <div class="p-4 border-b border-white/10" :class="o.done_count === o.total_count ? 'bg-emerald-500/10' : ''">
                    <div class="flex items-start justify-between gap-2 mb-2">
                        <div>
                            <div class="text-xs text-slate-400"><?= e(t('order_table')) ?></div>
                            <div class="text-3xl font-bold leading-none" x-text="o.table_code || o.order_code"></div>
                            <div class="text-xs text-slate-500 mt-1" x-text="(o.waiter_name ? '<?= e(t('waiter')) ?>: '+o.waiter_name : '')"></div>
                        </div>
                        <div class="text-right">
                            <div class="text-xs text-slate-400"><?= e(t('time')) ?></div>
                            <div class="text-2xl font-bold tabular-nums leading-none" x-text="elapsed(o.sent_at)"></div>
                            <div class="text-xs text-slate-500 mt-1" x-text="`${o.done_count}/${o.total_count} <?= e(t('items_ready_count')) ?>`"></div>
                        </div>
                    </div>
                    <div class="h-1.5 bg-slate-200 dark:bg-white/10 rounded-full overflow-hidden">
                        <div class="h-full transition-all"
                             :class="o.done_count === o.total_count ? 'bg-emerald-500' : 'bg-violet-500'"
                             :style="`width:${o.total_count ? (o.done_count/o.total_count*100) : 0}%`"></div>
                    </div>
                </div>

                <div class="divide-y divide-white/5">
                    <template x-for="it in o.items" :key="it.id">
                        <div class="p-3 flex items-start gap-3" :class="['ready','served'].includes(it.status) ? 'opacity-50 line-through' : ''">
                            <button @click="toggleItem(it)"
                                    class="shrink-0 mt-0.5 w-7 h-7 rounded-lg border-2 flex items-center justify-center transition"
                                    :class="['ready','served'].includes(it.status)
                                              ? 'bg-emerald-500 border-emerald-500 text-white'
                                              : (it.status==='preparing' ? 'bg-amber-500/20 border-amber-500 text-amber-700 dark:text-amber-300' : 'border-slate-300 dark:border-white/20 hover:border-emerald-500')">
                                <span x-show="['ready','served'].includes(it.status)">✓</span>
                                <span x-show="it.status==='preparing'" class="text-xs">⋯</span>
                            </button>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-baseline gap-2">
                                    <span class="text-2xl font-bold text-violet-500 tabular-nums" x-text="parseFloat(it.qty).toFixed(0)+'×'"></span>
                                    <span class="text-base sm:text-lg font-semibold flex-1" x-text="it.name"></span>
                                </div>
                                <div x-show="it.notes" class="mt-1 px-2 py-1 rounded-lg bg-amber-500/15 text-amber-800 dark:text-amber-200 text-xs">
                                    ⚠ <span x-text="it.notes"></span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="p-3 flex items-center gap-2 bg-slate-50/50 dark:bg-white/5 border-t border-white/10">
                    <button @click="reprintTicket(o.order_id)" class="px-3 py-2 rounded-lg bg-white/10 hover:bg-white/20 text-sm" :title="'<?= e(t('reprint')) ?>'">🖨️</button>
                    <button @click="allReady(o)" x-show="o.done_count < o.total_count"
                            class="flex-1 py-3 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-sm">
                        ✓ <?= e(t('all_ready')) ?>
                    </button>
                    <div x-show="o.done_count === o.total_count && o.total_count > 0"
                         class="flex-1 text-center py-3 text-emerald-600 dark:text-emerald-400 font-bold">
                        ✓ <?= e(t('all_ready')) ?>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <div x-show="!orders.length" class="text-center py-32 text-slate-400 text-xl">
        <div class="text-6xl mb-4">🍹</div>
        <div><?= e(t('no_drinks_bar')) ?></div>
    </div>
</div>

<script src="/assets/js/escpos-printer.js"></script>
<script>
const LANG = {
    print_error: <?= json_encode(t('print_error')) ?>,
    connect_error: <?= json_encode(t('connect_error')) ?>,
    test_error: <?= json_encode(t('test_error')) ?>,
    reprint_error: <?= json_encode(t('reprint_error')) ?>,
};
function kdsBoard(dest){return {
    orders: [], lastItemsCount: 0, dest,
    printerStatus: 'disconnected', printerName: '', printerError: '',
    printedIds: new Set(), printPollTimer: null,

    async load(){
        try {
            const r = await fetch('/api/orders.php?action=kitchen_queue&dest='+this.dest+'&lang=<?= e(current_lang()) ?>');
            const d = await r.json();
            const newOrders = d.orders || [];
            const newCount = newOrders.reduce((s,o) => s + o.items.filter(i => !['ready','served'].includes(i.status)).length, 0);
            if (newCount > this.lastItemsCount && this.lastItemsCount > 0 && navigator.vibrate) navigator.vibrate(200);
            this.orders = newOrders;
            this.lastItemsCount = newCount;
        } catch(e){}
    },
    async toggleItem(it){
        const next = it.status === 'sent' ? 'preparing' : it.status === 'preparing' ? 'ready' : 'sent';
        it.status = next;
        try {
            await fetch('/api/orders.php?action=item_status',{method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({id:it.id,status:next})});
        } catch(e){}
        this.load();
    },
    async allReady(o){
        try {
            await fetch('/api/orders.php?action=order_all_ready',{method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({order_id:o.order_id, dest:this.dest})});
        } catch(e){}
        this.load();
    },
    elapsed(d){if(!d)return'—';const m=Math.floor((Date.now()-new Date(d).getTime())/60000);const s=Math.floor(((Date.now()-new Date(d).getTime())/1000)%60);return m+':'+String(s).padStart(2,'0');},
    cardAgeClass(o){
        if (o.done_count === o.total_count && o.total_count > 0) return 'border-emerald-500/50 bg-emerald-500/5';
        if (!o.sent_at) return '';
        const m = (Date.now()-new Date(o.sent_at).getTime())/60000;
        if (m > 10) return 'border-rose-500/60 bg-rose-500/10';
        if (m > 5) return 'border-amber-500/60 bg-amber-500/10';
        return '';
    },

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
        } catch (e) { this.printerError = e.message || LANG.connect_error; }
    },
    async testPrint() {
        try { await fetch('/api/print_jobs.php?action=test&dest=' + this.dest, {method: 'POST'}); }
        catch(e) { this.printerError = LANG.test_error + ': ' + e.message; }
    },
    async reprintTicket(orderId) {
        try {
            await fetch('/api/print_jobs.php?action=reprint', {method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({order_id: orderId, dest: this.dest})});
        } catch(e) { this.printerError = LANG.reprint_error + ': ' + e.message; }
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
                        this.printerError = LANG.print_error + ': ' + (e.message || '');
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
