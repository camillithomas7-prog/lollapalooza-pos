<?php layout_head('Eventi'); layout_sidebar('events'); layout_topbar('Eventi', 'Calendario serate e eventi a tema'); ?>
<main class="md:ml-64 lg:ml-72 p-4 md:p-8 pb-24 md:pb-8" x-data="eventsPage()" x-init="init()">

    <!-- Navigazione settimana -->
    <div class="flex flex-wrap items-center gap-3 mb-4">
        <button @click="shiftWeek(-1)" class="px-3 py-2 rounded-lg bg-white/5 hover:bg-white/10 text-sm">← Settimana prec.</button>
        <button @click="goToday" class="px-3 py-2 rounded-lg bg-white/5 hover:bg-white/10 text-sm">Oggi</button>
        <button @click="shiftWeek(1)" class="px-3 py-2 rounded-lg bg-white/5 hover:bg-white/10 text-sm">Settimana succ. →</button>
        <div class="text-sm text-slate-300 font-semibold ml-2" x-text="weekLabel()"></div>
        <button @click="add(false)" class="ml-auto px-4 py-2 rounded-lg btn-primary text-sm font-semibold">+ Nuovo evento</button>
    </div>

    <!-- Vista settimanale -->
    <div class="grid grid-cols-1 md:grid-cols-7 gap-3 mb-8">
        <template x-for="(day, idx) in week" :key="day.date">
            <div class="card p-3 min-h-[140px]" :class="isToday(day.date) ? 'ring-2 ring-brand-400/60' : ''">
                <div class="flex items-center justify-between mb-2">
                    <div>
                        <div class="text-xs uppercase text-slate-400 tracking-wider" x-text="dayName(day.date)"></div>
                        <div class="text-xl font-bold" x-text="new Date(day.date).getDate()"></div>
                    </div>
                    <button @click="addOn(day)" class="text-brand-400 hover:text-brand-300 text-lg font-bold" title="Aggiungi per questo giorno">+</button>
                </div>
                <div class="space-y-2">
                    <template x-for="ev in day.events" :key="ev.id + '-' + day.date">
                        <button @click="edit(ev)" class="w-full text-left p-2 rounded-lg hover:scale-[1.02] transition" :class="colorBg(ev.color)">
                            <div class="flex items-center gap-1 text-[10px] uppercase font-bold tracking-wider opacity-80">
                                <span x-text="catIcon(ev.category)"></span>
                                <span x-show="ev.is_recurring" title="Ricorrente">🔁</span>
                                <span x-text="(ev.start_time||'').slice(0,5)"></span>
                            </div>
                            <div class="font-semibold text-sm mt-0.5 leading-tight" x-text="ev.title"></div>
                            <div x-show="ev.description" class="text-xs opacity-75 mt-0.5 leading-tight" x-text="ev.description"></div>
                        </button>
                    </template>
                    <div x-show="!day.events.length" class="text-xs text-slate-500 text-center py-2">—</div>
                </div>
            </div>
        </template>
    </div>

    <!-- Tutti gli eventi configurati -->
    <div class="card p-4">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-bold">Tutti gli eventi</h3>
            <div class="text-xs text-slate-400" x-text="all.length + ' eventi'"></div>
        </div>
        <div class="space-y-2">
            <template x-for="ev in all" :key="ev.id">
                <div class="flex flex-wrap items-center gap-3 p-3 rounded-lg" :class="ev.active ? 'bg-white/5' : 'bg-white/[0.02] opacity-50'">
                    <div class="text-2xl" x-text="catIcon(ev.category)"></div>
                    <div class="flex-1 min-w-[200px]">
                        <div class="font-semibold" x-text="ev.title"></div>
                        <div class="text-xs text-slate-400" x-text="recurrenceLabel(ev)"></div>
                    </div>
                    <div class="text-xs text-slate-400" x-text="(ev.start_time||'').slice(0,5)+' → '+(ev.end_time||'').slice(0,5)"></div>
                    <button @click="toggle(ev)" class="text-xs px-2 py-1 rounded" :class="ev.active?'bg-emerald-500/20 text-emerald-300':'bg-slate-500/20 text-slate-400'" x-text="ev.active ? 'Attivo' : 'Sospeso'"></button>
                    <button @click="edit(ev)" class="text-brand-400" title="Modifica">✏️</button>
                    <button @click="del(ev)" class="text-red-400 hover:text-red-300" title="Elimina">🗑️</button>
                </div>
            </template>
            <div x-show="!all.length" class="text-center py-8 text-slate-400 text-sm">Nessun evento configurato</div>
        </div>
    </div>

    <!-- Modal evento -->
    <div x-show="modal" x-cloak @click.self="modal=null" class="fixed inset-0 z-50 bg-black/70 flex items-center justify-center p-4 overflow-y-auto">
        <div class="card p-6 w-full max-w-xl my-8">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold" x-text="modal && modal.id ? 'Modifica evento' : 'Nuovo evento'"></h3>
                <button @click="modal=null" class="text-slate-400 hover:text-white text-xl">✕</button>
            </div>
            <template x-if="modal">
            <div class="space-y-3">
                <div>
                    <label class="text-xs text-slate-400 uppercase tracking-wider mb-1 block">Titolo</label>
                    <input x-model="modal.title" placeholder="es. Serata Latino" class="w-full px-3 py-2.5 rounded-lg bg-white/5 border border-white/10">
                </div>

                <div>
                    <label class="text-xs text-slate-400 uppercase tracking-wider mb-1 block">Descrizione</label>
                    <textarea x-model="modal.description" rows="2" placeholder="Dettagli, artista, particolari…" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10"></textarea>
                </div>

                <div>
                    <label class="text-xs text-slate-400 uppercase tracking-wider mb-1 block">Tipo</label>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        <template x-for="c in categories" :key="c.k">
                            <button type="button" @click="modal.category=c.k" :class="modal.category===c.k?'bg-brand-500/30 ring-2 ring-brand-500/50':'bg-white/5'" class="py-2 rounded-lg text-sm">
                                <span x-text="c.icon"></span> <span x-text="c.label"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <div>
                    <label class="text-xs text-slate-400 uppercase tracking-wider mb-1 block">Ricorrenza</label>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" @click="modal.is_recurring=true" :class="modal.is_recurring?'bg-brand-500/30 ring-2 ring-brand-500/50':'bg-white/5'" class="py-2 rounded-lg text-sm">🔁 Ogni settimana</button>
                        <button type="button" @click="modal.is_recurring=false" :class="!modal.is_recurring?'bg-brand-500/30 ring-2 ring-brand-500/50':'bg-white/5'" class="py-2 rounded-lg text-sm">📅 Data specifica</button>
                    </div>
                </div>

                <div x-show="modal.is_recurring">
                    <label class="text-xs text-slate-400 uppercase tracking-wider mb-1 block">Giorno della settimana</label>
                    <div class="grid grid-cols-7 gap-1">
                        <template x-for="(d,i) in ['Dom','Lun','Mar','Mer','Gio','Ven','Sab']" :key="i">
                            <button type="button" @click="modal.weekday=i" :class="modal.weekday===i?'bg-brand-500/30 ring-2 ring-brand-500/50':'bg-white/5'" class="py-2 rounded-lg text-xs font-semibold">
                                <span x-text="d"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <div x-show="!modal.is_recurring">
                    <label class="text-xs text-slate-400 uppercase tracking-wider mb-1 block">Data</label>
                    <input type="date" x-model="modal.event_date" class="w-full px-3 py-2.5 rounded-lg bg-white/5 border border-white/10">
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="text-xs text-slate-400 uppercase tracking-wider mb-1 block">Inizio</label>
                        <input type="time" x-model="modal.start_time" class="w-full px-3 py-2.5 rounded-lg bg-white/5 border border-white/10">
                    </div>
                    <div>
                        <label class="text-xs text-slate-400 uppercase tracking-wider mb-1 block">Fine</label>
                        <input type="time" x-model="modal.end_time" class="w-full px-3 py-2.5 rounded-lg bg-white/5 border border-white/10">
                    </div>
                </div>

                <div>
                    <label class="text-xs text-slate-400 uppercase tracking-wider mb-1 block">Colore</label>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="c in colors" :key="c">
                            <button type="button" @click="modal.color=c" :class="modal.color===c?'ring-2 ring-white':''" class="w-8 h-8 rounded-full" :style="{ background: colorHex(c) }"></button>
                        </template>
                    </div>
                </div>

                <div class="flex gap-2 pt-2">
                    <button @click="save" :disabled="saving" class="flex-1 btn-primary py-2.5 rounded-lg font-semibold disabled:opacity-50">
                        <span x-show="!saving">Salva</span>
                        <span x-show="saving">Salvataggio…</span>
                    </button>
                    <button @click="modal=null" class="px-4 py-2.5 rounded-lg bg-white/5">Annulla</button>
                </div>
            </div>
            </template>
        </div>
    </div>
