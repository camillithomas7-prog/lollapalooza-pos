<?php layout_head('Transfer'); layout_sidebar('transfers'); layout_topbar('Transfer', 'Pickup e drop-off clienti'); ?>
<main class="md:ml-64 lg:ml-72 p-4 md:p-8 pb-24 md:pb-8" x-data="transfersPage()" x-init="init()">

    <!-- Stats oggi -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
        <div class="card p-4">
            <div class="text-xs text-slate-400 uppercase tracking-wider">Oggi</div>
            <div class="text-2xl font-bold mt-1" x-text="stats.total||0"></div>
            <div class="text-xs text-slate-500">corse totali</div>
        </div>
        <div class="card p-4 border-l-4" style="border-left-color:#f59e0b">
            <div class="text-xs text-slate-400 uppercase tracking-wider">In attesa</div>
            <div class="text-2xl font-bold mt-1 text-amber-400" x-text="stats.pending||0"></div>
            <div class="text-xs text-slate-500">da prendere</div>
        </div>
        <div class="card p-4 border-l-4" style="border-left-color:#8b5cf6">
            <div class="text-xs text-slate-400 uppercase tracking-wider">In corso</div>
            <div class="text-2xl font-bold mt-1 text-brand-400" x-text="stats.in_progress||0"></div>
            <div class="text-xs text-slate-500">cliente a bordo</div>
        </div>
        <div class="card p-4 border-l-4" style="border-left-color:#10b981">
            <div class="text-xs text-slate-400 uppercase tracking-wider">Completate</div>
            <div class="text-2xl font-bold mt-1 text-emerald-400" x-text="stats.completed||0"></div>
            <div class="text-xs text-slate-500">consegnate</div>
        </div>
    </div>

    <!-- Link autista -->
    <div class="card p-4 mb-5">
        <div class="flex items-start gap-3 flex-wrap">
            <div class="flex-1 min-w-[260px]">
                <div class="text-sm font-bold mb-1">🚗 Link per l'autista</div>
                <div class="text-xs text-slate-400 mb-2">Condividi questo link via WhatsApp con il tuo autista. Vedrà solo i transfer assegnati, senza login.</div>
                <div class="flex gap-2 flex-wrap">
                    <input :value="driverUrl" readonly class="flex-1 min-w-[200px] px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-xs font-mono" @click="$event.target.select()">
                    <button @click="copyLink" class="px-3 py-2 rounded-lg bg-white/5 hover:bg-white/10 text-xs font-semibold" x-text="copied?'✓ Copiato':'📋 Copia'"></button>
                    <a :href="waShare" target="_blank" class="px-3 py-2 rounded-lg bg-emerald-500/20 text-emerald-300 hover:bg-emerald-500/30 text-xs font-semibold">💬 WhatsApp</a>
                    <button @click="regenToken" class="px-3 py-2 rounded-lg bg-rose-500/10 text-rose-300 hover:bg-rose-500/20 text-xs">🔄 Rigenera</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtri + azioni -->
    <div class="flex flex-wrap items-center gap-3 mb-4">
        <input type="date" x-model="from" @change="load" class="px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-sm">
        <span class="text-slate-500 text-sm">→</span>
        <input type="date" x-model="to" @change="load" class="px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-sm">
        <select x-model="filterStatus" class="px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-sm">
            <option value="">Tutti gli stati</option>
            <option value="scheduled">Programmati</option>
            <option value="on_way">In viaggio</option>
            <option value="picked_up">Cliente a bordo</option>
            <option value="completed">Completati</option>
            <option value="cancelled">Annullati</option>
            <option value="no_show">No-show</option>
        </select>
        <select x-model="filterDir" class="px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-sm">
            <option value="">Tutte le direzioni</option>
            <option value="arrival">Arrivo (da aeroporto)</option>
            <option value="departure">Partenza (verso aeroporto)</option>
            <option value="internal">Interno (hotel ↔ locale)</option>
        </select>
        <button @click="add" class="ml-auto px-4 py-2 rounded-lg btn-primary text-sm font-semibold">+ Nuovo transfer</button>
    </div>

    <!-- Lista -->
    <div class="space-y-2">
        <template x-for="t in filteredTransfers" :key="t.id">
            <div class="card p-4">
                <div class="flex flex-wrap items-start gap-3">
                    <div class="text-center w-20 flex-shrink-0">
                        <div class="text-xs text-slate-400" x-text="fmtDayName(t.pickup_when)"></div>
                        <div class="text-2xl font-bold" x-text="fmtDay(t.pickup_when)"></div>
                        <div class="text-sm font-semibold" :class="isPast(t.pickup_when)?'text-rose-400':'text-brand-400'" x-text="fmtTime(t.pickup_when)"></div>
                    </div>
                    <div class="flex-1 min-w-[200px]">
                        <div class="flex items-center gap-2 flex-wrap mb-1">
                            <span class="font-bold" x-text="t.customer_name"></span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase" :class="dirClass(t.direction)" x-text="dirLabel(t.direction)"></span>
                            <span x-show="t.flight_no" class="px-2 py-0.5 rounded text-[10px] bg-sky-500/15 text-sky-300 font-mono" x-text="'✈ '+t.flight_no"></span>
                        </div>
                        <div class="text-xs text-slate-400 mb-1">
                            <span x-show="t.phone" x-text="'📞 '+t.phone"></span>
                            <span x-show="t.passengers" x-text="' · 👤×'+t.passengers"></span>
                            <span x-show="t.luggage" x-text="' · 🧳×'+t.luggage"></span>
                        </div>
                        <div class="text-sm">
                            <span class="text-emerald-300" x-text="'📍 '+(t.pickup_location||t.pickup_address||'—')"></span>
                            <span class="text-slate-500" x-show="t.dropoff_location||t.dropoff_address"> → </span>
                            <span class="text-rose-300" x-show="t.dropoff_location||t.dropoff_address" x-text="'🏁 '+(t.dropoff_location||t.dropoff_address||'')"></span>
                        </div>
                        <div x-show="t.notes" class="text-xs text-amber-300 mt-1" x-text="'📝 '+t.notes"></div>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <select :value="t.status" @change="setStatus(t,$event.target.value)" class="px-2 py-1.5 rounded-lg border text-xs font-semibold" :class="statusClass(t.status)">
                            <option value="scheduled">Programmato</option>
                            <option value="on_way">In viaggio</option>
                            <option value="picked_up">Cliente a bordo</option>
                            <option value="completed">Completato</option>
                            <option value="cancelled">Annullato</option>
                            <option value="no_show">No-show</option>
                        </select>
                        <button @click="edit(t)" class="text-brand-400 hover:text-brand-300 text-lg" title="Modifica">✏️</button>
                        <button @click="del(t)" class="text-rose-400 hover:text-rose-300 text-lg" title="Elimina">🗑️</button>
                    </div>
                </div>
            </div>
        </template>
        <div x-show="!filteredTransfers.length" class="text-center py-12 text-slate-400">
            <div class="text-5xl mb-2">🚗</div>
            <div>Nessun transfer nel periodo selezionato</div>
            <button @click="add" class="mt-3 px-4 py-2 rounded-lg btn-primary text-sm font-semibold">+ Crea il primo transfer</button>
        </div>
    </div>

    <!-- Modal nuovo/modifica -->
    <div x-show="modal" x-cloak @click.self="modal=null" class="fixed inset-0 z-50 bg-black/70 flex items-center justify-center p-4 overflow-y-auto">
        <div class="card p-6 w-full max-w-2xl my-8">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold" x-text="modal?.id?'Modifica transfer':'Nuovo transfer'"></h3>
                <button @click="modal=null" class="text-slate-400 hover:text-white text-xl">✕</button>
            </div>
            <div class="space-y-3" x-show="modal">
                <!-- Cliente -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                    <input x-model="modal.customer_name" placeholder="Nome cliente *" class="px-3 py-2 rounded-lg bg-white/5 border border-white/10">
                    <input x-model="modal.phone" placeholder="Telefono (+39...)" class="px-3 py-2 rounded-lg bg-white/5 border border-white/10">
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <select x-model="modal.language" class="px-3 py-2 rounded-lg bg-white/5 border border-white/10">
                        <option value="it">🇮🇹 Italiano</option>
                        <option value="en">🇬🇧 English</option>
                        <option value="es">🇪🇸 Español</option>
                        <option value="fr">🇫🇷 Français</option>
                        <option value="de">🇩🇪 Deutsch</option>
                        <option value="ar">🇸🇦 العربية</option>
                    </select>
                    <select x-model="modal.direction" class="px-3 py-2 rounded-lg bg-white/5 border border-white/10">
                        <option value="arrival">🛬 Arrivo (da aeroporto)</option>
                        <option value="departure">🛫 Partenza (verso aeroporto)</option>
                        <option value="internal">🏨 Interno (hotel ↔ locale)</option>
                    </select>
                </div>

                <!-- Data/ora pickup -->
                <div>
                    <label class="text-xs text-slate-400 uppercase tracking-wider">Quando andarlo a prendere *</label>
                    <input type="datetime-local" x-model="modal.pickup_when" class="w-full mt-1 px-3 py-2 rounded-lg bg-white/5 border border-white/10">
                </div>

                <!-- Pickup -->
                <div class="card p-3 bg-emerald-500/5 border-emerald-500/20">
                    <div class="text-xs text-emerald-300 font-bold uppercase tracking-wider mb-2">📍 Punto di ritiro</div>
                    <input x-model="modal.pickup_location" placeholder="Nome luogo (es. Aeroporto SSH, Hotel Marriott)" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 mb-2">
                    <input x-model="modal.pickup_address" placeholder="Indirizzo completo (per Maps)" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10">
                </div>

                <!-- Dropoff -->
                <div class="card p-3 bg-rose-500/5 border-rose-500/20">
                    <div class="text-xs text-rose-300 font-bold uppercase tracking-wider mb-2">🏁 Destinazione</div>
                    <input x-model="modal.dropoff_location" placeholder="Nome luogo (default: Lollapalooza)" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 mb-2">
                    <input x-model="modal.dropoff_address" placeholder="Indirizzo (opzionale)" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10">
                </div>

                <!-- Dettagli pratici -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                    <div>
                        <label class="text-xs text-slate-400">Passeggeri</label>
                        <input type="number" min="1" x-model.number="modal.passengers" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10">
                    </div>
                    <div>
                        <label class="text-xs text-slate-400">Bagagli</label>
                        <input type="number" min="0" x-model.number="modal.luggage" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10">
                    </div>
                    <div>
                        <label class="text-xs text-slate-400">Volo</label>
                        <input x-model="modal.flight_no" placeholder="es. MS794" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 font-mono">
                    </div>
                    <div>
                        <label class="text-xs text-slate-400">Prezzo (LE)</label>
                        <input type="number" min="0" step="50" x-model.number="modal.price_egp" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                    <div>
                        <label class="text-xs text-slate-400">Veicolo (opzionale)</label>
                        <input x-model="modal.vehicle" placeholder="es. Hyundai H1 bianco" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10">
                    </div>
                    <div>
                        <label class="text-xs text-slate-400">Autista (opzionale)</label>
                        <input x-model="modal.driver_name" placeholder="Nome autista" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10">
                    </div>
                </div>

                <div>
                    <label class="text-xs text-slate-400">Note</label>
                    <textarea x-model="modal.notes" rows="2" placeholder="Es. cliente parla solo inglese, viaggia con bambino piccolo" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10"></textarea>
                </div>

                <div class="flex gap-2 mt-4">
                    <button @click="save" class="flex-1 btn-primary py-2.5 rounded-lg font-semibold">💾 Salva transfer</button>
                    <button @click="modal=null" class="px-4 py-2.5 rounded-lg bg-white/5 hover:bg-white/10">Annulla</button>
                </div>
            </div>
        </div>
    </div>

