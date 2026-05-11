<?php layout_head('Menu'); layout_sidebar('menu'); layout_topbar('Menu', 'Categorie e prodotti'); ?>
<main class="md:ml-64 lg:ml-72 p-4 md:p-8 pb-24 md:pb-8" x-data="menuMgr()" x-init="load()">
    <div class="grid lg:grid-cols-4 gap-4">
        <!-- Categorie -->
        <div class="card p-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-bold">Categorie</h3>
                <button @click="editCat({})" class="text-brand-400 text-sm">+ Nuova</button>
            </div>
            <div class="space-y-1">
                <button @click="currentCat=null" :class="!currentCat?'bg-brand-500/20 text-white':'hover:bg-white/5'" class="w-full text-left px-3 py-2 rounded-lg text-sm">📋 Tutto</button>
                <template x-for="c in categories" :key="c.id">
                    <div class="flex items-center gap-1">
                        <button @click="currentCat=c.id" :class="currentCat===c.id?'bg-brand-500/20 text-white':'hover:bg-white/5'" class="flex-1 text-left px-3 py-2 rounded-lg text-sm">
                            <span x-text="c.icon+' '+c.name"></span>
                        </button>
                        <button @click="editCat(c)" class="p-1 text-slate-400">✏️</button>
                    </div>
                </template>
            </div>
        </div>
        <!-- Prodotti -->
        <div class="lg:col-span-3 card p-4">
            <div class="flex items-center justify-between mb-3 gap-2 flex-wrap">
                <h3 class="font-bold">Prodotti</h3>
                <div class="flex gap-2">
                    <input type="search" x-model="search" placeholder="Cerca..." class="px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-sm">
                    <button @click="editProd({})" class="px-4 py-2 rounded-lg btn-primary text-sm font-semibold">+ Nuovo prodotto</button>
                </div>
            </div>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <template x-for="p in filteredProds()" :key="p.id">
                    <div class="card p-3" :class="!p.available && 'opacity-40'">
                        <div class="aspect-square rounded-lg mb-2 overflow-hidden bg-white/5 flex items-center justify-center" :style="!p.image ? `background:${catColor(p.category_id)}15` : ''">
                            <template x-if="p.image"><img :src="p.image" class="w-full h-full object-cover" loading="lazy"></template>
                            <template x-if="!p.image"><span class="text-4xl opacity-60" x-text="catIcon(p.category_id)"></span></template>
                        </div>
                        <div class="flex items-start justify-between mb-1">
                            <button @click="toggleAvail(p)" :class="p.available?'text-emerald-400':'text-slate-500'" class="text-xs">
                                <span x-text="p.available?'✓ Disp.':'✗ Esaurito'"></span>
                            </button>
                        </div>
                        <div class="font-semibold text-sm leading-tight mb-1" x-text="p.name"></div>
                        <div class="text-xs text-slate-400 mb-2" x-text="p.category_name"></div>
                        <div class="flex justify-between items-center">
                            <div>
                                <div class="font-bold text-emerald-400" x-text="'€'+parseFloat(p.price).toFixed(2)"></div>
                                <div class="text-xs text-slate-500" x-text="'C: €'+parseFloat(p.cost).toFixed(2)+' · M: '+margin(p)+'%'"></div>
                            </div>
                            <button @click="editProd(p)" class="text-brand-400 text-sm">✏️</button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Modal categoria -->
    <div x-show="catModal" x-cloak @click.self="catModal=null" class="fixed inset-0 z-50 bg-black/70 flex items-center justify-center p-4">
        <div class="card p-6 w-full max-w-md">
            <h3 class="text-lg font-bold mb-4">Categoria</h3>
            <div class="space-y-3">
                <div><label class="text-xs text-slate-400">Nome</label><input x-model="catModal.name" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10"></div>
                <div class="grid grid-cols-3 gap-2">
                    <div><label class="text-xs text-slate-400">Icona</label><input x-model="catModal.icon" placeholder="🍕" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-center text-2xl"></div>
                    <div class="col-span-2"><label class="text-xs text-slate-400">Destinazione</label><select x-model="catModal.destination" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10"><option value="kitchen">Cucina</option><option value="bar">Bar</option><option value="none">Nessuna</option></select></div>
                </div>
                <div class="flex gap-2 mt-4">
                    <button @click="saveCat" class="flex-1 btn-primary py-2.5 rounded-lg font-semibold">Salva</button>
                    <button @click="catModal=null" class="px-4 py-2.5 rounded-lg bg-white/5">Annulla</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal prodotto -->
    <div x-show="prodModal" x-cloak @click.self="prodModal=null" class="fixed inset-0 z-50 bg-black/70 flex items-center justify-center p-4 overflow-y-auto">
        <div class="card p-6 w-full max-w-2xl my-auto">
            <h3 class="text-lg font-bold mb-4">Prodotto</h3>
            <div class="grid grid-cols-2 gap-3">
                <div class="col-span-2">
                    <label class="text-xs text-slate-400 mb-1 block">Immagine prodotto</label>
                    <div class="flex items-center gap-3">
                        <div class="w-24 h-24 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center overflow-hidden flex-shrink-0 relative">
                            <template x-if="prodModal.image">
                                <img :src="prodModal.image" class="w-full h-full object-cover">
                            </template>
                            <template x-if="!prodModal.image">
                                <span class="text-3xl opacity-40">🍽</span>
                            </template>
                            <div x-show="uploading" class="absolute inset-0 bg-black/60 flex items-center justify-center text-xs">⏳</div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <input type="file" accept="image/*" @change="uploadImage($event)" class="block w-full text-xs file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-brand-500/20 file:text-brand-300 file:cursor-pointer file:font-semibold cursor-pointer">
                            <p class="text-xs text-slate-500 mt-1">JPG/PNG/WebP, max 5MB</p>
                            <button type="button" x-show="prodModal.image" @click="prodModal.image=''" class="text-xs text-rose-400 mt-1">✕ Rimuovi immagine</button>
                        </div>
                    </div>
                </div>
                <div class="col-span-2"><label class="text-xs text-slate-400">Nome</label><input x-model="prodModal.name" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10"></div>
                <div class="col-span-2"><label class="text-xs text-slate-400">Descrizione</label><textarea x-model="prodModal.description" rows="2" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10"></textarea></div>
                <div><label class="text-xs text-slate-400">Categoria</label><select x-model.number="prodModal.category_id" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10"><template x-for="c in categories" :key="c.id"><option :value="c.id" x-text="c.name"></option></template></select></div>
                <div><label class="text-xs text-slate-400">IVA %</label><input type="number" x-model.number="prodModal.vat" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10"></div>
                <div><label class="text-xs text-slate-400">Prezzo €</label><input type="number" step="0.01" x-model.number="prodModal.price" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10"></div>
                <div><label class="text-xs text-slate-400">Costo €</label><input type="number" step="0.01" x-model.number="prodModal.cost" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10"></div>
                <div class="col-span-2"><label class="text-xs text-slate-400">Allergeni</label><input x-model="prodModal.allergens" placeholder="glutine, lattosio..." class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10"></div>
                <div class="col-span-2"><label class="text-xs text-slate-400">Ingredienti</label><input x-model="prodModal.ingredients" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10"></div>
                <div class="col-span-2 grid grid-cols-3 gap-2 p-3 rounded-lg bg-white/5">
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" x-model="prodModal.track_stock" :true-value="1" :false-value="0"> Magazzino</label>
                    <div><label class="text-xs text-slate-400">Stock</label><input type="number" x-model.number="prodModal.stock" class="w-full px-2 py-1.5 rounded bg-white/5 border border-white/10"></div>
                    <div><label class="text-xs text-slate-400">Min</label><input type="number" x-model.number="prodModal.stock_min" class="w-full px-2 py-1.5 rounded bg-white/5 border border-white/10"></div>
                </div>

                <!-- Traduzioni multi-lingua -->
                <div class="col-span-2 p-3 rounded-lg bg-white/5">
                    <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
                        <div class="text-sm font-semibold">🌐 Traduzioni</div>
                        <button type="button" @click="autoTranslate" :disabled="translating" class="text-xs px-3 py-1.5 rounded-lg bg-brand-500/20 text-brand-300 disabled:opacity-50" x-text="translating?'⏳ traduco...':'✨ Auto-traduci con AI'"></button>
                    </div>
                    <div class="flex gap-1 mb-3 overflow-x-auto" style="scrollbar-width:thin">
                        <template x-for="l in langs" :key="l.code">
                            <button type="button" @click="trLang=l.code" :class="trLang===l.code?'btn-primary':'bg-white/5'" class="px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap" x-text="l.flag+' '+l.code.toUpperCase()"></button>
                        </template>
                    </div>
                    <template x-for="l in langs" :key="l.code">
                        <div x-show="trLang===l.code" class="space-y-2">
                            <div><label class="text-xs text-slate-400">Nome</label><input x-model="prodModal.translations[l.code].name" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-sm" :placeholder="'Nome in '+l.code"></div>
                            <div><label class="text-xs text-slate-400">Descrizione</label><textarea x-model="prodModal.translations[l.code].description" rows="2" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-sm"></textarea></div>
                            <div><label class="text-xs text-slate-400">Allergeni</label><input x-model="prodModal.translations[l.code].allergens" class="w-full px-3 py-2 rounded-lg bg-white/5 border border-white/10 text-sm"></div>
                        </div>
                    </template>
                </div>
                <div class="col-span-2 flex gap-2 mt-2">
                    <button @click="saveProd" class="flex-1 btn-primary py-3 rounded-lg font-semibold">Salva prodotto</button>
                    <button x-show="prodModal.id" @click="deleteProd" class="px-4 py-3 rounded-lg bg-rose-500/20 text-rose-400">🗑</button>
                    <button @click="prodModal=null" class="px-4 py-3 rounded-lg bg-white/5">Annulla</button>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