</main>

<script>
function eventsPage(){return {
    start: monday(new Date()),
    week: [],
    all: [],
    modal: null,
    saving: false,
    categories: [
        { k:'tema',         label:'Tema',       icon:'🎉' },
        { k:'musica_live',  label:'Live',       icon:'🎷' },
        { k:'dj',           label:'DJ',         icon:'🎧' },
        { k:'spettacolo',   label:'Spettacolo', icon:'💃' },
    ],
    colors: ['amber','rose','emerald','sky','violet','orange','slate'],
    async init(){
        await this.load();
    },
    async load(){
        const [w, a] = await Promise.all([
            fetch('/api/events.php?action=week&start='+this.start).then(r=>r.json()),
            fetch('/api/events.php?action=list').then(r=>r.json()),
        ]);
        this.week = w.week || [];
        this.all  = a.events || [];
    },
    shiftWeek(n){
        const d = new Date(this.start); d.setDate(d.getDate() + n*7);
        this.start = d.toISOString().slice(0,10);
        this.load();
    },
    goToday(){ this.start = monday(new Date()); this.load(); },
    weekLabel(){
        if (!this.week.length) return '';
        const a = new Date(this.week[0].date), b = new Date(this.week[6].date);
        const fmt = (d)=>d.toLocaleDateString('it-IT',{day:'2-digit',month:'short'});
        return fmt(a) + ' → ' + fmt(b);
    },
    isToday(d){ return d === new Date().toISOString().slice(0,10); },
    dayName(d){ return new Date(d).toLocaleDateString('it-IT',{weekday:'short'}); },
    add(recurring){
        this.modal = { title:'', description:'', category:'tema', is_recurring:!!recurring, weekday:new Date().getDay(), event_date:new Date().toISOString().slice(0,10), start_time:'21:00', end_time:'03:00', color:'amber', notes:'', active:1 };
    },
    addOn(day){
        this.modal = { title:'', description:'', category:'tema', is_recurring:false, weekday:day.weekday, event_date:day.date, start_time:'21:00', end_time:'03:00', color:'amber', notes:'', active:1 };
    },
    edit(ev){
        this.modal = { ...ev,
            is_recurring: !!ev.is_recurring,
            start_time: (ev.start_time||'21:00:00').slice(0,5),
            end_time:   (ev.end_time||'03:00:00').slice(0,5),
        };
    },
    async save(){
        if (!this.modal.title.trim()){ alert('Titolo obbligatorio'); return; }
        this.saving = true;
        try {
            await fetch('/api/events.php?action=save', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(this.modal) });
            this.modal = null;
            await this.load();
        } finally { this.saving = false; }
    },
    async toggle(ev){
        await fetch('/api/events.php?action=toggle', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({id:ev.id}) });
        this.load();
    },
    async del(ev){
        if (!confirm('Eliminare l\'evento "'+ev.title+'"?')) return;
        await fetch('/api/events.php?action=delete', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({id:ev.id}) });
        this.load();
    },
    catIcon(c){ return ({tema:'🎉', musica_live:'🎷', dj:'🎧', spettacolo:'💃'})[c] || '🎉'; },
    recurrenceLabel(ev){
        if (ev.is_recurring){
            const names = ['Domenica','Lunedì','Martedì','Mercoledì','Giovedì','Venerdì','Sabato'];
            return 'Ogni ' + (names[ev.weekday] || '—');
        }
        return ev.event_date ? new Date(ev.event_date).toLocaleDateString('it-IT',{weekday:'long', day:'2-digit', month:'long', year:'numeric'}) : '—';
    },
    colorBg(c){ return ({
        amber:'bg-amber-500/20 text-amber-100',
        rose:'bg-rose-500/20 text-rose-100',
        emerald:'bg-emerald-500/20 text-emerald-100',
        sky:'bg-sky-500/20 text-sky-100',
        violet:'bg-violet-500/20 text-violet-100',
        orange:'bg-orange-500/20 text-orange-100',
        slate:'bg-slate-500/20 text-slate-100',
    })[c] || 'bg-amber-500/20 text-amber-100'; },
    colorHex(c){ return ({amber:'#f59e0b', rose:'#f43f5e', emerald:'#10b981', sky:'#0ea5e9', violet:'#8b5cf6', orange:'#f97316', slate:'#64748b'})[c] || '#f59e0b'; },
}}
function monday(d){
    const x = new Date(d); const day = x.getDay(); const diff = day === 0 ? -6 : 1 - day;
    x.setDate(x.getDate() + diff); return x.toISOString().slice(0,10);
}
</script>
<?php layout_mobile_nav('events'); layout_foot(); ?>
