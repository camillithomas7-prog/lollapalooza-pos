<?php layout_head('Ordina');
$tableId = (int)($_GET['id'] ?? 0); ?>
<div class="min-h-screen pb-32" x-data="waiterOrder(<?= $tableId ?>)" x-init="init()">
    <header class="sticky top-0 z-30 glass border-b border-white/5 px-3 py-2 flex items-center justify-between gap-2">
        <a href="/index.php?p=waiter" class="px-3 py-2 rounded-lg bg-white/5 text-sm">← Tavoli</a>
        <div class="text-center">
            <div class="font-bold" x-text="'Tavolo '+(table?.code||'')"></div>
            <div class="text-xs text-slate-400" x-text="order ? `#${order.code}` : '...'"></div>
        </div>
        <div class="flex items-center gap-2">
            <?= theme_switcher() ?>
            <button @click="showCart=true" class="relative px-3 py-2 rounded-lg btn-primary text-sm font-bold">
                🛒 <span x-text="cartCount"></span>
            </button>
        </div>
    </header>

    <!-- Search bar -->
    <div class="p-3 sticky top-[57px] z-20 bg-ink-900/95 backdrop-blur">
        <input type="search" x-model="search" placeholder="🔍 Cerca prodotto..." class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-base">
    </div>

    <!-- Categories chips -->
    <div class="px-3 pb-2 flex gap-2 overflow-x-auto scrollbar-thin">
        <button @click="currentCat=null" :class="!currentCat?'btn-primary':'bg-white/5'" class="px-3 py-2 rounded-xl text-sm font-medium whitespace-nowrap">Tutto</button>
        <template x-for="c in categories" :key="c.id">
            <button @click="currentCat=c.id" :class="currentCat===c.id?'btn-primary':'bg-white/5'" class="px-3 py-2 rounded-xl text-sm font-medium whitespace-nowrap">
                <span x-text="c.icon+' '+c.name"></span>
            </button>
        </template>
    </div>

    <!-- Products grid -->
    <div class="p-3 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
        <template x-for="p in filteredProducts()" :key="p.id">
            <button @click="addToCart(p)" class="card p-3 text-left hover:scale-[1.02] active:scale-95 transition relative">
                <div class="aspect-square rounded-xl mb-2 flex items-center justify-center text-3xl" :style="`background:${catColor(p.category_id)}20`">
                    <span x-text="catIcon(p.category_id)"></span>
                </div>
                <div class="font-semibold text-sm leading-tight" x-text="p.name"></div>
                <div class="font-bold text-emerald-400 mt-1" x-text="'€ '+parseFloat(p.price).toFixed(2)"></div>
                <div x-show="cartQty(p.id)>0" class="absolute top-2 right-2 w-7 h-7 bg-brand-500 rounded-full text-xs font-bold flex items-center justify-center" x-text="cartQty(p.id)"></div>
            </button>
        </template>
    </div>

    <!-- Bottom bar -->
    <div x-show="cartCount>0" class="fixed bottom-0 inset-x-0 glass border-t border-white/5 p-3 z-30">
        <div class="flex gap-2">
            <button @click="showCart=true" class="flex-1 py-3 rounded-xl bg-white/5 font-semibold">
                <span x-text="cartCount+' prodotti'"></span> · <span x-text="'€ '+cartTotal().toFixed(2)"></span>
            </button>
            <button @click="sendOrder" class="px-6 py-3 rounded-xl btn-primary font-bold">📤 Invia</button>
        </div>
    </div>

    <!-- Cart modal -->
    <div x-show="showCart" x-cloak class="fixed inset-0 z-50 bg-black/70 flex items-end justify-center" @click.self="showCart=false">
        <div class="bg-ink-800 w-full max-w-lg rounded-t-3xl p-4 max-h-[85vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold">Carrello</h3>
                <button @click="showCart=false" class="text-slate-400 text-2xl">✕</button>
            </div>
            <!-- Items già inviati -->
            <template x-if="sentItems().length">
                <div class="mb-4">
                    <div class="text-xs text-slate-400 uppercase mb-2">Già inviati</div>
                    <template x-for="i in sentItems()" :key="i.id">
                        <div class="flex justify-between p-2 text-sm border-b border-white/5">
                            <span><span class="text-amber-400 mr-2" x-text="i.qty+'x'"></span><span x-text="i.name"></span></span>
                            <span x-text="'€'+(i.qty*i.price).toFixed(2)"></span>
                        </div>
                    </template>
                </div>
            </template>
            <!-- Cart -->
            <div class="space-y-2 mb-4">
                <template x-for="(it,i) in cart" :key="i">
                    <div class="flex items-center gap-2 p-2 rounded-xl bg-white/5">
                        <div class="flex-1">
                            <div class="font-semibold text-sm" x-text="it.name"></div>
                            <input type="text" x-model="it.notes" placeholder="Note (es. senza glutine)" class="w-full text-xs bg-transparent border-b border-white/10 mt-1 px-1 py-0.5">
                        </div>
                        <div class="flex items-center gap-1.5">
                            <button @click="changeCart(i,-1)" class="w-8 h-8 rounded-lg bg-white/5">−</button>
                            <span class="w-8 text-center font-bold" x-text="it.qty"></span>
                            <button @click="changeCart(i,1)" class="w-8 h-8 rounded-lg bg-white/5">+</button>
                        </div>
                        <div class="w-16 text-right font-bold text-sm" x-text="'€'+(it.qty*it.price).toFixed(2)"></div>
                    </div>
                </template>
                <div x-show="!cart.length" class="text-center py-8 text-slate-400 text-sm">Carrello vuoto</div>
            </div>
            <div class="flex justify-between text-lg font-bold pt-3 border-t border-white/10 mb-4">
                <span>Da inviare</span>
                <span class="text-emerald-400" x-text="'€ '+cartTotal().toFixed(2)"></span>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <button @click="showCart=false" class="py-3 rounded-xl bg-white/5">← Continua</button>
                <button @click="sendOrder" :disabled="!cart.length" class="py-3 rounded-xl btn-primary font-bold disabled:opacity-30">📤 Invia ordine</button>
            </div>
            <a :href="`/index.php?p=order_view&id=${order?.id}`" class="block mt-3 text-center text-sm text-brand-400">Vai al conto completo →</a>
        </div>
    </div>
