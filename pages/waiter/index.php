<?php layout_head('Tavoli', 'waiter'); ?>
<div class="min-h-screen pb-20" x-data="waiterTables()" x-init="load(); setInterval(load,5000)">
    <header class="sticky top-0 z-30 glass border-b border-white/5 px-4 py-3 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <img src="/assets/img/logo.jpeg" class="w-9 h-9 rounded-lg object-cover" alt="">
            <div>
                <div class="font-bold text-sm">Lollapalooza — Cameriere</div>
                <div class="text-xs text-slate-400"><?= e(user()['name']) ?></div>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <?= theme_switcher() ?>
            <a href="/index.php?p=logout" class="text-sm text-slate-400">⏻</a>
        </div>
    </header>

    <div class="p-4">
        <!-- Tabs sale -->
        <div class="flex gap-2 mb-4 overflow-x-auto scrollbar-thin">
            <template x-for="r in rooms" :key="r.id">
                <button @click="currentRoom=r.id"
                    :class="currentRoom===r.id?'bg-brand-500/20 text-white border-brand-500/40':'bg-white/5 text-slate-400 border-white/10'"
                    class="px-4 py-2 rounded-xl border text-sm font-medium whitespace-nowrap" x-text="r.name"></button>
            </template>
        </div>

        <div class="grid grid-cols-3 sm:grid-cols-4 gap-3">
            <template x-for="t in roomTables()" :key="t.id">
                <div @click="clickTable(t)"
                    class="table-card aspect-square rounded-2xl border-2 flex flex-col items-center justify-center text-center p-2"
                    :class="t.status">
                    <div class="font-bold text-xl" x-text="t.code"></div>
                    <div class="text-xs opacity-70" x-text="`${t.seats}p`"></div>
                    <div x-show="t.order_total" class="text-xs mt-1 font-bold" x-text="t.order_total ? 'LE '+parseFloat(t.order_total).toFixed(0) : ''"></div>
                </div>
            </template>
        </div>
    </div>

    <!-- Modal -->
    <div x-show="modal" x-cloak @click.self="modal=null" class="fixed inset-0 z-50 bg-black/70 flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="card p-6 w-full sm:max-w-md rounded-t-3xl sm:rounded-2xl">
            <h3 class="text-2xl font-bold mb-4 text-center" x-text="`Tavolo ${modal?.code}`"></h3>
            <template x-if="modal && !modal.order_id">
                <div class="space-y-3">
                    <label class="text-sm text-slate-400 block">Coperti</label>
                    <div class="grid grid-cols-6 gap-2">
                        <template x-for="n in [1,2,3,4,5,6]" :key="n"><button @click="guests=n" :class="guests===n?'btn-primary':'bg-white/5'" class="py-3 rounded-xl font-bold" x-text="n"></button></template>
                    </div>
                    <input type="number" x-model.number="guests" class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10" placeholder="Più di 6...">
                    <button @click="openTable" class="w-full btn-primary py-4 rounded-xl text-lg font-bold mt-2">Apri tavolo</button>
                    <button @click="modal=null" class="w-full py-3 text-slate-400">Annulla</button>
                </div>
            </template>
            <template x-if="modal && modal.order_id">
                <div class="space-y-3">
                    <div class="text-center mb-2">
                        <div class="text-3xl font-bold text-emerald-400" x-text="'LE '+parseFloat(modal.order_total||0).toFixed(2)"></div>
                        <div class="text-xs text-slate-400">Totale corrente</div>
                    </div>
                    <a :href="`/index.php?p=waiter_table&id=${modal.id}`" class="block btn-primary py-4 rounded-xl text-center text-lg font-bold">+ Aggiungi prodotti</a>
                    <a :href="`/index.php?p=order_view&id=${modal.order_id}`" class="block bg-white/5 py-4 rounded-xl text-center font-semibold">📋 Vedi conto</a>
                    <button @click="modal=null" class="w-full py-3 text-slate-400">Chiudi</button>
                </div>
            </template>
        </div>
    </div>
</div>
<script>
function waiterTables(){return {rooms:[],tables:[],currentRoom:null,modal:null,guests:2,async load(){const r=await fetch('/api/tables.php?action=list');const d=await r.json();this.rooms=d.rooms;this.tables=d.tables;if(!this.currentRoom&&this.rooms[0])this.currentRoom=this.rooms[0].id;},roomTables(){return this.tables.filter(t=>t.room_id===this.currentRoom);},clickTable(t){this.modal=t;this.guests=2;},async openTable(){const r=await fetch('/api/tables.php?action=open',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({table_id:this.modal.id,guests:this.guests})});const d=await r.json();if(d.error)return alert(d.error);location.href='/index.php?p=waiter_table&id='+this.modal.id;}}}
</script>
<?php layout_foot(); ?>
