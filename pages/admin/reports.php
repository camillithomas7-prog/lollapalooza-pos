<?php layout_head('Report'); layout_sidebar('reports'); layout_topbar('Report', 'Analytics e performance'); ?>
<main class="md:ml-64 lg:ml-72 p-4 md:p-8 pb-24 md:pb-8" x-data="repPage()" x-init="load()">
    <div class="flex flex-wrap gap-2 mb-4 items-center">
        <input type="date" x-model="from" @change="load" class="px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-sm">
        <span>→</span>
        <input type="date" x-model="to" @change="load" class="px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-sm">
        <button @click="exportCSV" class="ml-auto px-4 py-2 rounded-lg bg-emerald-500/20 text-emerald-300 text-sm">📊 Esporta CSV</button>
    </div>

    <div class="grid lg:grid-cols-2 gap-4 mb-4">
        <div class="card p-5">
            <h3 class="font-bold mb-3">⏰ Vendite per ora</h3>
            <canvas id="chartHour" height="120"></canvas>
        </div>
        <div class="card p-5">
            <h3 class="font-bold mb-3">👥 Performance camerieri</h3>
            <div class="space-y-2">
                <template x-for="w in stats.waiters||[]" :key="w.name">
                    <div class="flex justify-between items-center py-2 border-b border-white/5">
                        <div>
                            <div class="font-semibold" x-text="w.name"></div>
                            <div class="text-xs text-slate-400" x-text="(w.orders_count||0)+' ordini'"></div>
                        </div>
                        <div class="font-bold text-emerald-400" x-text="'€'+parseFloat(w.revenue||0).toFixed(2)"></div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-4">
        <div class="card p-5">
            <h3 class="font-bold mb-3">🏆 Top prodotti</h3>
            <div class="overflow-x-auto -mx-5 px-5 scrollbar-thin">
            <table class="w-full text-sm min-w-[480px]">
                <thead class="text-xs uppercase text-slate-400">
                    <tr><th class="text-left p-2">Prodotto</th><th class="text-right p-2">Q.tà</th><th class="text-right p-2">Ricavi</th><th class="text-right p-2">Margine</th></tr>
                </thead>
                <tbody>
                    <template x-for="p in stats.top_products||[]" :key="p.name">
                        <tr class="border-t border-white/5">
                            <td class="p-2" x-text="p.name"></td>
                            <td class="p-2 text-right tabular-nums" x-text="parseFloat(p.qty).toFixed(0)"></td>
                            <td class="p-2 text-right text-emerald-400 font-semibold tabular-nums whitespace-nowrap" x-text="'€'+parseFloat(p.revenue||0).toFixed(2)"></td>
                            <td class="p-2 text-right text-sky-400 tabular-nums whitespace-nowrap" x-text="'€'+parseFloat(p.margin||0).toFixed(2)"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
            </div>
        </div>
        <div class="card p-5">
            <h3 class="font-bold mb-3">🪑 Tavoli più redditizi</h3>
            <div class="overflow-x-auto -mx-5 px-5 scrollbar-thin">
            <table class="w-full text-sm min-w-[360px]">
                <thead class="text-xs uppercase text-slate-400"><tr><th class="text-left p-2">Tavolo</th><th class="text-right p-2">Ordini</th><th class="text-right p-2">Ricavi</th></tr></thead>
                <tbody>
                    <template x-for="t in stats.tables||[]" :key="t.code">
                        <tr class="border-t border-white/5">
                            <td class="p-2 font-bold whitespace-nowrap" x-text="t.code"></td>
                            <td class="p-2 text-right tabular-nums" x-text="t.orders_count||0"></td>
                            <td class="p-2 text-right text-emerald-400 font-semibold tabular-nums whitespace-nowrap" x-text="'€'+parseFloat(t.revenue||0).toFixed(2)"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
            </div>
        </div>
    </div>
</main>
<script>
function repPage(){return {
    from:new Date(new Date().setDate(1)).toISOString().slice(0,10),to:new Date().toISOString().slice(0,10),stats:{},chartHour:null,
    async load(){const r=await fetch(`/api/stats.php?action=reports&from=${this.from}&to=${this.to}`);this.stats=await r.json();this.renderChart();},
    renderChart(){if(this.chartHour)this.chartHour.destroy();const h=this.stats.by_hour||[];this.chartHour=new Chart(document.getElementById('chartHour'),{type:'bar',data:{labels:h.map(x=>x.h+':00'),datasets:[{label:'Ricavi',data:h.map(x=>x.tot),backgroundColor:'rgba(139,92,246,0.6)'}]},options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{ticks:{color:'#94a3b8'}},x:{ticks:{color:'#94a3b8'}}}}});},
    exportCSV(){const rows=[['Prodotto','Q.tà','Ricavi','Costo','Margine']];(this.stats.top_products||[]).forEach(p=>rows.push([p.name,p.qty,p.revenue,p.cost,p.margin]));const csv=rows.map(r=>r.join(';')).join('\n');const b=new Blob([csv],{type:'text/csv'});const u=URL.createObjectURL(b);const a=document.createElement('a');a.href=u;a.download=`report-${this.from}_${this.to}.csv`;a.click();}
}}
</script>
<?php layout_mobile_nav('reports'); layout_foot(); ?>
