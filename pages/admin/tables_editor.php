<?php layout_head('Editor Mappa'); layout_sidebar('tables'); layout_topbar('Editor Mappa Tavoli', 'Trascina i tavoli per posizionarli'); ?>
<main class="md:ml-64 lg:ml-72 p-4 md:p-8 pb-24 md:pb-8" x-data="editor()" x-init="load()">
    <div class="flex flex-wrap gap-2 mb-4">
        <template x-for="r in rooms" :key="r.id">
            <button @click="currentRoom=r.id"
                :class="currentRoom===r.id?'bg-brand-500/20 border-brand-500/40':'bg-white/5 border-white/10'"
                class="px-4 py-2 rounded-xl border text-sm" x-text="r.name"></button>
        </template>
        <button @click="addTable" class="px-4 py-2 rounded-xl btn-primary text-sm">+ Aggiungi tavolo</button>
        <button @click="save" class="px-4 py-2 rounded-xl bg-emerald-500 text-white text-sm font-semibold">💾 Salva</button>
    </div>

    <div class="card p-4 overflow-auto" style="min-height:600px">
        <div class="relative" :style="`width:${currentRoomData()?.width||900}px;height:${currentRoomData()?.height||600}px;background:repeating-linear-gradient(0deg,rgba(255,255,255,0.02) 0,rgba(255,255,255,0.02) 1px,transparent 1px,transparent 40px),repeating-linear-gradient(90deg,rgba(255,255,255,0.02) 0,rgba(255,255,255,0.02) 1px,transparent 1px,transparent 40px);border-radius:12px;`">
            <template x-for="t in roomTables()" :key="t.id">
                <div class="absolute cursor-move"
                    :style="`left:${t.pos_x}px;top:${t.pos_y}px;width:${tableSize(t)}px;height:${tableSize(t)}px;`"
                    @mousedown="startDrag($event, t)"
                    @click="selected=t">
                    <template x-for="(c,i) in chairs(t)" :key="i">
                        <div class="absolute"
                            :class="selected?.id===t.id?'bg-brand-400':'bg-white/30'"
                            :style="`left:${c.left}px;top:${c.top}px;width:${c.w}px;height:${c.h}px;border-radius:${c.radius};`"></div>
                    </template>
                    <div class="w-full h-full flex flex-col items-center justify-center border-2 text-center"
                        :class="selected?.id===t.id?'border-brand-500 bg-brand-500/20':'border-white/20 bg-white/5'"
                        :style="`border-radius:${t.shape==='round'?'50%':'12px'};`">
                        <div class="font-bold text-sm" x-text="t.code"></div>
                        <div class="text-[10px]" x-text="`${t.seats}p`"></div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Pannello selezione -->
    <div x-show="selected" x-cloak class="fixed bottom-20 md:bottom-4 left-4 right-4 md:left-auto md:right-4 md:w-80 card p-4 z-30">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-bold">Tavolo selezionato</h3>
            <button @click="selected=null" class="text-slate-400">✕</button>
        </div>
        <div class="space-y-2 text-sm">
            <div><label class="text-xs text-slate-400">Codice</label><input x-model="selected.code" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10"></div>
            <div class="grid grid-cols-2 gap-2">
                <div><label class="text-xs text-slate-400">Coperti</label><input type="number" x-model.number="selected.seats" min="1" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10"></div>
                <div><label class="text-xs text-slate-400">Forma</label><select x-model="selected.shape" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10"><option value="square">Quadrato</option><option value="round">Tondo</option></select></div>
            </div>
            <div><label class="text-xs text-slate-400">Sala</label><select x-model.number="selected.room_id" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10">
                <template x-for="r in rooms" :key="r.id"><option :value="r.id" x-text="r.name"></option></template>
            </select></div>
            <button @click="deleteTable" class="w-full mt-2 py-2 rounded-lg bg-rose-500/20 text-rose-400 text-sm">🗑 Elimina</button>
        </div>
    </div>
</main>

<script>
function editor() {
    return {
        rooms: [], tables: [], currentRoom: null, selected: null, dragging: null,
        async load() {
            const r = await fetch('/api/tables.php?action=list'); const d = await r.json();
            this.rooms = d.rooms; this.tables = d.tables;
            if (!this.currentRoom && this.rooms[0]) this.currentRoom = this.rooms[0].id;
        },
        currentRoomData() { return this.rooms.find(r=>r.id===this.currentRoom); },
        roomTables() { return this.tables.filter(t=>t.room_id===this.currentRoom); },
        // Dimensione del tavolo proporzionale ai coperti
        tableSize(t) {
            const s = Math.max(1, parseInt(t.seats) || 2);
            return Math.min(190, 58 + s * 12);
        },
        // Posizione delle sedie attorno al tavolo (calcolata da forma + coperti)
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
                const base = Math.floor(n/4), counts = [base,base,base,base]; // top, bottom, right, left
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
        startDrag(e, t) {
            e.preventDefault(); this.selected = t; this.dragging = t;
            const startX = e.clientX, startY = e.clientY, ox = t.pos_x, oy = t.pos_y;
            const move = (ev) => { t.pos_x = Math.max(0, ox + ev.clientX - startX); t.pos_y = Math.max(0, oy + ev.clientY - startY); };
            const up = () => { document.removeEventListener('mousemove', move); document.removeEventListener('mouseup', up); this.dragging = null; };
            document.addEventListener('mousemove', move); document.addEventListener('mouseup', up);
        },
        async addTable() {
            const code = prompt('Codice tavolo (es. T9):'); if (!code) return;
            const r = await fetch('/api/tables.php?action=create', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({code, room_id:this.currentRoom, seats:4, pos_x:50, pos_y:50})});
            await r.json(); this.load();
        },
        async deleteTable() {
            if (!confirm('Eliminare tavolo '+this.selected.code+'?')) return;
            await fetch('/api/tables.php?action=delete',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:this.selected.id})});
            this.selected = null; this.load();
        },
        async save() {
            await fetch('/api/tables.php?action=save_layout',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({tables:this.tables})});
            alert('Mappa salvata');
        }
    }
}
</script>
<?php layout_mobile_nav('tables'); layout_foot(); ?>
