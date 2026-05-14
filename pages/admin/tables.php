<?php layout_head('Tavoli'); layout_sidebar('tables');
$extra = '<a href="/index.php?p=tables_editor" class="px-3 py-2 rounded-xl bg-white/5 hover:bg-white/10 text-sm">⚙️ Editor mappa</a>';
layout_topbar('Tavoli', 'Mappa visuale del locale', $extra); ?>
<main class="md:ml-64 lg:ml-72 p-4 md:p-8 pb-24 md:pb-8" x-data="tablesPage()" x-init="load(); setInterval(load, 5000)">
    <!-- Stato legenda -->
    <div class="flex flex-wrap gap-3 mb-4 text-xs">
        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-emerald-500"></span>Libero</span>
        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-rose-500"></span>Occupato</span>
        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-amber-500"></span>Prenotato</span>
        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-sky-500"></span>Da pulire</span>
    </div>

    <!-- Tabs sale -->
    <div class="flex gap-2 mb-4 overflow-x-auto scrollbar-thin">
        <template x-for="r in rooms" :key="r.id">
            <button @click="currentRoom=r.id"
                :class="currentRoom===r.id?'bg-brand-500/20 text-white border-brand-500/40':'bg-white/5 text-slate-400 border-white/10'"
                class="px-4 py-2 rounded-xl border text-sm font-medium whitespace-nowrap" x-text="r.name"></button>
        </template>
    </div>

    <!-- Mappa -->
    <div class="card p-4 overflow-auto" style="min-height:600px">
        <div class="relative" :style="`width:${currentRoomData()?.width||900}px;height:${currentRoomData()?.height||600}px;background:radial-gradient(circle at 50% 50%, rgba(139,92,246,0.05), transparent);border-radius:12px;`">
            <template x-for="t in roomTables()" :key="t.id">
                <div @click="clickTable(t)"
                    class="absolute cursor-pointer transition-transform hover:scale-105"
                    :style="`left:${t.pos_x}px;top:${t.pos_y}px;width:${tableSize(t)}px;height:${tableSize(t)}px;`">
                    <template x-for="(c,i) in chairs(t)" :key="i">
                        <div class="absolute bg-white/25"
                            :style="`left:${c.left}px;top:${c.top}px;width:${c.w}px;height:${c.h}px;border-radius:${c.radius};`"></div>
                    </template>
                    <div class="table-card relative w-full h-full border-2 flex flex-col items-center justify-center text-center p-2"
                        :class="t.status"
                        :style="`border-radius:${t.shape==='round'?'50%':'12px'};`">
                        <div class="font-bold text-base" x-text="t.code"></div>
                        <div class="text-[10px] opacity-80" x-text="`${t.seats}p`"></div>
                        <div x-show="t.order_total" class="text-[10px] mt-0.5 font-semibold" x-text="t.order_total ? 'LE '+parseFloat(t.order_total).toFixed(0) : ''"></div>
                        <div x-show="t.status==='occupied'" class="absolute top-1 right-1 w-2 h-2 bg-rose-500 rounded-full anim-pulse-slow"></div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Modal apertura -->
    <div x-show="modal" x-cloak @click.self="modal=null" class="fixed inset-0 z-50 bg-black/70 backdrop-blur flex items-center justify-center p-4">
        <div class="card p-6 max-w-md w-full">
            <h3 class="text-lg font-bold mb-4" x-text="`Tavolo ${modal?.code}`"></h3>
            <template x-if="modal && !modal.order_id">
                <div>
                    <label class="text-sm text-slate-400 mb-2 block">Numero coperti</label>
                    <input type="number" x-model.number="guests" min="1" max="20" class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 mb-4">
                    <div class="flex gap-2">
                        <button @click="openTable()" class="flex-1 btn-primary py-3 rounded-xl font-semibold">Apri tavolo</button>
                        <button @click="modal=null" class="px-4 py-3 rounded-xl bg-white/5">Annulla</button>
                    </div>
                </div>
            </template>
            <template x-if="modal && modal.order_id">
                <div>
                    <p class="text-sm text-slate-400 mb-4">Tavolo già aperto. Totale corrente: <strong class="text-emerald-400" x-text="'LE '+parseFloat(modal.order_total||0).toFixed(2)"></strong></p>
                    <div class="flex flex-col gap-2">
                        <a :href="`/index.php?p=order_view&id=${modal.order_id}`" class="btn-primary text-center py-3 rounded-xl font-semibold">Apri ordine</a>
                        <a :href="`/index.php?p=waiter_table&id=${modal.id}`" class="text-center py-3 rounded-xl bg-white/5">Aggiungi prodotti</a>
                        <button @click="modal=null" class="py-2 rounded-xl text-slate-400">Chiudi</button>
                    </div>
                </div>
            </template>
        </div>
    </div>
</main>

<script>
function tablesPage() {
    return {
        rooms: [], tables: [], currentRoom: null, modal: null, guests: 2,
        async load() {
            const r = await fetch('/api/tables.php?action=list');
            const d = await r.json();
            this.rooms = d.rooms; this.tables = d.tables;
            if (!this.currentRoom && this.rooms[0]) this.currentRoom = this.rooms[0].id;
        },
        currentRoomData() { return this.rooms.find(r=>r.id===this.currentRoom); },
        roomTables() { return this.tables.filter(t=>t.room_id===this.currentRoom); },
        // Stessa logica dell'editor: dimensione proporzionale ai coperti
        tableSize(t) {
            const s = Math.max(1, parseInt(t.seats) || 2);
            return Math.min(190, 58 + s * 12);
        },
        // Stessa logica dell'editor: sedie attorno al tavolo
        chairs(t) {
            const size = this.tableSize(t);
            const n = Math.max(1, parseInt(t.seats) || 2);
            const out = [];
            if (t.shape === 'round') {
                const cs = 15, r = size/2 + 9;
                for (let i=0;i<n;i++) {
                    const a = (Math.PI*2*i/n) - Math.PI/2;
                    out.push({ left:size/2 + r*Math.cos(a) - cs/2, top:size/2 + r*Math.sin(a) - cs/2, w:cs, h:cs, radius:'50%' });
                }
            } else {
                const base = Math.floor(n/4), counts = [base,base,base,base];
                for (let k=0;k<n%4;k++) counts[k]++;
                const [top,bot,right,left] = counts;
                const thick = 12, len = 22, gap = 5;
                const spread = (count) => { const a=[]; for(let i=0;i<count;i++) a.push(size*(i+1)/(count+1)); return a; };
                spread(top).forEach(x   => out.push({ left:x-len/2, top:-thick-gap, w:len, h:thick, radius:'5px' }));
                spread(bot).forEach(x   => out.push({ left:x-len/2, top:size+gap, w:len, h:thick, radius:'5px' }));
                spread(right).forEach(y => out.push({ left:size+gap, top:y-len/2, w:thick, h:len, radius:'5px' }));
                spread(left).forEach(y  => out.push({ left:-thick-gap, top:y-len/2, w:thick, h:len, radius:'5px' }));
            }
            return out;
        },
        clickTable(t) { this.modal = t; this.guests = 2; },
        async openTable() {
            const r = await fetch('/api/tables.php?action=open',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({table_id:this.modal.id, guests:this.guests})});
            const d = await r.json();
            if (d.error) { alert(d.error); return; }
            location.href = '/index.php?p=order_view&id='+d.order_id;
        }
    }
}
</script>
<?php layout_mobile_nav('tables'); layout_foot(); ?>
