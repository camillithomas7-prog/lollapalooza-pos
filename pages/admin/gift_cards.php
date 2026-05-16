<?php layout_head('Gift Card'); layout_sidebar('giftcards'); layout_topbar('Gift Card', 'Sconto 10% personalizzato — valido solo per la data scelta'); ?>
<main class="md:ml-64 lg:ml-72 p-4 md:p-8 pb-24 md:pb-8" x-data="gcPage()" x-init="load()">

    <!-- Stats -->
    <div class="grid grid-cols-3 gap-3 mb-5">
        <div class="card p-4">
            <div class="text-xs text-slate-400 uppercase tracking-wider">Emesse oggi</div>
            <div class="text-2xl font-bold mt-1" x-text="stats.today_issued||0"></div>
        </div>
        <div class="card p-4 border-l-4" style="border-left-color:#10b981">
            <div class="text-xs text-slate-400 uppercase tracking-wider">Usate oggi</div>
            <div class="text-2xl font-bold mt-1 text-emerald-400" x-text="stats.today_used||0"></div>
        </div>
        <div class="card p-4 border-l-4" style="border-left-color:#f59e0b">
            <div class="text-xs text-slate-400 uppercase tracking-wider">Attive in futuro</div>
            <div class="text-2xl font-bold mt-1 text-amber-400" x-text="stats.active_total||0"></div>
        </div>
    </div>

    <!-- Form genera -->
    <div class="card p-5 mb-6">
        <h3 class="text-lg font-bold mb-3">🎁 Genera nuova gift card</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
                <label class="text-xs text-slate-400 uppercase tracking-wider">Nome cliente *</label>
                <input x-model="form.customer_name" placeholder="Mario Rossi" class="w-full mt-1 px-3 py-2.5 rounded-lg bg-white/5 border border-white/10">
            </div>
            <div>
                <label class="text-xs text-slate-400 uppercase tracking-wider">Telefono (opzionale)</label>
                <input x-model="form.phone" placeholder="+39..." class="w-full mt-1 px-3 py-2.5 rounded-lg bg-white/5 border border-white/10">
            </div>
            <div>
                <label class="text-xs text-slate-400 uppercase tracking-wider">Data della visita *</label>
                <input type="date" x-model="form.valid_date" :min="today" class="w-full mt-1 px-3 py-2.5 rounded-lg bg-white/5 border border-white/10">
            </div>
        </div>
        <div class="grid grid-cols-2 gap-3 mt-3">
            <div>
                <label class="text-xs text-slate-400 uppercase tracking-wider">Valida dalle ore</label>
                <input type="time" x-model="form.valid_from_hour" class="w-full mt-1 px-3 py-2.5 rounded-lg bg-white/5 border border-white/10">
            </div>
            <div>
                <label class="text-xs text-slate-400 uppercase tracking-wider">Valida fino alle ore</label>
                <input type="time" x-model="form.valid_to_hour" class="w-full mt-1 px-3 py-2.5 rounded-lg bg-white/5 border border-white/10">
            </div>
        </div>
        <div class="mt-2 text-xs text-slate-400">
            ⏰ Orari in <b>ora egiziana (Africa/Cairo)</b>. Se l'ora di fine è inferiore all'ora di inizio
            (es. 20:00 → 03:00), la finestra di validità copre <b>anche la notte successiva</b>.
            Default: <span class="text-amber-300">20:00 → 03:00</span> (locale serale).
        </div>
        <div class="mt-4 flex gap-2">
            <button @click="create" :disabled="busy" class="px-5 py-2.5 rounded-lg btn-primary font-semibold disabled:opacity-50">
                <span x-show="!busy">🎁 Genera gift card 10%</span>
                <span x-show="busy">Generazione…</span>
            </button>
            <button @click="resetForm" class="px-4 py-2.5 rounded-lg bg-white/5 hover:bg-white/10">Annulla</button>
        </div>
    </div>

    <!-- Modal con risultato (gift card appena creata) -->
    <div x-show="result" x-cloak @click.self="result=null" class="fixed inset-0 z-50 bg-black/80 flex items-center justify-center p-4 overflow-y-auto">
        <div class="card p-5 w-full max-w-lg my-8">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-bold">✅ Gift card creata</h3>
                <button @click="result=null" class="text-slate-400 hover:text-white text-xl">✕</button>
            </div>
            <template x-if="result">
                <div>
                    <img :src="result.image_url + '&v=' + Date.now()" alt="Gift card" class="w-full rounded-xl border border-white/10 mb-4">
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between bg-white/5 p-2 rounded">
                            <span class="text-slate-400">Codice</span>
                            <span class="font-mono font-bold" x-text="result.code"></span>
                        </div>
                        <a :href="result.download_url" target="_blank" class="block w-full text-center py-2.5 rounded-lg bg-white/5 hover:bg-white/10 font-semibold">⬇️ Scarica PNG</a>
                        <a :href="result.whatsapp_url" target="_blank" class="block w-full text-center py-2.5 rounded-lg bg-emerald-500 text-emerald-950 hover:bg-emerald-400 font-bold">💬 Invia su WhatsApp</a>
                        <button @click="copyImageLink" class="block w-full py-2.5 rounded-lg bg-white/5 hover:bg-white/10 font-semibold" x-text="copied?'✓ Link copiato!':'🔗 Copia link immagine'"></button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Validazione gift card (cassa) -->
    <div class="card p-5 mb-6">
        <h3 class="text-lg font-bold mb-3">🔍 Valida gift card (cassa)</h3>
        <div class="flex flex-wrap gap-2">
            <input x-model="lookupCode" @keydown.enter="lookup" placeholder="LOL-XXXXXX-XXXX" class="flex-1 min-w-[200px] px-3 py-2.5 rounded-lg bg-white/5 border border-white/10 font-mono uppercase">
            <button @click="lookup" class="px-4 py-2.5 rounded-lg btn-primary font-semibold">Verifica</button>
        </div>
        <template x-if="lookupResult">
            <div class="mt-4 p-4 rounded-lg" :class="lookupResult.valid_today ? 'bg-emerald-500/10 border border-emerald-500/30' : 'bg-rose-500/10 border border-rose-500/30'">
                <div class="flex items-start gap-3">
                    <div class="text-3xl" x-text="lookupResult.valid_today ? '✅' : '⛔'"></div>
                    <div class="flex-1">
                        <div class="font-bold text-lg" x-text="lookupResult.gift_card?.customer_name"></div>
                        <div class="text-sm text-slate-300">
                            Sconto: <b x-text="lookupResult.gift_card?.percent + '%'"></b>
                            · Valida per: <b x-text="fmtDate(lookupResult.gift_card?.valid_date)"></b>
                            · Stato: <b x-text="lookupResult.gift_card?.status"></b>
                        </div>
                        <div x-show="!lookupResult.valid_today" class="text-sm text-rose-300 mt-1" x-text="lookupResult.reason"></div>
                        <button x-show="lookupResult.valid_today" @click="redeem" class="mt-3 px-4 py-2 rounded-lg bg-emerald-500 text-emerald-950 font-bold">✓ Applica sconto e segna usata</button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Lista emesse -->
    <div class="card p-5">
        <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
            <h3 class="text-lg font-bold">📋 Gift card emesse</h3>
            <div class="flex gap-2 items-center">
                <input type="date" x-model="filterFrom" @change="load" class="px-2 py-1.5 rounded-lg bg-white/5 border border-white/10 text-xs">
                <span class="text-slate-500">→</span>
                <input type="date" x-model="filterTo" @change="load" class="px-2 py-1.5 rounded-lg bg-white/5 border border-white/10 text-xs">
            </div>
        </div>
        <div class="space-y-2">
            <template x-for="gc in cards" :key="gc.id">
                <div class="flex flex-wrap items-center gap-3 p-3 rounded-lg bg-white/5">
                    <div class="text-center w-14 flex-shrink-0">
                        <div class="text-[10px] text-slate-400" x-text="fmtDayName(gc.valid_date)"></div>
                        <div class="text-xl font-bold" x-text="fmtDay(gc.valid_date)"></div>
                    </div>
                    <div class="flex-1 min-w-[200px]">
                        <div class="font-bold" x-text="gc.customer_name"></div>
                        <div class="text-xs text-slate-400 font-mono" x-text="gc.code"></div>
                        <div class="text-xs text-amber-300/70" x-text="'⏰ '+fmtWindow(gc)"></div>
                        <div x-show="gc.phone" class="text-xs text-slate-400" x-text="'📞 '+gc.phone"></div>
                    </div>
                    <div class="px-2 py-1 rounded-lg text-xs font-bold" :class="statusBadge(gc.status)">
                        <span x-text="statusLabel(gc.status)"></span>
                    </div>
                    <div class="flex gap-1.5">
                        <a :href="`/gift_card.php?code=${gc.code}`" target="_blank" class="px-2 py-1 rounded bg-white/5 hover:bg-white/10 text-xs" title="Vedi">👁</a>
                        <button @click="openShare(gc)" class="px-2 py-1 rounded bg-emerald-500/20 text-emerald-300 hover:bg-emerald-500/30 text-xs" title="WhatsApp">💬</button>
                        <button @click="cancel(gc)" x-show="gc.status==='issued'" class="px-2 py-1 rounded bg-rose-500/15 text-rose-300 hover:bg-rose-500/25 text-xs" title="Annulla">✕</button>
                        <button @click="del(gc)" class="px-2 py-1 rounded bg-rose-500/15 text-rose-300 hover:bg-rose-500/25 text-xs" title="Elimina">🗑</button>
                    </div>
                </div>
            </template>
            <div x-show="!cards.length" class="text-center py-10 text-slate-500">
                <div class="text-4xl mb-2">🎁</div>
                <div>Nessuna gift card nel periodo selezionato</div>
            </div>
        </div>
    </div>
