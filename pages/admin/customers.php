<?php layout_head('Clienti'); layout_sidebar('customers'); layout_topbar('Clienti', 'CRM e fidelity'); ?>
<main class="md:ml-64 lg:ml-72 p-4 md:p-8 pb-24 md:pb-8" x-data="cPage()" x-init="load()">
    <div class="flex justify-between gap-2 mb-4">
        <input type="search" x-model="q" @input.debounce.300="load" placeholder="Cerca cliente..." class="flex-1 px-4 py-2 rounded-lg bg-white/5 border border-white/10">
        <button @click="add" class="px-4 py-2 rounded-lg btn-primary text-sm font-semibold whitespace-nowrap">+ Cliente</button>
    </div>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
        <template x-for="c in customers" :key="c.id">
            <div class="card p-4">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-brand-500 to-brand-700 flex items-center justify-center font-bold" x-text="c.name?.[0]"></div>
                        <div>
                            <div class="font-bold" x-text="c.name"></div>
                            <div class="text-xs text-slate-400" x-text="c.phone||c.email||''"></div>
                        </div>
                    </div>
                    <button @click="edit(c)" class="text-brand-400">✏️</button>
                </div>
                <div class="grid grid-cols-3 gap-2 mt-3 text-xs">
                    <div><div class="text-slate-400">Visite</div><div class="font-bold" x-text="c.visits"></div></div>
                    <div><div class="text-slate-400">Speso</div><div class="font-bold text-emerald-400" x-text="'€'+parseFloat(c.total_spent||0).toFixed(0)"></div></div>
                    <div><div class="text-slate-400">Punti</div><div class="font-bold text-amber-400" x-text="c.points"></div></div>
                </div>
            </div>
        </template>
    </div>

    <div x-show="modal" x-cloak @click.self="modal=null" class="fixed inset-0 z-50 bg-black/70 flex items-center justify-center p-4">
        <div class="card p-6 w-full max-w-md">
            <h3 class="text-lg font-bold mb-4">Cliente</h3>
            <div class="space-y-2">
                <input x-model="modal.name" placeholder="Nome" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10">
                <input x-model="modal.phone" placeholder="Telefono" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10">
                <input x-model="modal.email" type="email" placeholder="Email" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10">
                <input x-model="modal.birthday" type="date" placeholder="Compleanno" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10">
                <input x-model="modal.fidelity_card" placeholder="Tessera fidelity" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10">
                <textarea x-model="modal.notes" rows="2" placeholder="Note (preferenze, allergie...)" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10"></textarea>
                <div class="flex gap-2 mt-3">
                    <button @click="save" class="flex-1 btn-primary py-2.5 rounded-lg font-semibold">Salva</button>
                    <button @click="modal=null" class="px-4 py-2.5 rounded-lg bg-white/5">Annulla</button>
                </div>
            </div>
        </div>
    </div>
</main>
<script>
function cPage(){return {
    q:'',customers:[],modal:null,
    async load(){const r=await fetch('/api/customers.php?action=list&q='+encodeURIComponent(this.q));this.customers=(await r.json()).customers;},
    add(){this.modal={name:'',phone:'',email:'',birthday:'',notes:''};},
    edit(c){this.modal={...c};},
    async save(){await fetch('/api/customers.php?action=save',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(this.modal)});this.modal=null;this.load();}
}}
</script>
<?php layout_mobile_nav('customers'); layout_foot(); ?>
