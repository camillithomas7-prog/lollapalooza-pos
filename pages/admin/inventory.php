<?php layout_head('Magazzino'); layout_sidebar('inventory'); layout_topbar('Magazzino', 'Stock e movimenti'); ?>
<main class="md:ml-64 lg:ml-72 p-4 md:p-8 pb-24 md:pb-8" x-data="invPage()" x-init="load()">
    <div class="grid md:grid-cols-3 gap-3 mb-4">
        <div class="card p-4"><div class="text-xs text-slate-400">Prodotti tracciati</div><div class="text-2xl font-bold" x-text="products.length"></div></div>
        <div class="card p-4"><div class="text-xs text-slate-400">Sotto soglia</div><div class="text-2xl font-bold text-rose-400" x-text="lowCount()"></div></div>
        <div class="card p-4"><div class="text-xs text-slate-400">Valore stock</div><div class="text-2xl font-bold text-emerald-400" x-text="'€ '+totalValue().toFixed(2)"></div></div>
    </div>

    <div class="card overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="text-xs uppercase text-slate-400 border-b border-white/5">
                <tr><th class="text-left p-3">Prodotto</th><th class="text-left p-3">Categoria</th><th class="text-right p-3">Stock</th><th class="text-right p-3">Min</th><th class="text-right p-3">Costo</th><th class="text-right p-3">Valore</th><th></th></tr>
            </thead>
            <tbody>
                <template x-for="p in products" :key="p.id">
                    <tr class="border-t border-white/5 hover:bg-white/5">
                        <td class="p-3 font-semibold" x-text="p.name"></td>
                        <td class="p-3 text-slate-400" x-text="p.category_name"></td>
                        <td class="p-3 text-right" :class="parseFloat(p.stock)<=parseFloat(p.stock_min)?'text-rose-400 font-bold':''" x-text="parseFloat(p.stock).toFixed(0)+' '+p.unit"></td>
                        <td class="p-3 text-right text-slate-400" x-text="parseFloat(p.stock_min).toFixed(0)"></td>
                        <td class="p-3 text-right" x-text="'€'+parseFloat(p.cost).toFixed(2)"></td>
                        <td class="p-3 text-right text-emerald-400 font-semibold" x-text="'€'+(parseFloat(p.stock)*parseFloat(p.cost)).toFixed(2)"></td>
                        <td class="p-3"><button @click="openMov(p)" class="px-3 py-1 rounded-lg bg-brand-500/20 text-brand-300 text-xs">Movimento</button></td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <!-- Modal mov -->
    <div x-show="mov" x-cloak @click.self="mov=null" class="fixed inset-0 z-50 bg-black/70 flex items-center justify-center p-4">
        <div class="card p-6 w-full max-w-md">
            <h3 class="text-lg font-bold mb-2" x-text="mov?.product?.name"></h3>
            <p class="text-sm text-slate-400 mb-4" x-text="`Stock attuale: ${parseFloat(mov?.product?.stock||0).toFixed(0)} ${mov?.product?.unit||''}`"></p>
            <div class="grid grid-cols-3 gap-2 mb-3">
                <button @click="mov.type='in'" :class="mov.type==='in'?'btn-primary':'bg-white/5'" class="py-2 rounded-lg text-sm font-semibold">+ Carico</button>
                <button @click="mov.type='out'" :class="mov.type==='out'?'btn-primary':'bg-white/5'" class="py-2 rounded-lg text-sm font-semibold">− Scarico</button>
                <button @click="mov.type='adjust'" :class="mov.type==='adjust'?'btn-primary':'bg-white/5'" class="py-2 rounded-lg text-sm font-semibold">⚖ Rettifica</button>
            </div>
            <div class="space-y-2">
                <input type="number" x-model.number="mov.qty" step="0.01" :placeholder="mov.type==='adjust'?'Nuovo stock totale':'Quantità'" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10">
                <input type="number" x-model.number="mov.cost" step="0.01" placeholder="Costo unitario (carico)" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10" x-show="mov.type==='in'">
                <input x-model="mov.notes" placeholder="Note" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10">
                <div class="flex gap-2 mt-3">
                    <button @click="saveMov" class="flex-1 btn-primary py-2.5 rounded-lg font-semibold">Conferma</button>
                    <button @click="mov=null" class="px-4 py-2.5 rounded-lg bg-white/5">Annulla</button>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
function invPage(){return {
    products:[], mov:null,
    async load(){const r=await fetch('/api/inventory.php?action=stock');this.products=(await r.json()).products;},
    lowCount(){return this.products.filter(p=>parseFloat(p.stock)<=parseFloat(p.stock_min)).length;},
    totalValue(){return this.products.reduce((s,p)=>s+parseFloat(p.stock)*parseFloat(p.cost),0);},
    openMov(p){this.mov={product:p,product_id:p.id,type:'in',qty:0,cost:p.cost,notes:''};},
    async saveMov(){await fetch('/api/inventory.php?action=movement',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(this.mov)});this.mov=null;this.load();}
}}
</script>
<?php layout_mobile_nav('inventory'); layout_foot(); ?>