</main>

<script>
function gcPage(){return {
    cards: [], stats: {}, busy: false,
    form: { customer_name:'', phone:'', valid_date: new Date().toISOString().slice(0,10), valid_from_hour:'20:00', valid_to_hour:'03:00' },
    today: new Date().toISOString().slice(0,10),
    filterFrom: new Date(Date.now()-30*86400000).toISOString().slice(0,10),
    filterTo:   new Date(Date.now()+60*86400000).toISOString().slice(0,10),
    result: null, copied: false,
    lookupCode: '', lookupResult: null,
    async load(){
        const r = await fetch(`/api/gift_cards.php?action=list&from=${this.filterFrom}&to=${this.filterTo}`);
        const d = await r.json();
        this.cards = d.gift_cards || [];
        this.stats = d.stats || {};
    },
    async create(){
        if (!this.form.customer_name.trim()){ alert('Nome cliente obbligatorio'); return; }
        if (!this.form.valid_date){ alert('Data valida obbligatoria'); return; }
        this.busy = true;
        try {
            const r = await fetch('/api/gift_cards.php?action=create', {
                method:'POST', headers:{'Content-Type':'application/json'},
                body: JSON.stringify({...this.form, percent: 10})
            });
            const d = await r.json();
            if (d.error){ alert(d.error); return; }
            // pre-componi messaggio WhatsApp con link gift card
            const phoneOnly = (this.form.phone||'').replace(/[^0-9+]/g,'').replace(/^\+/,'');
            const dateFmt = this.form.valid_date.split('-').reverse().join('/');
            const msg = `Ecco la Sua gift card del 10% per il giorno ${dateFmt} — valida SOLO per quella data, da mostrare in cassa. ${d.image_url}`;
            d.whatsapp_url = (phoneOnly ? `https://wa.me/${phoneOnly}` : `https://wa.me/`) + `?text=` + encodeURIComponent(msg);
            this.result = d;
            this.resetForm();
            this.load();
        } catch(e){ alert('Errore: '+e.message); }
        finally { this.busy = false; }
    },
    resetForm(){ this.form = { customer_name:'', phone:'', valid_date: this.today, valid_from_hour:'20:00', valid_to_hour:'03:00' }; },
    async copyImageLink(){
        try { await navigator.clipboard.writeText(this.result.image_url); this.copied = true; setTimeout(()=>this.copied=false, 1500); } catch(e){ alert(this.result.image_url); }
    },
    async lookup(){
        if (!this.lookupCode.trim()) return;
        const code = this.lookupCode.trim().toUpperCase();
        const r = await fetch('/api/gift_cards.php?action=lookup&code='+encodeURIComponent(code));
        const d = await r.json();
        this.lookupResult = d;
    },
    async redeem(){
        if (!confirm('Applicare lo sconto e marcare la gift card come usata?')) return;
        const r = await fetch('/api/gift_cards.php?action=redeem', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({code: this.lookupResult.gift_card.code})
        });
        const d = await r.json();
        if (d.error){ alert(d.error); return; }
        alert('✅ Sconto applicato. Gift card segnata come usata.');
        this.lookupCode = ''; this.lookupResult = null; this.load();
    },
    openShare(gc){
        const phoneOnly = (gc.phone||'').replace(/[^0-9+]/g,'').replace(/^\+/,'');
        const dateFmt = gc.valid_date.split('-').reverse().join('/');
        const url = `${location.origin}/gift_card.php?code=${gc.code}`;
        const msg = `Ecco la Sua gift card del 10% per il giorno ${dateFmt} — valida SOLO per quella data, da mostrare in cassa. ${url}`;
        window.open((phoneOnly ? `https://wa.me/${phoneOnly}` : `https://wa.me/`) + '?text=' + encodeURIComponent(msg), '_blank');
    },
    async cancel(gc){
        if (!confirm(`Annullare la gift card di ${gc.customer_name}?`)) return;
        await fetch('/api/gift_cards.php?action=cancel', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({id:gc.id})});
        this.load();
    },
    async del(gc){
        if (!confirm(`Eliminare definitivamente la gift card di ${gc.customer_name}? Non sarà più recuperabile.`)) return;
        await fetch('/api/gift_cards.php?action=delete', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({id:gc.id})});
        this.load();
    },
    fmtDate(s){ return s ? s.split('-').reverse().join('/') : ''; },
    fmtWindow(gc){
        const from = (gc.valid_from_hour||'20:00:00').slice(0,5);
        const to   = (gc.valid_to_hour||'03:00:00').slice(0,5);
        const next = parseInt(to.slice(0,2),10) < parseInt(from.slice(0,2),10);
        return next ? `${from} → ${to} (notte)` : `${from} → ${to}`;
    },
    fmtDay(s){ return s ? new Date(s).getDate() : ''; },
    fmtDayName(s){ return s ? new Date(s).toLocaleDateString('it-IT',{weekday:'short'}) : ''; },
    statusLabel(s){ return ({issued:'Emessa', used:'Usata', expired:'Scaduta', cancelled:'Annullata'})[s] || s; },
    statusBadge(s){ return ({
        issued:   'bg-amber-500/15 text-amber-300',
        used:     'bg-emerald-500/15 text-emerald-300',
        expired:  'bg-slate-500/15 text-slate-400',
        cancelled:'bg-rose-500/15 text-rose-300'
    })[s] || 'bg-white/5'; }
}}
</script>
<?php layout_mobile_nav('giftcards'); layout_foot(); ?>
