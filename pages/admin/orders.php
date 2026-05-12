<?php layout_head('Ordini'); layout_sidebar('orders'); layout_topbar('Ordini', 'Tutti gli ordini'); ?>
<main class="md:ml-64 lg:ml-72 p-4 md:p-8 pb-24 md:pb-8" x-data="ordersList()" x-init="load(); setInterval(load,8000)">
    <div class="flex flex-wrap gap-2 mb-4">
        <button @click="filter='active'; load()" :class="filter==='active'?'btn-primary':'bg-white/5'" class="px-4 py-2 rounded-xl text-sm font-medium">Attivi</button>
        <button @click="filter='today'; load()" :class="filter==='today'?'btn-primary':'bg-white/5'" class="px-4 py-2 rounded-xl text-sm font-medium">Oggi</button>
        <button @click="filter='all'; load()" :class="filter==='all'?'btn-primary':'bg-white/5'" class="px-4 py-2 rounded-xl text-sm font-medium">Tutti</button>
    </div>
    <div class="card overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="text-xs uppercase text-slate-400 border-b border-white/5">
                <tr><th class="text-left p-3">Codice</th><th class="text-left p-3">Tavolo</th><th class="text-left p-3">Cameriere</th><th class="text-left p-3">Stato</th><th class="text-left p-3">Ora</th><th class="text-right p-3">Totale</th><th></th></tr>
            </thead>
            <tbody>
                <template x-for="o in orders" :key="o.id">
                    <tr class="border-t border-white/5 hover:bg-white/5">
                        <td class="p-3 font-mono text-xs" x-text="o.code"></td>
                        <td class="p-3" x-text="o.table_code || o.type"></td>
                        <td class="p-3 text-slate-400" x-text="o.waiter_name || '—'"></td>
                        <td class="p-3"><span class="text-xs px-2 py-1 rounded" :class="badge(o.status)" x-text="label(o.status)"></span></td>
                        <td class="p-3 text-xs text-slate-400" x-text="fmtTime(o.created_at)"></td>
                        <td class="p-3 text-right font-semibold" x-text="fmt(o.total)"></td>
                        <td class="p-3"><a :href="`/index.php?p=order_view&id=${o.id}`" class="text-brand-400 hover:text-brand-300">Apri →</a></td>
                    </tr>
                </template>
            </tbody>
        </table>
        <div x-show="!orders.length" class="p-12 text-center text-slate-400">Nessun ordine</div>
    </div>
</main>
<script>
function ordersList(){return{filter:'active',orders:[],async load(){const p=this.filter==='active'?'active=1':this.filter==='today'?'date='+new Date().toISOString().slice(0,10):'';const r=await fetch('/api/orders.php?action=list&'+p);this.orders=(await r.json()).orders||[];},fmt(v){return 'LE '+(parseFloat(v||0)).toLocaleString('it-IT',{minimumFractionDigits:2});},fmtTime(s){try{return new Date(s).toLocaleString('it-IT',{day:'2-digit',month:'2-digit',hour:'2-digit',minute:'2-digit'});}catch(e){return ''}},badge(s){return {open:'bg-slate-500/20',sent:'bg-amber-500/20 text-amber-300',preparing:'bg-orange-500/20 text-orange-300',ready:'bg-sky-500/20 text-sky-300',served:'bg-emerald-500/20 text-emerald-300',closed:'bg-emerald-500/20 text-emerald-300',cancelled:'bg-rose-500/20 text-rose-300'}[s]||'bg-slate-500/20';},label(s){return {open:'Aperto',sent:'Inviato',preparing:'In prep.',ready:'Pronto',served:'Servito',closed:'Chiuso',cancelled:'Annull.'}[s]||s;}}}
</script>
<?php layout_mobile_nav('orders'); layout_foot(); ?>
