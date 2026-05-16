<?php layout_head('Ordini'); layout_sidebar('orders'); layout_topbar('Ordini', 'Tutti gli ordini'); ?>
<main class="md:ml-64 lg:ml-72 p-4 md:p-8 pb-24 md:pb-8" x-data="ordersList()" x-init="init()">

    <div class="flex flex-wrap gap-2 mb-4 items-center">
        <button @click="filter='active'; load()" :class="filter==='active'?'btn-primary':'bg-white/5'" class="px-4 py-2 rounded-xl text-sm font-medium">Attivi</button>
        <button @click="filter='today'; load()" :class="filter==='today'?'btn-primary':'bg-white/5'" class="px-4 py-2 rounded-xl text-sm font-medium">Oggi</button>
        <button @click="filter='all'; load()" :class="filter==='all'?'btn-primary':'bg-white/5'" class="px-4 py-2 rounded-xl text-sm font-medium">Tutti</button>
        <button @click="openNewOrder" class="ml-auto px-4 py-2 rounded-xl btn-primary text-sm font-semibold">+ Nuovo ordine al banco</button>
    </div>

    <div class="card overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="text-xs uppercase text-slate-400 border-b border-white/5">
                <tr>
                    <th class="text-left p-3">Codice</th>
                    <th class="text-left p-3">Tavolo</th>
                    <th class="text-left p-3">Cameriere</th>
                    <th class="text-left p-3">Gift card</th>
                    <th class="text-left p-3">Stato</th>
                    <th class="text-left p-3">Ora</th>
                    <th class="text-right p-3">Totale</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <template x-for="o in orders" :key="o.id">
                    <tr class="border-t border-white/5 hover:bg-white/5">
                        <td class="p-3 font-mono text-xs" x-text="o.code"></td>
                        <td class="p-3" x-text="o.table_code || o.type"></td>
                        <td class="p-3 text-slate-400" x-text="o.waiter_name || '—'"></td>
                        <td class="p-3">
                            <span x-show="o.gift_card_id" class="text-xs px-2 py-1 rounded bg-amber-500/15 text-amber-300 font-mono" x-text="'-'+(parseFloat(o.discount_percent||0).toFixed(0))+'%'"></span>
                            <span x-show="!o.gift_card_id" class="text-xs text-slate-500">—</span>
                        </td>
                        <td class="p-3"><span class="text-xs px-2 py-1 rounded" :class="badge(o.status)" x-text="label(o.status)"></span></td>
                        <td class="p-3 text-xs text-slate-400" x-text="fmtTime(o.created_at)"></td>
                        <td class="p-3 text-right font-semibold" x-text="fmt(o.total)"></td>
                        <td class="p-3"><a :href="`/index.php?p=order_view&id=${o.id}`" class="text-brand-400 hover:text-brand-300">Apri →</a></td>
                    </tr>
                </template>
            </tbody>
        </table>
        <div x-show="!orders.length" class="p-12 text-center text-slate-400">Nessun ordine</div>
    </div>

    <!-- Modal Nuovo ordine al banco -->
    <div x-show="newModal" x-cloak @click.self="newModal=false" class="fixed inset-0 z-50 bg-black/70 flex items-center justify-center p-4 overflow-y-auto">
        <div class="card p-6 w-full max-w-lg my-8">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold">🍹 Nuovo ordine al banco</h3>
                <button @click="newModal=false" class="text-slate-400 hover:text-white text-xl">✕</button>
            </div>

            <div class="space-y-4">
                <!-- Tipo -->
                <div>
                    <label class="text-xs text-slate-400 uppercase tracking-wider mb-1 block">Tipo ordine</label>
                    <div class="grid grid-cols-3 gap-2">
                        <button @click="newForm.type='bar'" :class="newForm.type==='bar'?'bg-brand-500/20 ring-2 ring-brand-500/40':'bg-white/5'" class="py-2 rounded-lg text-sm font-semibold">🍸 Bar / Banco</button>
                        <button @click="newForm.type='takeaway'" :class="newForm.type==='takeaway'?'bg-brand-500/20 ring-2 ring-brand-500/40':'bg-white/5'" class="py-2 rounded-lg text-sm font-semibold">🥡 Take-away</button>
                        <button @click="newForm.type='dine_in'" :class="newForm.type==='dine_in'?'bg-brand-500/20 ring-2 ring-brand-500/40':'bg-white/5'" class="py-2 rounded-lg text-sm font-semibold">🪑 Tavolo</button>
                    </div>
                </div>

                <!-- Tavolo (solo se dine_in) -->
                <div x-show="newForm.type==='dine_in'">
                    <label class="text-xs text-slate-400 uppercase tracking-wider mb-1 block">Tavolo</label>
                    <select x-model="newForm.table_id" class="w-full px-3 py-2.5 rounded-lg bg-white/5 border border-white/10">
                        <option :value="null">— Seleziona —</option>
                        <template x-for="tbl in tables" :key="tbl.id">
                            <option :value="tbl.id" x-text="`${tbl.code} · ${tbl.seats} posti`"></option>
                        </template>
                    </select>
                </div>

                <!-- Gift card (autocomplete) -->
                <div class="card p-4 bg-amber-500/5 border-amber-500/20">
                    <label class="text-xs text-amber-300 font-bold uppercase tracking-wider mb-2 block">🎁 Applica gift card (opzionale)</label>
                    <div class="relative">
                        <input x-model="gcSearch" @input.debounce.300ms="searchGc" @focus="searchGc" placeholder="Cerca per nome cliente o codice (LOL-...)" class="w-full px-3 py-2.5 rounded-lg bg-white/5 border border-white/10 font-mono">
                        <div x-show="gcResults.length && gcOpen" class="absolute z-10 left-0 right-0 top-full mt-1 card max-h-72 overflow-y-auto">
                            <template x-for="g in gcResults" :key="g.id">
                                <button type="button" @click="pickGc(g)" class="w-full text-left p-3 hover:bg-white/5 border-b border-white/5">
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono text-xs text-amber-300" x-text="g.code"></span>
                                        <span x-show="g.valid_now" class="text-[10px] px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-300 font-bold">VALIDA ORA</span>
                                        <span x-show="!g.valid_now" class="text-[10px] px-2 py-0.5 rounded bg-slate-500/20 text-slate-400">non valida ora</span>
                                    </div>
                                    <div class="font-semibold mt-0.5" x-text="g.customer_name"></div>
                                    <div class="text-xs text-slate-400" x-text="g.window_label"></div>
                                </button>
                            </template>
                        </div>
                    </div>
                    <div x-show="gcPicked" class="mt-3 p-3 rounded-lg bg-emerald-500/10 border border-emerald-500/30">
                        <div class="text-xs text-emerald-300 uppercase tracking-wider font-bold">Selezionata</div>
                        <div class="font-bold mt-1" x-text="gcPicked?.customer_name"></div>
                        <div class="text-xs font-mono text-emerald-200" x-text="gcPicked?.code"></div>
                        <div class="text-xs text-slate-400" x-text="gcPicked?.window_label"></div>
                        <button @click="gcPicked=null" class="mt-2 text-xs text-rose-400 hover:text-rose-300">✕ Rimuovi</button>
                    </div>
                </div>

                <div class="text-xs text-slate-500">
                    Dopo aver creato l'ordine verrai portato alla pagina di gestione per aggiungere i prodotti.
                </div>

                <div class="flex gap-2">
                    <button @click="createOrder" :disabled="creating" class="flex-1 btn-primary py-2.5 rounded-lg font-semibold disabled:opacity-50">
                        <span x-show="!creating">Crea ordine →</span>
                        <span x-show="creating">Creazione…</span>
                    </button>
                    <button @click="newModal=false" class="px-4 py-2.5 rounded-lg bg-white/5">Annulla</button>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