</div>

<script>
function waiterOrder(tableId){return {
    tableId, table:null, order:null, items:[], categories:[], products:[],
    currentCat:null, search:'', cart:[], showCart:false,
    async init() {
        // Find or create order for this table
        const t = await fetch('/api/tables.php?action=list');
        const td = await t.json();
        this.table = td.tables.find(x=>x.id==this.tableId);
        if (!this.table?.order_id) {
            const r = await fetch('/api/tables.php?action=open',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({table_id:this.tableId,guests:2})});
            const d = await r.json();
            if (d.order_id) this.table.order_id = d.order_id;
        }
        await this.loadOrder();
        await this.loadMenu();
    },
    async loadOrder(){
        if (!this.table?.order_id) return;
        const r = await fetch('/api/orders.php?action=get&id='+this.table.order_id);
        const d = await r.json();
        this.order = d.order; this.items = d.items;
    },
    async loadMenu(){
        const r = await fetch('/api/menu.php?action=list');
        const d = await r.json();
        this.categories = d.categories; this.products = d.products;
    },
    sentItems(){ return this.items.filter(i=>i.status!=='draft'); },
    catColor(id){return this.categories.find(c=>c.id===id)?.color||'#8b5cf6';},
    catIcon(id){return this.categories.find(c=>c.id===id)?.icon||'🍽';},
    filteredProducts(){
        let p = this.products;
        if (this.currentCat) p = p.filter(x=>x.category_id===this.currentCat);
        if (this.search) { const s = this.search.toLowerCase(); p = p.filter(x=>x.name.toLowerCase().includes(s)); }
        return p;
    },
    cartQty(pid){ const it = this.cart.find(c=>c.id===pid); return it?it.qty:0; },
    cartCount(){ return this.cart.reduce((s,c)=>s+c.qty,0); },
    cartTotal(){ return this.cart.reduce((s,c)=>s+c.qty*c.price,0); },
    addToCart(p){
        const ex = this.cart.find(c=>c.id===p.id && !c.notes);
        if (ex) ex.qty++; else this.cart.push({id:p.id,name:p.name,price:p.price,qty:1,notes:''});
        // Mini haptic feedback
        if (navigator.vibrate) navigator.vibrate(15);
    },
    changeCart(i,d){ this.cart[i].qty += d; if (this.cart[i].qty<=0) this.cart.splice(i,1); },
    async sendOrder(){
        if (!this.cart.length) return alert('Carrello vuoto');
        if (!this.order?.id) return;
        for (const it of this.cart) {
            await fetch('/api/orders.php?action=add_item',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({order_id:this.order.id, product_id:it.id, qty:it.qty, notes:it.notes||''})});
        }
        await fetch('/api/orders.php?action=send',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({order_id:this.order.id})});
        this.cart = []; this.showCart = false;
        if (navigator.vibrate) navigator.vibrate([50,30,50]);
        await this.loadOrder();
        // Toast
        const t = document.createElement('div');
        t.className='fixed top-20 left-1/2 -translate-x-1/2 px-4 py-3 rounded-xl bg-emerald-500 text-white font-bold shadow-2xl z-50';
        t.textContent='✅ Ordine inviato!'; document.body.appendChild(t);
        setTimeout(()=>t.remove(),2000);
    }
}}
</script>
<?php layout_foot(); ?>
