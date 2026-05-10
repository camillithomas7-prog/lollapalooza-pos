<?php layout_head('Fornitori'); layout_sidebar('suppliers'); layout_topbar('Fornitori', 'Gestione fornitori'); ?>
<main class="md:ml-64 lg:ml-72 p-4 md:p-8 pb-24 md:pb-8" x-data="sPage()" x-init="load()">
    <div class="flex justify-between mb-4">
        <h2 class="font-bold">Fornitori</h2>
        <button @click="add" class="px-4 py-2 rounded-lg btn-primary text-sm font-semibold">+ Fornitore</button>
    </div>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
        <template x-for="s in suppliers" :key="s.id">
            <div class="card p-4">
                <div class="font-bold" x-text="s.name"></div>
                <div class="text-sm text-slate-400 mt-1" x-text="s.contact"></div>
                <div class="text-xs text-slate-500 mt-2 space-y-1">
                    <div x-text="'📞 '+(s.phone||'—')"></div>
                    <div x-text="'✉ '+(s.email||'—')"></div>
                </div>
                <button @click="edit(s)" class="mt-3 text-brand-400 text-sm">Modifica →</button>
            </div>
        </template>
    </div>
    <div x-show="modal" x-cloak @click.self="modal=null" class="fixed inset-0 z-50 bg-black/70 flex items-center justify-center p-4">
        <div class="card p-6 w-full max-w-md">
            <h3 class="text-lg font-bold mb-4">Fornitore</h3>
            <div class="space-y-2">
                <input x-model="modal.name" placeholder="Nome azienda" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10">
                <input x-model="modal.contact" placeholder="Persona di contatto" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10">
                <input x-model="modal.phone" placeholder="Telefono" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10">
                <input x-model="modal.email" placeholder="Email" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10">
                <textarea x-model="modal.notes" rows="2" placeholder="Note" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10"></textarea>
                <div class="flex gap-2 mt-3"><button @click="save" class="flex-1 btn-primary py-2.5 rounded-lg font-semibold">Salva</button><button @click="modal=null" class="px-4 py-2.5 rounded-lg bg-white/5">Annulla</button></div>
            </div>
        </div>
    </div>
</main>
<script>
function sPage(){return {suppliers:[],modal:null,async load(){const r=await fetch('/api/inventory.php?action=suppliers');this.suppliers=(await r.json()).suppliers;},add(){this.modal={name:'',contact:'',phone:'',email:'',notes:''};},edit(s){this.modal={...s};},async save(){await fetch('/api/inventory.php?action=save_supplier',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(this.modal)});this.modal=null;this.load();}}}
</script>
<?php layout_mobile_nav('suppliers'); layout_foot(); ?>
