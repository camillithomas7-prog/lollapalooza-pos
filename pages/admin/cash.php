<?php layout_head('Cassa'); layout_sidebar('cash'); layout_topbar('Cassa', 'Apertura/chiusura, movimenti, pagamenti'); ?>
<main class="md:ml-64 lg:ml-72 p-4 md:p-8 pb-24 md:pb-8" x-data="cashPage()" x-init="load(); setInterval(load,5000)">
    <template x-if="!session">
        <div class="card p-8 text-center max-w-md mx-auto">
            <div class="text-5xl mb-3">💰</div>
            <h2 class="text-xl font-bold mb-4">Apri cassa</h2>
            <label class="text-sm text-slate-400 block mb-2">Fondo cassa iniziale (€)</label>
            <input type="number" x-model.number="openAmt" step="0.01" class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 mb-4 text-center text-xl">
            <button @click="openSession" class="w-full btn-primary py-3 rounded-xl font-bold">Apri cassa</button>
        </div>
    </template>
    <template x-if="session">
        <div class="space-y-4">
            <div class="grid md:grid-cols-4 gap-3">
                <div class="card p-4"><div class="text-xs text-slate-400">Apertura</div><div class="font-bold text-lg" x-text="'€ '+parseFloat(session.open_amount||0).toFixed(2)"></div></div>
                <div class="card p-4"><div class="text-xs text-slate-400">Contanti</div><div class="font-bold text-lg text-emerald-400" x-text="'€ '+parseFloat(session.by_method?.cash||0).toFixed(2)"></div></div>
                <div class="card p-4"><div class="text-xs text-slate-400">Carte/POS</div><div class="font-bold text-lg text-sky-400" x-text="'€ '+(parseFloat(session.by_method?.card||0)+parseFloat(session.by_method?.pos||0)).toFixed(2)"></div></div>
                <div class="card p-4"><div class="text-xs text-slate-400">Atteso in cassa</div><div class="font-bold text-lg text-amber-400" x-text="'€ '+parseFloat(session.expected||0).toFixed(2)"></div></div>
            </div>

            <div class="grid md:grid-cols-2 gap-4">
                <div class="card p-5">
                    <h3 class="font-bold mb-3">📤 Movimento manuale</h3>
                    <div class="grid grid-cols-2 gap-2 mb-2">
                        <button @click="movType='in'" :class="movType==='in'?'btn-primary':'bg-white/5'" class="py-2 rounded-lg text-sm font-semibold">+ Entrata</button>
                        <button @click="movType='out'" :class="movType==='out'?'btn-primary':'bg-white/5'" class="py-2 rounded-lg text-sm font-semibold">− Uscita</button>
                    </div>
                    <input type="number" x-model.number="movAmt" step="0.01" placeholder="Importo €" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 mb-2">
                    <input x-model="movReason" placeholder="Causale (es. mancia, spesa...)" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 mb-2">
                    <button @click="addMov" class="w-full btn-primary py-2.5 rounded-lg font-semibold">Aggiungi movimento</button>
                </div>
                <div class="card p-5">
                    <h3 class="font-bold mb-3">🔒 Chiudi cassa</h3>
                    <p class="text-xs text-slate-400 mb-2">Conta i contanti effettivi e inserisci il totale</p>
                    <input type="number" x-model.number="closeAmt" step="0.01" placeholder="€ contanti reali" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 mb-2 text-xl text-center">
                    <textarea x-model="closeNotes" rows="2" placeholder="Note chiusura..." class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 mb-2 text-sm"></textarea>
                    <button @click="closeSession" class="w-full py-2.5 rounded-lg bg-rose-500/20 text-rose-400 font-semibold">Chiudi sessione</button>
                </div>
            </div>
        </div>
    </template>

    <!-- Storico -->
    <div class="card p-5 mt-4">
        <h3 class="font-bold mb-3">📊 Storico chiusure</h3>
        <div class="overflow-x-auto -mx-5 px-5 scrollbar-thin">
            <table class="w-full text-sm min-w-[640px]">
                <thead class="text-xs uppercase text-slate-400">
                    <tr>
                        <th class="text-left p-2 whitespace-nowrap">Data</th>
                        <th class="text-left p-2 whitespace-nowrap">Operatore</th>
                        <th class="text-right p-2 whitespace-nowrap">Apertura</th>
                        <th class="text-right p-2 whitespace-nowrap">Atteso</th>
                        <th class="text-right p-2 whitespace-nowrap">Reale</th>
                        <th class="text-right p-2 whitespace-nowrap">Diff</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="s in history" :key="s.id">
                        <tr class="border-t border-white/5">
                            <td class="p-2 text-xs whitespace-nowrap" x-text="fmtDate(s.opened_at)"></td>
                            <td class="p-2 whitespace-nowrap" x-text="s.user_name||'—'"></td>
                            <td class="p-2 text-right whitespace-nowrap tabular-nums" x-text="'€'+parseFloat(s.open_amount||0).toFixed(2)"></td>
                            <td class="p-2 text-right whitespace-nowrap tabular-nums" x-text="'€'+parseFloat(s.expected||0).toFixed(2)"></td>
                            <td class="p-2 text-right whitespace-nowrap tabular-nums" x-text="s.close_amount?'€'+parseFloat(s.close_amount).toFixed(2):'—'"></td>
                            <td class="p-2 text-right whitespace-nowrap tabular-nums font-semibold" :class="parseFloat(s.diff||0)>=0?'text-emerald-400':'text-rose-400'" x-text="s.diff!=null?'€'+parseFloat(s.diff).toFixed(2):'—'"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
            <div x-show="!history.length" class="text-center py-6 text-slate-400 text-sm">Nessuna chiusura ancora</div>
        </div>
    </div>
</main>

<script>
function cashPage(){return {
    session:null, history:[], openAmt:100, movType:'in', movAmt:0, movReason:'', closeAmt:0, closeNotes:'',
    async load(){
        const r = await fetch('/api/cash.php?action=current'); const d = await r.json(); this.session = d.session;
        const h = await fetch('/api/cash.php?action=history'); this.history = (await h.json()).sessions;
    },
    async openSession(){await fetch('/api/cash.php?action=open_session',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({open_amount:this.openAmt})});this.load();},
    async closeSession(){if(!confirm('Chiudere cassa?'))return;await fetch('/api/cash.php?action=close_session',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:this.session.id, close_amount:this.closeAmt, notes:this.closeNotes})});this.load();},
    async addMov(){if(!this.movAmt)return;await fetch('/api/cash.php?action=movement',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({session_id:this.session.id, type:this.movType, amount:this.movAmt, reason:this.movReason})});this.movAmt=0;this.movReason='';this.load();},
    fmtDate(s){try{return new Date(s).toLocaleString('it-IT',{day:'2-digit',month:'2-digit',hour:'2-digit',minute:'2-digit'});}catch(e){return s;}}
}}
</script>
<?php layout_mobile_nav('cash'); layout_foot(); ?>