</main>
<script>
function transfersPage(){return {
    from: new Date().toISOString().slice(0,10),
    to: new Date(Date.now()+14*86400000).toISOString().slice(0,10),
    filterStatus: '', filterDir: '',
    transfers: [], stats: {}, modal: null,
    driverUrl: '', token: '', copied: false,
    get waShare(){ return 'https://wa.me/?text=' + encodeURIComponent('Ciao, ecco il link con i transfer che devi gestire: ' + this.driverUrl); },
    get filteredTransfers(){
        return this.transfers.filter(t => {
            if (this.filterStatus && t.status !== this.filterStatus) return false;
            if (this.filterDir && t.direction !== this.filterDir) return false;
            return true;
        });
    },
    async init(){ await this.load(); },
    async load(){
        const r = await fetch(`/api/transfers.php?action=list&from=${this.from}&to=${this.to}`);
        const d = await r.json();
        this.transfers = d.transfers || [];
        this.stats = d.stats || {};
        this.token = d.token;
        const base = location.origin;
        this.driverUrl = base + '/index.php?p=driver&t=' + this.token;
    },
    add(){
        const now = new Date(); now.setMinutes(0); now.setHours(now.getHours()+2);
        this.modal = {
            customer_name: '', phone: '', language: 'it', direction: 'arrival',
            pickup_when: now.toISOString().slice(0,16),
            pickup_location: '', pickup_address: '',
            dropoff_location: 'Lollapalooza', dropoff_address: 'Al Motelat, Sharm el Sheikh 2',
            passengers: 2, luggage: 2, flight_no: '', vehicle: '', driver_name: '',
            price_egp: 0, notes: '', status: 'scheduled'
        };
    },
    edit(t){
        this.modal = {...t, pickup_when: (t.pickup_when||'').replace(' ','T').slice(0,16)};
    },
    async save(){
        if (!this.modal.customer_name?.trim()){ alert('Nome cliente obbligatorio'); return; }
        if (!this.modal.pickup_when){ alert('Data/ora pickup obbligatoria'); return; }
        const payload = {...this.modal, pickup_when: this.modal.pickup_when.replace('T',' ') + (this.modal.pickup_when.length===16?':00':'')};
        await fetch('/api/transfers.php?action=save', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)});
        this.modal = null; this.load();
    },
    async setStatus(t, s){
        await fetch('/api/transfers.php?action=set_status', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({id:t.id, status:s})});
        this.load();
    },
    async del(t){
        if (!confirm(`Eliminare il transfer di ${t.customer_name}?`)) return;
        await fetch('/api/transfers.php?action=delete', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({id:t.id})});
        this.load();
    },
    async copyLink(){
        try { await navigator.clipboard.writeText(this.driverUrl); this.copied = true; setTimeout(()=>this.copied=false, 1500); }
        catch(e){ alert('Copia non riuscita: ' + this.driverUrl); }
    },
    async regenToken(){
        if (!confirm('Rigenerare il link autista? Il vecchio link smetterà di funzionare.')) return;
        const r = await fetch('/api/transfers.php?action=regenerate_token', {method:'POST', headers:{'Content-Type':'application/json'}});
        const d = await r.json();
        this.token = d.token; this.driverUrl = d.url;
    },
    // Helpers
    fmtDay(s){ return s ? new Date(s.replace(' ','T')).getDate() : ''; },
    fmtDayName(s){ return s ? new Date(s.replace(' ','T')).toLocaleDateString('it-IT',{weekday:'short'}) : ''; },
    fmtTime(s){ return s ? new Date(s.replace(' ','T')).toLocaleTimeString('it-IT',{hour:'2-digit',minute:'2-digit'}) : ''; },
    isPast(s){ return s ? new Date(s.replace(' ','T')) < new Date() : false; },
    dirLabel(d){ return ({arrival:'🛬 Arrivo', departure:'🛫 Partenza', internal:'🏨 Interno'})[d] || d; },
    dirClass(d){ return ({arrival:'bg-emerald-500/15 text-emerald-300', departure:'bg-sky-500/15 text-sky-300', internal:'bg-brand-500/15 text-brand-400'})[d] || 'bg-white/5'; },
    statusClass(s){ return ({
        scheduled:'bg-white/5 border-white/10',
        on_way:'bg-amber-500/15 border-amber-500/30 text-amber-300',
        picked_up:'bg-brand-500/15 border-brand-500/30 text-brand-300',
        completed:'bg-emerald-500/15 border-emerald-500/30 text-emerald-300',
        cancelled:'bg-rose-500/15 border-rose-500/30 text-rose-300',
        no_show:'bg-rose-500/15 border-rose-500/30 text-rose-300'
    })[s] || 'bg-white/5 border-white/10'; }
}}
</script>
<?php layout_mobile_nav('transfers'); layout_foot(); ?>