function menuMgr(){return {
    categories:[], products:[], currentCat:null, search:'', catModal:null, prodModal:null, uploading:false,
    langs: [{code:'en',flag:'🇬🇧'},{code:'es',flag:'🇪🇸'},{code:'fr',flag:'🇫🇷'},{code:'de',flag:'🇩🇪'}],
    trLang: 'en',
    translating: false,
    emptyTranslations(){return {en:{name:'',description:'',allergens:''},es:{name:'',description:'',allergens:''},fr:{name:'',description:'',allergens:''},de:{name:'',description:'',allergens:''}};},
    async autoTranslate(){
        if (!this.prodModal.name) { alert('Inserisci prima nome e descrizione in italiano'); return; }
        this.translating = true;
        try {
            const r = await fetch('/api/translate.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({
                name:this.prodModal.name, description:this.prodModal.description||'', allergens:this.prodModal.allergens||''
            })});
            const d = await r.json();
            if (d.error) alert('Errore: '+d.error);
            else if (d.translations) {
                for (const lc of ['en','es','fr','de']) {
                    if (d.translations[lc]) this.prodModal.translations[lc] = d.translations[lc];
                }
            }
        } catch(e){ alert('Errore: '+e.message); }
        this.translating = false;
    },
    async load(){const r=await fetch('/api/menu.php?action=list');const d=await r.json();this.categories=d.categories;this.products=d.products;},
    async uploadImage(e){
        const f=e.target.files[0]; if(!f) return;
        if(f.size>5*1024*1024){alert('File troppo grande (max 5MB)');e.target.value='';return;}
        this.uploading=true;
        const fd=new FormData(); fd.append('file', f);
        try{
            const r=await fetch('/api/upload.php?type=products',{method:'POST',body:fd});
            const d=await r.json();
            if(d.error){alert('Errore upload: '+d.error);}else{this.prodModal.image=d.url;}
        }catch(err){alert('Errore di rete: '+err.message);}
        finally{this.uploading=false; e.target.value='';}
    },
    catIcon(id){return this.categories.find(c=>c.id===id)?.icon||'🍽';},
    catColor(id){return this.categories.find(c=>c.id===id)?.color||'#8b5cf6';},
    margin(p){const m=p.price?((p.price-p.cost)/p.price*100):0;return m.toFixed(0);},
    filteredProds(){let p=this.products;if(this.currentCat)p=p.filter(x=>x.category_id===this.currentCat);if(this.search){const s=this.search.toLowerCase();p=p.filter(x=>x.name.toLowerCase().includes(s));}return p;},
    editCat(c){this.catModal={id:c.id,name:c.name||'',icon:c.icon||'🍽',color:c.color||'#0ea5e9',destination:c.destination||'kitchen'};},
    async saveCat(){await fetch('/api/menu.php?action=save_category',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(this.catModal)});this.catModal=null;this.load();},
    editProd(p){
        let tr = this.emptyTranslations();
        try { if (p.translations) { const parsed = typeof p.translations === 'string' ? JSON.parse(p.translations) : p.translations; for (const lc in tr) tr[lc] = {...tr[lc], ...(parsed[lc]||{})}; } } catch(e){}
        this.prodModal={...p,price:parseFloat(p.price||0),cost:parseFloat(p.cost||0),vat:parseFloat(p.vat||22),stock:parseFloat(p.stock||0),stock_min:parseFloat(p.stock_min||0),available:p.available??1,track_stock:p.track_stock??0,category_id:p.category_id||this.categories[0]?.id, translations: tr};
        this.trLang = 'en';
    },
    async saveProd(){await fetch('/api/menu.php?action=save_product',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(this.prodModal)});this.prodModal=null;this.load();},
    async deleteProd(){if(!confirm('Eliminare?'))return;await fetch('/api/menu.php?action=delete_product',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:this.prodModal.id})});this.prodModal=null;this.load();},
    async toggleAvail(p){await fetch('/api/menu.php?action=toggle_available',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:p.id})});this.load();}
}}
</script>
<?php layout_mobile_nav('menu'); layout_foot(); ?>
