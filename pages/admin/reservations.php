<?php layout_head('Prenotazioni'); layout_sidebar('reservations'); layout_topbar('Prenotazioni', 'Calendario coperti e tavoli'); ?>
<main class="md:ml-64 lg:ml-72 p-4 md:p-8 pb-24 md:pb-8" x-data="resPage()" x-init="loadTables(); load()">
    <div class="flex justify-between items-center mb-4">
        <input type="date" x-model="from" @change="load" class="px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-sm">
        <button @click="add" class="px-4 py-2 rounded-lg btn-primary text-sm font-semibold">+ Prenotazione</button>
    </div>
    <div class="grid gap-2">
        <template x-for="r in reservations" :key="r.id">
            <div class="card p-4 flex flex-wrap items-center gap-3">
                <div class="text-center w-16">
                    <div class="text-xs text-slate-400" x-text="new Date(r.date).toLocaleDateString('it-IT',{weekday:'short'})"></div>
                    <div class="text-2xl font-bold" x-text="new Date(r.date).getDate()"></div>
                    <div class="text-sm font-semibold text-brand-400" x-text="r.time?.slice(0,5)"></div>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="font-bold" x-text="r.customer_name"></div>
                    <div class="text-sm text-slate-400" x-text="`${r.guests} coperti · ${r.phone||''}`"></div>
                    <div x-show="r.notes" class="text-xs text-amber-300 mt-1" x-text="r.notes"></div>
                </div>
                <div class="text-sm" x-text="r.table_code?'🪑 '+r.table_code:''"></div>
                <select :value="r.status" @change="setStatus(r,$event.target.value)" class="px-3 py-1.5 rounded-lg bg-white/5 border border-white/10 text-xs">
                    <option value="confirmed">Confermata</option><option value="seated">Seduta</option><option value="no_show">No-show</option><option value="cancelled">Annullata</option>
                </select>
                <button @click="edit(r)" class="text-brand-400" title="Modifica">✏️</button>
                <button @click="del(r)" class="text-red-400 hover:text-red-300" title="Elimina">🗑️</button>
            </div>
        </template>
        <div x-show="!reservations.length" class="text-center py-12 text-slate-400">Nessuna prenotazione</div>
    </div>

    <div x-show="modal" x-cloak @click.self="modal=null" class="fixed inset-0 z-50 bg-black/70 flex items-center justify-center p-4">
        <div class="card p-6 w-full max-w-md">
            <h3 class="text-lg font-bold mb-4">Prenotazione</h3>
            <div class="space-y-2">
                <input x-model="modal.customer_name" placeholder="Nome cliente" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10">
                <input x-model="modal.phone" placeholder="Telefono" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10">
                <div class="grid grid-cols-2 gap-2">
                    <input type="date" x-model="modal.date" class="px-3 py-2 rounded-lg bg-white/5 border border-white/10">
                    <input type="time" x-model="modal.time" class="px-3 py-2 rounded-lg bg-white/5 border border-white/10">
                </div>
                <input type="number" x-model.number="modal.guests" placeholder="Coperti" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10">
                <select x-model="modal.table_id" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10">
                    <option :value="null">— Nessun tavolo assegnato —</option>
                    <template x-for="room in rooms" :key="room.id">
                        <optgroup :label="room.name">
                            <template x-for="tbl in tables.filter(t=>t.room_id===room.id)" :key="tbl.id">
                                <option :value="tbl.id" x-text="`Tavolo ${tbl.code} · ${tbl.seats} posti`"></option>
                            </template>
                        </optgroup>
                    </template>
                    <template x-for="tbl in tables.filter(t=>!rooms.some(r=>r.id===t.room_id))" :key="tbl.id">
                        <option :value="tbl.id" x-text="`Tavolo ${tbl.code} · ${tbl.seats} posti`"></option>
                    </template>
                </select>
                <textarea x-model="modal.notes" rows="2" placeholder="Note" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10"></textarea>
                <div class="flex gap-2 mt-3">
                    <button @click="save" class="flex-1 btn-primary py-2.5 rounded-lg font-semibold">Salva</button>
                    <button @click="modal=null" class="px-4 py-2.5 rounded-lg bg-white/5">Annulla</button>
                </div>
            </div>
        </div>
    </div>
</main>
<script>
function resPage(){return {
    from:new Date().toISOString().slice(0,10), reservations:[], modal:null,
    rooms:[], tables:[],
    async load(){const r=await fetch('/api/reservations.php?action=list&from='+this.from);this.reservations=(await r.json()).reservations;},
    async loadTables(){const r=await fetch('/api/tables.php?action=list');const d=await r.json();this.rooms=d.rooms||[];this.tables=d.tables||[];},
    add(){this.modal={customer_name:'',phone:'',date:this.from,time:'20:00',guests:2,table_id:null,notes:'',status:'confirmed'};},
    edit(r){this.modal={...r, table_id: r.table_id || null};},
    async save(){await fetch('/api/reservations.php?action=save',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(this.modal)});this.modal=null;this.load();},
    async setStatus(r,s){await fetch('/api/reservations.php?action=set_status',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:r.id,status:s})});this.load();},
    async del(r){if(!confirm(`Eliminare la prenotazione di ${r.customer_name||'questo cliente'}?`))return;await fetch('/api/reservations.php?action=delete',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:r.id})});this.load();}
}}
</script>
<?php layout_mobile_nav('reservations'); layout_foot(); ?>
