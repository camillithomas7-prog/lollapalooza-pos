<?php layout_head('Bilancio'); layout_sidebar('finance'); layout_topbar('Bilancio', 'Entrate, uscite e utile'); ?>
<main class="md:ml-64 lg:ml-72 p-4 md:p-8 pb-24 md:pb-8" x-data="finPage()" x-init="load()">
    <div class="flex flex-wrap gap-2 mb-4 items-center">
        <input type="date" x-model="from" @change="load" class="px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-sm">
        <span class="text-slate-400">→</span>
        <input type="date" x-model="to" @change="load" class="px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-sm">
        <button @click="setRange('today')" class="px-3 py-2 rounded-lg bg-white/5 text-xs">Oggi</button>
        <button @click="setRange('week')" class="px-3 py-2 rounded-lg bg-white/5 text-xs">Settimana</button>
        <button @click="setRange('month')" class="px-3 py-2 rounded-lg bg-white/5 text-xs">Mese</button>
        <button @click="addExp" class="ml-auto px-4 py-2 rounded-lg btn-primary text-sm font-semibold">+ Spesa</button>
    </div>

    <div class="grid md:grid-cols-4 gap-3 mb-4">
        <div class="card p-4"><div class="text-xs text-slate-400">Entrate</div><div class="text-2xl font-bold text-emerald-400" x-text="fmt(stats.totals?.revenue)"></div></div>
        <div class="card p-4"><div class="text-xs text-slate-400">Costo merce</div><div class="text-2xl font-bold text-amber-400" x-text="fmt(stats.totals?.cogs)"></div></div>
        <div class="card p-4"><div class="text-xs text-slate-400">Spese</div><div class="text-2xl font-bold text-rose-400" x-text="fmt(stats.totals?.expenses)"></div></div>
        <div class="card p-4"><div class="text-xs text-slate-400">Utile netto</div><div class="text-2xl font-bold" :class="(stats.totals?.profit||0)>=0?'text-sky-400':'text-rose-400'" x-text="fmt(stats.totals?.profit)"></div></div>
    </div>

    <div class="grid lg:grid-cols-2 gap-4 mb-4">
        <div class="card p-5"><h3 class="font-bold mb-3">📈 Cashflow</h3><canvas id="chartCash" height="100"></canvas></div>
        <div class="card p-5"><h3 class="font-bold mb-3">💳 Pagamenti per metodo</h3><canvas id="chartPay" height="100"></canvas></div>
    </div>

    <div class="grid lg:grid-cols-2 gap-4">
        <div class="card p-5">
            <h3 class="font-bold mb-3">Spese per categoria</h3>
            <div class="space-y-2">
                <template x-for="c in stats.expenses_by_cat||[]" :key="c.category">
                    <div class="flex justify-between items-center py-2 border-b border-white/5">
                        <span x-text="c.category"></span>
                        <span class="font-bold text-rose-400" x-text="fmt(c.tot)"></span>
                    </div>
                </template>
            </div>
        </div>
        <div class="card p-5">
            <h3 class="font-bold mb-3">Spese (lista)</h3>
            <div class="space-y-1 max-h-96 overflow-y-auto scrollbar-thin">
                <template x-for="e in expenses" :key="e.id">
                    <div class="flex items-center gap-2 py-2 border-b border-white/5 text-sm">
                        <div class="flex-1">
                            <div class="font-semibold" x-text="e.category"></div>
                            <div class="text-xs text-slate-400" x-text="e.description+' · '+e.date"></div>
                        </div>
                        <div class="font-bold text-rose-400" x-text="fmt(e.amount)"></div>
                        <button @click="delExp(e)" class="text-slate-500 hover:text-rose-400">🗑</button>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Modal spesa -->
    <div x-show="expModal" x-cloak @click.self="expModal=null" class="fixed inset-0 z-50 bg-black/70 flex items-center justify-center p-4">
        <div class="card p-6 w-full max-w-md">
            <h3 class="text-lg font-bold mb-4">Nuova spesa</h3>
            <div class="space-y-2">
                <select x-model="expModal.category" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10">
                    <option>Merce</option><option>Stipendi</option><option>Bollette</option><option>Affitto</option><option>Manutenzione</option><option>Tasse</option><option>Marketing</option><option>Altro</option>
                </select>
                <input type="number" step="0.01" x-model.number="expModal.amount" placeholder="Importo LE" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10">
                <input x-model="expModal.description" placeholder="Descrizione" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10">
                <input type="date" x-model="expModal.date" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10">
                <div class="flex gap-2 mt-3">
                    <button @click="saveExp" class="flex-1 btn-primary py-2.5 rounded-lg font-semibold">Salva</button>
                    <button @click="expModal=null" class="px-4 py-2.5 rounded-lg bg-white/5">Annulla</button>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
