<?php layout_head('Dashboard'); layout_sidebar('dashboard'); layout_topbar('Dashboard', 'Panoramica live del tuo locale'); ?>
<main class="md:ml-64 lg:ml-72 p-4 md:p-8 pb-24 md:pb-8" x-data="dashboard()" x-init="load(); setInterval(load, 10000)">
    <!-- KPI -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4 mb-6">
        <div class="card p-5">
            <div class="text-xs text-slate-400 uppercase tracking-wider mb-1">Incassi oggi</div>
            <div class="text-lg md:text-3xl font-bold break-words text-emerald-400" x-text="fmt(s.revenue_today)"></div>
            <div class="text-xs text-slate-500 mt-1" x-text="`${s.orders_today||0} ordini`"></div>
        </div>
        <div class="card p-5">
            <div class="text-xs text-slate-400 uppercase tracking-wider mb-1">Utile oggi</div>
            <div class="text-lg md:text-3xl font-bold break-words" :class="(s.profit_today||0) >= 0 ? 'text-sky-400' : 'text-rose-400'" x-text="fmt(s.profit_today)"></div>
            <div class="text-xs text-slate-500 mt-1" x-text="`Costi: ${fmt((s.cogs_today||0)+(s.expenses_today||0))}`"></div>
        </div>
        <div class="card p-5">
            <div class="text-xs text-slate-400 uppercase tracking-wider mb-1">Tavoli occupati</div>
            <div class="text-lg md:text-3xl font-bold break-words text-amber-400" x-text="`${s.tables?.occupied||0}/${s.tables?.total||0}`"></div>
            <div class="text-xs text-slate-500 mt-1" x-text="`${Math.round(((s.tables?.occupied||0)/(s.tables?.total||1))*100)}% occupazione`"></div>
        </div>
        <div class="card p-5">
            <div class="text-xs text-slate-400 uppercase tracking-wider mb-1">Ordini attivi</div>
            <div class="text-lg md:text-3xl font-bold break-words text-brand-400" x-text="s.active_orders||0"></div>
            <div class="text-xs text-slate-500 mt-1">In corso ora</div>
        </div>
    </div>

    <!-- Grafici -->
    <div class="grid lg:grid-cols-3 gap-4 mb-6">
        <div class="card p-5 lg:col-span-2">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold">📈 Andamento ricavi (7 giorni)</h3>
            </div>
            <canvas id="chartRevenue" height="100"></canvas>
        </div>
        <div class="card p-5">
            <h3 class="font-bold mb-4">🏆 Top prodotti oggi</h3>
            <div class="space-y-2">
                <template x-for="(p,i) in s.top_products||[]" :key="i">
                    <div class="flex items-center justify-between gap-2 py-2 border-b border-white/5 last:border-0">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="w-6 h-6 rounded-full bg-brand-500/20 text-brand-400 text-xs flex items-center justify-center font-bold" x-text="i+1"></span>
                            <span class="text-sm truncate" x-text="p.name"></span>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <div class="text-sm font-semibold" x-text="`${parseFloat(p.qty).toFixed(0)}x`"></div>
                            <div class="text-xs text-emerald-400" x-text="fmt(p.revenue)"></div>
                        </div>
                    </div>
                </template>
                <div x-show="!(s.top_products||[]).length" class="text-center text-slate-500 py-8 text-sm">Nessun dato</div>
            </div>
        </div>
    </div>

    <!-- Recent orders -->
    <div class="card p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold">🧾 Ultimi ordini</h3>
            <a href="/index.php?p=orders" class="text-xs text-brand-400 hover:text-brand-300">Vedi tutti →</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-xs text-slate-400 uppercase">
                    <tr><th class="text-left py-2 px-2">Codice</th><th class="text-left py-2 px-2">Tavolo</th><th class="text-left py-2 px-2">Stato</th><th class="text-left py-2 px-2">Ora</th><th class="text-right py-2 px-2">Totale</th></tr>
                </thead>
                <tbody>
                    <template x-for="o in s.recent_orders||[]" :key="o.id">
                        <tr class="border-t border-white/5 hover:bg-white/5">
                            <td class="py-2 px-2 font-mono text-xs" x-text="o.code"></td>
                            <td class="py-2 px-2" x-text="o.table_code || (o.type==='takeaway'?'Asporto':o.type)"></td>
                            <td class="py-2 px-2"><span class="text-xs px-2 py-1 rounded-md" :class="badgeStatus(o.status)" x-text="statusLabel(o.status)"></span></td>
                            <td class="py-2 px-2 text-slate-400 text-xs" x-text="formatTime(o.created_at)"></td>
                            <td class="py-2 px-2 text-right font-semibold" x-text="fmt(o.total)"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script>
function dashboard() {
    return {
        s: {},
        chart: null,
        async load() {
            const r = await fetch('/api/stats.php?action=dashboard');
            this.s = await r.json();
            this.renderChart();
        },
        renderChart() {
            const ctx = document.getElementById('chartRevenue');
            if (!ctx || !(this.s.history_7d||[]).length) return;
            if (this.chart) this.chart.destroy();
            this.chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: this.s.history_7d.map(x=>x.label),
                    datasets: [{
                        label: 'Ricavi',
                        data: this.s.history_7d.map(x=>x.revenue),
                        borderColor: '#8b5cf6',
                        backgroundColor: 'rgba(139,92,246,0.1)',
                        fill: true, tension: 0.4, borderWidth: 2,
                        pointBackgroundColor: '#8b5cf6', pointRadius: 4
                    }]
                },
                options: { responsive: true, plugins: { legend: { display: false } },
                    scales: { y: { ticks: { color: '#64748b' }, grid: { color: 'rgba(255,255,255,0.05)' } },
                              x: { ticks: { color: '#64748b' }, grid: { display: false } } }
                }
            });
        },
        fmt(v) { return '€ ' + (parseFloat(v||0)).toLocaleString('it-IT', {minimumFractionDigits:2,maximumFractionDigits:2}); },
        formatTime(s) { try { return new Date(s).toLocaleTimeString('it-IT',{hour:'2-digit',minute:'2-digit'}); } catch(e){ return ''; } },
        badgeStatus(s) {
            return {open:'bg-slate-500/20 text-slate-300',sent:'bg-amber-500/20 text-amber-300',preparing:'bg-amber-500/20 text-amber-300',ready:'bg-sky-500/20 text-sky-300',served:'bg-emerald-500/20 text-emerald-300',closed:'bg-emerald-500/20 text-emerald-300',cancelled:'bg-rose-500/20 text-rose-300'}[s]||'bg-slate-500/20';
        },
        statusLabel(s){return {open:'Aperto',sent:'Inviato',preparing:'In prep.',ready:'Pronto',served:'Servito',closed:'Chiuso',cancelled:'Annullato'}[s]||s;}
    }
}
</script>
<?php layout_mobile_nav('dashboard'); layout_foot(); ?>