function ordersList(){return {
    filter:'active',
    orders: [],
    tables: [],
    newModal: false,
    newForm: { type:'bar', table_id:null },
    gcSearch: '', gcResults: [], gcOpen: false, gcPicked: null,
    creating: false,
    async init(){
        this.load();
        setInterval(()=>this.load(), 8000);
        // pre-load tavoli liberi
        try {
            const r = await fetch('/api/tables.php?action=list');
            const d = await r.json();
            this.tables = (d.tables || []).filter(t => t.status !== 'occupied');
        } catch(e){}
    },
    async load(){
        const p = this.filter==='active' ? 'active=1'
                : this.filter==='today'  ? 'date=' + new Date().toISOString().slice(0,10)
                : '';
        const r = await fetch('/api/orders.php?action=list&' + p);
        this.orders = (await r.json()).orders || [];
    },
    openNewOrder(){
        this.newModal = true;
        this.newForm = { type:'bar', table_id:null };
        this.gcSearch = ''; this.gcResults = []; this.gcOpen = false; this.gcPicked = null;
    },
    async searchGc(){
        const q = this.gcSearch.trim();
        const r = await fetch('/api/gift_cards.php?action=search&q=' + encodeURIComponent(q));
        const d = await r.json();
        this.gcResults = d.gift_cards || [];
        this.gcOpen = true;
    },
    pickGc(g){
        this.gcPicked = g;
        this.gcOpen = false;
        this.gcSearch = '';
    },
    async createOrder(){
        if (this.newForm.type === 'dine_in' && !this.newForm.table_id){
            alert('Seleziona un tavolo'); return;
        }
        if (this.gcPicked && !this.gcPicked.valid_now){
            if (!confirm('La gift card selezionata NON è valida in questo momento. Continuare comunque?')) return;
        }
        this.creating = true;
        try {
            const r = await fetch('/api/orders.php?action=quick_create', {
                method:'POST', headers:{'Content-Type':'application/json'},
                body: JSON.stringify({ type: this.newForm.type, table_id: this.newForm.table_id })
            });
            const d = await r.json();
            if (!d.ok){ alert(d.error || 'Errore'); return; }
            // se gift card selezionata, applico SUBITO (ma con discount 0 perché items ancora vuoti — l'utente la potrà ri-applicare in order_view dopo)
            // Per ora salvo lo stato e lasciamo l'utente applicarla in order_view
            const url = `/index.php?p=order_view&id=${d.order_id}` + (this.gcPicked ? `&apply_gc=${encodeURIComponent(this.gcPicked.code)}` : '');
            location.href = url;
        } catch(e){ alert('Errore: '+e.message); }
        finally { this.creating = false; }
    },
    fmt(v){ return 'LE '+(parseFloat(v||0)).toLocaleString('it-IT',{minimumFractionDigits:2}); },
    fmtTime(s){ try{ return new Date(s).toLocaleString('it-IT',{day:'2-digit',month:'2-digit',hour:'2-digit',minute:'2-digit'}); }catch(e){ return '' } },
    badge(s){ return {open:'bg-slate-500/20', sent:'bg-amber-500/20 text-amber-300', preparing:'bg-orange-500/20 text-orange-300', ready:'bg-sky-500/20 text-sky-300', served:'bg-emerald-500/20 text-emerald-300', closed:'bg-emerald-500/20 text-emerald-300', cancelled:'bg-rose-500/20 text-rose-300'}[s]||'bg-slate-500/20'; },
    label(s){ return {open:'Aperto', sent:'Inviato', preparing:'In prep.', ready:'Pronto', served:'Servito', closed:'Chiuso', cancelled:'Annull.'}[s]||s; }
}}
</script>
<?php layout_mobile_nav('orders'); layout_foot(); ?>
