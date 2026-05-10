<?php layout_head('Ordine'); layout_sidebar('orders'); layout_topbar('Ordine', 'Gestione ordine completa'); ?>
<main class="md:ml-64 lg:ml-72 p-4 md:p-8 pb-24 md:pb-8" x-data="orderView(<?= (int)($_GET['id'] ?? 0) ?>)" x-init="load(); setInterval(load, 8000)">
    <div x-show="!order" class="text-center py-20 text-slate-400">Caricamento...</div>
    <template x-if="order">
        <div class="grid lg:grid-cols-3 gap-4">
            <!-- Left: items -->
            <div class="lg:col-span-2 space-y-4">
                <div class="card p-5">
                    <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
                        <div>
                            <div class="text-xs text-slate-400">Ordine</div>
                            <div class="font-mono font-bold" x-text="order.code"></div>
                        </div>
                        <div>
                            <div class="text-xs text-slate-400">Tavolo</div>
                            <div class="font-bold text-lg" x-text="order.table_code || '—'"></div>
                        </div>
                        <div>
                            <div class="text-xs text-slate-400">Coperti</div>
                            <div class="font-bold" x-text="order.guests"></div>
                        </div>
                        <div>
                            <div class="text-xs text-slate-400">Stato</div>
                            <span class="text-sm px-2 py-1 rounded-md font-medium" :class="badgeStatus(order.status)" x-text="statusLabel(order.status)"></span>
                        </div>
                    </div>

                    <!-- Items -->
                    <div class="space-y-2">
                        <template x-for="i in items" :key="i.id">
                            <div class="flex items-center gap-3 p-3 rounded-xl border border-white/5 hover:bg-white/5">
                                <div class="text-xs px-2 py-1 rounded font-medium" :class="badgeItem(i.status)" x-text="itemLabel(i.status)"></div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-semibold truncate" x-text="i.name"></div>
                                    <div x-show="i.notes" class="text-xs text-amber-400" x-text="'📝 '+i.notes"></div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button @click="changeQty(i,-1)" :disabled="i.status!=='draft'" class="w-7 h-7 rounded-lg bg-white/5 disabled:opacity-30">−</button>
                                    <span class="w-8 text-center font-bold" x-text="parseFloat(i.qty).toFixed(0)"></span>
                                    <button @click="changeQty(i,1)" :disabled="i.status!=='draft'" class="w-7 h-7 rounded-lg bg-white/5 disabled:opacity-30">+</button>
                                </div>
                                <div class="text-right w-20">
                                    <div class="font-bold" x-text="fmt(i.qty*i.price)"></div>
                                    <div class="text-xs text-slate-400" x-text="fmt(i.price)+'/cad'"></div>
                                </div>
                            </div>
                        </template>
                        <div x-show="!items.length" class="text-center py-12 text-slate-400">
                            Nessun prodotto. <a :href="`/index.php?p=waiter_table&id=${order.table_id}`" class="text-brand-400">Aggiungi →</a>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <a :href="`/index.php?p=waiter_table&id=${order.table_id}`" class="flex-1 btn-primary py-3 rounded-xl text-center font-semibold">+ Aggiungi prodotti</a>
                        <button @click="sendOrder" :disabled="!hasDraft()" class="px-6 py-3 rounded-xl bg-amber-500 text-black font-semibold disabled:opacity-30">📤 Invia in cucina/bar</button>
                    </div>
                </div>
            </div>

            <!-- Right: total + actions -->
            <div class="space-y-4">
                <div class="card p-5">
                    <h3 class="font-bold mb-3">Totale</h3>
                    <div class="space-y-1 text-sm">
                        <div class="flex justify-between text-slate-400"><span>Subtotale</span><span x-text="fmt(order.subtotal)"></span></div>
                        <div class="flex justify-between text-slate-400"><span>IVA</span><span x-text="fmt(order.tax)"></span></div>
                        <div class="flex justify-between text-slate-400">
                            <span>Sconto €</span>
                            <input type="number" :value="order.discount" @change="setDiscount($event.target.value)" step="0.01" class="w-20 text-right bg-white/5 rounded px-2">
                        </div>
                        <div class="flex justify-between text-2xl font-bold pt-2 border-t border-white/10"><span>Totale</span><span class="text-emerald-400" x-text="fmt(order.total)"></span></div>
                        <div class="flex justify-between text-sm text-slate-400 pt-2"><span>Pagato</span><span x-text="fmt(order.paid)"></span></div>
                        <div class="flex justify-between text-sm font-semibold"><span>Da pagare</span><span x-text="fmt(order.total-order.paid)"></span></div>
                    </div>
                </div>

                <div class="card p-5">
                    <h3 class="font-bold mb-3">💳 Pagamento</h3>
                    <div class="grid grid-cols-2 gap-2 mb-3">
                        <button @click="quickPay('cash')" class="py-3 rounded-xl bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 font-semibold">💵 Contanti</button>
                        <button @click="quickPay('card')" class="py-3 rounded-xl bg-sky-500/10 hover:bg-sky-500/20 text-sky-400 font-semibold">💳 Carta</button>
                        <button @click="quickPay('pos')" class="py-3 rounded-xl bg-violet-500/10 hover:bg-violet-500/20 text-violet-400 font-semibold">📱 POS</button>
                        <button @click="splitPay" class="py-3 rounded-xl bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 font-semibold">🔀 Split</button>
                    </div>
                    <button onclick="window.print()" class="w-full py-2.5 rounded-xl bg-white/5 hover:bg-white/10 text-sm">🖨 Stampa preconto</button>
                </div>

                <div class="card p-4">
                    <button @click="cancelOrder" class="w-full py-2 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 text-sm">🗑 Annulla ordine</button>
                </div>
            </div>
        </div>
    </template>

    <!-- Split modal -->
    <div x-show="splitModal" x-cloak @click.self="splitModal=false" class="fixed inset-0 z-50 bg-black/70 flex items-center justify-center p-4">
        <div class="card p-6 max-w-md w-full">
            <h3 class="text-lg font-bold mb-4">Pagamento Split</h3>
            <div class="space-y-3 max-h-[60vh] overflow-y-auto">
                <template x-for="(p,i) in splits" :key="i">
                    <div class="flex gap-2">
                        <select x-model="p.method" class="px-3 py-2 rounded-lg bg-white/5 border border-white/10 flex-1">
                            <option value="cash">Contanti</option><option value="card">Carta</option><option value="pos">POS</option><option value="voucher">Buono</option>
                        </select>
                        <input type="number" x-model.number="p.amount" step="0.01" class="px-3 py-2 rounded-lg bg-white/5 border border-white/10 w-32 text-right" placeholder="0.00">
                        <button @click="splits.splice(i,1)" class="text-rose-400">✕</button>
                    </div>
                </template>
            </div>
            <button @click="splits.push({method:'cash',amount:0})" class="w-full mt-3 py-2 rounded-lg bg-white/5 text-sm">+ Aggiungi metodo</button>
            <div class="mt-4 flex gap-2">
                <button @click="confirmSplit" class="flex-1 btn-primary py-3 rounded-xl font-semibold">Conferma pagamento</button>
                <button @click="splitModal=false" class="px-4 py-3 rounded-xl bg-white/5">Annulla</button>
            </div>
        </div>
    </div>
