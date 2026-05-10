<?php layout_head('Personale'); layout_sidebar('staff'); layout_topbar('Personale', 'Dipendenti, turni, presenze'); ?>
<main class="md:ml-64 lg:ml-72 p-4 md:p-8 pb-24 md:pb-8" x-data="staffPage()" x-init="load()">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-bold">Dipendenti</h2>
        <button @click="edit({})" class="px-4 py-2 rounded-lg btn-primary text-sm font-semibold">+ Nuovo dipendente</button>
    </div>

    <div class="card overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="text-xs uppercase text-slate-400 border-b border-white/5">
                <tr><th class="text-left p-3">Nome</th><th class="text-left p-3">Ruolo</th><th class="text-left p-3">Email</th><th class="text-left p-3">Telefono</th><th class="text-right p-3">Stipendio</th><th class="text-right p-3">€/h</th><th class="text-center p-3">PIN</th><th></th></tr>
            </thead>
            <tbody>
                <template x-for="u in users" :key="u.id">
                    <tr class="border-t border-white/5 hover:bg-white/5" :class="!u.active && 'opacity-40'">
                        <td class="p-3 font-semibold" x-text="u.name"></td>
                        <td class="p-3"><span class="text-xs px-2 py-1 rounded bg-brand-500/20 text-brand-300 capitalize" x-text="u.role"></span></td>
                        <td class="p-3 text-slate-400 text-xs" x-text="u.email"></td>
                        <td class="p-3 text-slate-400" x-text="u.phone||'—'"></td>
                        <td class="p-3 text-right" x-text="'€'+parseFloat(u.salary||0).toFixed(2)"></td>
                        <td class="p-3 text-right" x-text="'€'+parseFloat(u.hourly_rate||0).toFixed(2)"></td>
                        <td class="p-3 text-center font-mono" x-text="u.pin||'—'"></td>
                        <td class="p-3"><button @click="edit(u)" class="text-brand-400">✏️</button></td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <!-- Turni -->
    <div class="mt-6 card p-5">
        <div class="flex justify-between items-center mb-3">
            <h3 class="font-bold">📅 Turni settimana</h3>
            <button @click="addShift" class="px-3 py-1.5 rounded-lg btn-primary text-xs">+ Turno</button>
        </div>
        <div class="grid grid-cols-7 gap-2 text-xs">
            <template x-for="d in weekDays()" :key="d">
                <div>
                    <div class="font-semibold mb-1 text-center" x-text="fmtDay(d)"></div>
                    <template x-for="s in shiftsByDay(d)" :key="s.id">
                        <div class="p-2 mb-1 rounded-lg bg-brand-500/20 border border-brand-500/30">
                            <div class="font-semibold" x-text="s.name"></div>
                            <div class="text-slate-300" x-text="s.start_time+'–'+s.end_time"></div>
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </div>

    <!-- Modal user -->
    <div x-show="modal" x-cloak @click.self="modal=null" class="fixed inset-0 z-50 bg-black/70 flex items-center justify-center p-4 overflow-y-auto">
        <div class="card p-6 w-full max-w-lg my-auto">
            <h3 class="text-lg font-bold mb-4">Dipendente</h3>
            <div class="grid grid-cols-2 gap-3">
                <div class="col-span-2"><label class="text-xs text-slate-400">Nome completo</label><input x-model="modal.name" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10"></div>
                <div><label class="text-xs text-slate-400">Email</label><input type="email" x-model="modal.email" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10"></div>
                <div><label class="text-xs text-slate-400">Ruolo</label><select x-model="modal.role" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10"><option value="admin">Admin</option><option value="manager">Manager</option><option value="cassiere">Cassiere</option><option value="cameriere">Cameriere</option><option value="cucina">Cucina</option><option value="bar">Bar</option><option value="magazziniere">Magazziniere</option></select></div>
                <div><label class="text-xs text-slate-400">Telefono</label><input x-model="modal.phone" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10"></div>
                <div><label class="text-xs text-slate-400">PIN (4 cifre)</label><input x-model="modal.pin" maxlength="4" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10"></div>
                <div><label class="text-xs text-slate-400">Stipendio</label><input type="number" step="0.01" x-model.number="modal.salary" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10"></div>
                <div><label class="text-xs text-slate-400">€/ora</label><input type="number" step="0.01" x-model.number="modal.hourly_rate" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10"></div>
                <div class="col-span-2"><label class="text-xs text-slate-400">Password (lascia vuoto per non cambiare)</label><input type="password" x-model="modal.password" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10"></div>
                <div class="col-span-2 flex items-center gap-2"><input type="checkbox" x-model="modal.active" :true-value="1" :false-value="0"> <label>Attivo</label></div>
                <div class="col-span-2 flex gap-2 mt-3">
                    <button @click="save" class="flex-1 btn-primary py-2.5 rounded-lg font-semibold">Salva</button>
                    <button @click="modal=null" class="px-4 py-2.5 rounded-lg bg-white/5">Annulla</button>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
function staffPage(){return {
    users:[], shifts:[], modal:null,
    async load(){
        const u=await fetch('/api/staff.php?action=list');this.users=(await u.json()).users;
        const s=await fetch('/api/staff.php?action=shifts');this.shifts=(await s.json()).shifts;
    },
    edit(u){this.modal={...u,salary:parseFloat(u.salary||0),hourly_rate:parseFloat(u.hourly_rate||0),active:u.active??1,password:''};},
    async save(){await fetch('/api/staff.php?action=save',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(this.modal)});this.modal=null;this.load();},
    weekDays(){const d=new Date();const m=new Date(d.setDate(d.getDate()-d.getDay()+1));return [...Array(7)].map((_,i)=>{const x=new Date(m);x.setDate(m.getDate()+i);return x.toISOString().slice(0,10);});},
    fmtDay(d){return new Date(d).toLocaleDateString('it-IT',{weekday:'short',day:'2-digit'});},
    shiftsByDay(d){return this.shifts.filter(s=>s.date===d);},
    async addShift(){const uid=prompt('ID dipendente?');if(!uid)return;const date=prompt('Data (YYYY-MM-DD)?');const st=prompt('Inizio (HH:MM)?');const en=prompt('Fine (HH:MM)?');await fetch('/api/staff.php?action=save_shift',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({user_id:uid,date,start_time:st,end_time:en})});this.load();}
}}
</script>
<?php layout_mobile_nav('staff'); layout_foot(); ?>