function finPage(){return {
    from: new Date(new Date().setDate(1)).toISOString().slice(0,10), to: new Date().toISOString().slice(0,10),
    stats:{}, expenses:[], expModal:null, chartCash:null, chartPay:null,
    async load(){
        const s=await fetch(`/api/stats.php?action=finance&from=${this.from}&to=${this.to}`);this.stats=await s.json();
        const e=await fetch(`/api/finance.php?action=expenses&from=${this.from}&to=${this.to}`);this.expenses=(await e.json()).expenses;
        this.renderCharts();
    },
    setRange(r){const t=new Date();if(r==='today'){this.from=this.to=t.toISOString().slice(0,10);}else if(r==='week'){const w=new Date(t);w.setDate(t.getDate()-7);this.from=w.toISOString().slice(0,10);this.to=t.toISOString().slice(0,10);}else{this.from=new Date(t.getFullYear(),t.getMonth(),1).toISOString().slice(0,10);this.to=t.toISOString().slice(0,10);}this.load();},
    addExp(){this.expModal={category:'Merce',amount:0,description:'',date:new Date().toISOString().slice(0,10)};},
    async saveExp(){await fetch('/api/finance.php?action=save_expense',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(this.expModal)});this.expModal=null;this.load();},
    async delExp(e){if(!confirm('Eliminare?'))return;await fetch('/api/finance.php?action=delete_expense',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:e.id})});this.load();},
    fmt(v){return 'LE '+(parseFloat(v||0)).toLocaleString('it-IT',{minimumFractionDigits:2,maximumFractionDigits:2});},
    renderCharts(){
        if(this.chartCash)this.chartCash.destroy();
        const dates=[...new Set([...(this.stats.revenue||[]).map(x=>x.d),...(this.stats.expenses||[]).map(x=>x.d)])].sort();
        const rev=dates.map(d=>(this.stats.revenue||[]).find(x=>x.d===d)?.tot||0);
        const exp=dates.map(d=>(this.stats.expenses||[]).find(x=>x.d===d)?.tot||0);
        this.chartCash=new Chart(document.getElementById('chartCash'),{type:'bar',data:{labels:dates,datasets:[{label:'Entrate',data:rev,backgroundColor:'rgba(16,185,129,0.6)'},{label:'Uscite',data:exp,backgroundColor:'rgba(239,68,68,0.6)'}]},options:{responsive:true,scales:{y:{ticks:{color:'#94a3b8'}},x:{ticks:{color:'#94a3b8'}}},plugins:{legend:{labels:{color:'#94a3b8'}}}}});
        if(this.chartPay)this.chartPay.destroy();
        const pm=this.stats.payments_by_method||[];
        this.chartPay=new Chart(document.getElementById('chartPay'),{type:'doughnut',data:{labels:pm.map(x=>x.method),datasets:[{data:pm.map(x=>x.tot),backgroundColor:['#10b981','#0ea5e9','#8b5cf6','#f59e0b','#ef4444']}]},options:{responsive:true,plugins:{legend:{labels:{color:'#94a3b8'}}}}});
    }
}}
</script>
<?php layout_mobile_nav('finance'); layout_foot(); ?>
