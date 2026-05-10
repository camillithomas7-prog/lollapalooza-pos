<?php layout_head('Cucina'); ?>
<div class="min-h-screen p-4" x-data="kdsBoard('kitchen')" x-init="load(); setInterval(load,3000)">
    <header class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-3">
            <img src="/assets/img/logo.jpeg" class="w-10 h-10 rounded-xl object-cover" alt="">
            <div>
                <div class="text-2xl font-bold">👨‍🍳 Cucina</div>
                <div class="text-xs text-slate-400" x-text="`${items.length} ordini in coda · ${new Date().toLocaleTimeString('it-IT')}`"></div>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <?= theme_switcher() ?>
            <button onclick="document.documentElement.requestFullscreen?.()" class="px-3 py-2 rounded-xl bg-white/5 text-sm">⛶ Fullscreen</button>
            <a href="/index.php?p=logout" class="px-3 py-2 rounded-xl bg-white/5 text-sm">⏻</a>
        </div>
    </header>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        <template x-for="i in items" :key="i.id">
            <div class="card p-4" :class="ageClass(i.sent_at)">
                <div class="flex items-start justify-between mb-2">
                    <div>
                        <div class="text-xs text-slate-400">Tavolo</div>
                        <div class="text-2xl font-bold" x-text="i.table_code || i.order_code"></div>
                    </div>
                    <div class="text-right">
                        <div class="text-xs text-slate-400">Tempo</div>
                        <div class="text-lg font-bold tabular-nums" x-text="elapsed(i.sent_at)"></div>
                    </div>
                </div>
                <div class="border-t border-white/10 pt-3 mb-3">
                    <div class="flex items-baseline gap-2">
                        <span class="text-3xl font-bold text-amber-400" x-text="parseFloat(i.qty).toFixed(0)+'x'"></span>
                        <span class="text-xl font-semibold flex-1" x-text="i.name"></span>
                    </div>
                    <div x-show="i.notes" class="mt-2 p-2 rounded-lg bg-amber-500/10 text-amber-300 text-sm">
                        <span class="font-bold">⚠ Note:</span> <span x-text="i.notes"></span>
                    </div>
                </div>
                <div class="text-xs text-slate-400 mb-3" x-text="`Cameriere: ${i.waiter_name||'—'}`"></div>
                <div class="flex gap-2">
                    <button x-show="i.status==='sent'" @click="setStatus(i,'preparing')" class="flex-1 py-3 rounded-xl bg-orange-500 text-white font-bold text-sm">▶ In preparazione</button>
                    <button x-show="i.status==='preparing'" @click="setStatus(i,'ready')" class="flex-1 py-3 rounded-xl bg-emerald-500 text-white font-bold text-sm">✓ PRONTO</button>
                </div>
            </div>
        </template>
    </div>

    <div x-show="!items.length" class="text-center py-32 text-slate-400 text-xl">
        <div class="text-6xl mb-4">🍽️</div>
        <div>Nessun ordine in cucina</div>
    </div>
</div>

<audio id="bell" preload="auto" src="data:audio/mp3;base64,SUQzAwAAAAAAEUVOR0FBQUFBQUFBQUFBQUFBQUFB"></audio>

<script>
function kdsBoard(dest){return {
    items: [], lastCount: 0, dest,
    async load(){
        const r = await fetch('/api/orders.php?action=kitchen_queue&dest='+this.dest);
        const d = await r.json();
        if ((d.items||[]).length > this.lastCount && this.lastCount > 0) {
            try { document.getElementById('bell')?.play(); } catch(e){}
            if (navigator.vibrate) navigator.vibrate(200);
        }
        this.items = d.items||[]; this.lastCount = this.items.length;
    },
    async setStatus(i, s){
        await fetch('/api/orders.php?action=item_status',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:i.id,status:s})});
        this.load();
    },
    elapsed(d){
        if (!d) return '—';
        const m = Math.floor((Date.now()-new Date(d).getTime())/60000);
        const s = Math.floor(((Date.now()-new Date(d).getTime())/1000)%60);
        return m+':'+String(s).padStart(2,'0');
    },
    ageClass(d){
        if (!d) return '';
        const m = (Date.now()-new Date(d).getTime())/60000;
        if (m > 20) return 'border-rose-500/50 bg-rose-500/5';
        if (m > 10) return 'border-amber-500/50 bg-amber-500/5';
        return '';
    }
}}
</script>
<?php layout_foot(); ?>