</main>

<script>
function orderView(id) {
    return {
        id, order: null, items: [], payments: [], splitModal: false, splits: [],
        async load() {
            const r = await fetch('/api/orders.php?action=get&id='+this.id);
            const d = await r.json(); if (d.error) return alert(d.error);
            this.order = d.order; this.items = d.items; this.payments = d.payments;
        },
        hasDraft() { return this.items.some(i=>i.status==='draft'); },
        async sendOrder() {
            await fetch('/api/orders.php?action=send',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({order_id:this.id})});
            this.load();
        },
        async changeQty(it, d) {
            const newQty = Math.max(0, parseFloat(it.qty) + d);
            await fetch('/api/orders.php?action=update_item',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:it.id, qty:newQty, notes:it.notes||''})});
            this.load();
        },
        async setDiscount(v) {
            await fetch('/api/orders.php?action=discount',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({order_id:this.id, discount:parseFloat(v)||0})});
            this.load();
        },
        async quickPay(method) {
            const amt = this.order.total - this.order.paid;
            if (amt<=0) return alert('Già pagato');
            await fetch('/api/orders.php?action=pay',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({order_id:this.id, payments:[{method, amount:amt}]})});
            this.load();
        },
        splitPay() { this.splits=[{method:'cash',amount:0},{method:'card',amount:0}]; this.splitModal=true; },
        async confirmSplit() {
            await fetch('/api/orders.php?action=pay',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({order_id:this.id, payments:this.splits})});
            this.splitModal=false; this.load();
        },
        async cancelOrder() {
            if (!confirm('Annullare ordine?')) return;
            await fetch('/api/orders.php?action=cancel',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({order_id:this.id})});
            location.href = '/index.php?p=tables';
        },
        fmt(v){return '€ '+(parseFloat(v||0)).toLocaleString('it-IT',{minimumFractionDigits:2,maximumFractionDigits:2});},
        badgeStatus(s){return {open:'bg-slate-500/20 text-slate-300',sent:'bg-amber-500/20 text-amber-300',preparing:'bg-amber-500/20 text-amber-300',ready:'bg-sky-500/20 text-sky-300',served:'bg-emerald-500/20 text-emerald-300',closed:'bg-emerald-500/20 text-emerald-300',cancelled:'bg-rose-500/20 text-rose-300'}[s]||'bg-slate-500/20';},
        statusLabel(s){return {open:'Aperto',sent:'Inviato',preparing:'In prep.',ready:'Pronto',served:'Servito',closed:'Chiuso',cancelled:'Annullato'}[s]||s;},
        badgeItem(s){return {draft:'bg-slate-500/20 text-slate-300',sent:'bg-amber-500/20 text-amber-300',preparing:'bg-orange-500/20 text-orange-300',ready:'bg-sky-500/20 text-sky-300',served:'bg-emerald-500/20 text-emerald-300',cancelled:'bg-rose-500/20 text-rose-300'}[s]||'bg-slate-500/20';},
        itemLabel(s){return {draft:'BOZZA',sent:'INVIATO',preparing:'IN PREP',ready:'PRONTO',served:'SERVITO',cancelled:'ANN'}[s]||s;}
    }
}
</script>
<?php layout_mobile_nav('orders'); layout_foot(); ?>
