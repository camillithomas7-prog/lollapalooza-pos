<?php
$token = $_GET['t'] ?? '';
if (!$token) { http_response_code(401); echo 'Link non valido'; exit; }

$st = db()->prepare("SELECT s.tenant_id, t.name FROM settings s LEFT JOIN tenants t ON t.id=s.tenant_id WHERE s.`key`='transfer_driver_token' AND s.value=?");
$st->execute([$token]);
$row = $st->fetch();
if (!$row) { http_response_code(403); echo 'Link non valido o scaduto'; exit; }
$tenantName = $row['name'] ?? 'Lollapalooza';
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="theme-color" content="#0a0a13">
<title>Transfer driver · <?= htmlspecialchars($tenantName) ?></title>
<link rel="icon" type="image/jpeg" href="/assets/img/logo.jpeg">
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/alpinejs@3.13.5/dist/cdn.min.js" defer></script>
<script>
tailwind.config = { darkMode:'class', theme:{ extend:{ colors:{ brand:{400:'#a78bfa',500:'#8b5cf6',600:'#7c3aed'} } } } }
</script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  body{font-family:'Inter',sans-serif;background:#0a0a13;color:#e2e8f0;-webkit-font-smoothing:antialiased;}
  .card{background:linear-gradient(135deg,rgba(255,255,255,0.045),rgba(255,255,255,0.01));border:1px solid rgba(255,255,255,0.09);border-radius:18px;}
  .glass{backdrop-filter:blur(16px);background:rgba(16,16,28,0.78);}
  .pulse-dot{width:8px;height:8px;border-radius:50%;background:#10b981;box-shadow:0 0 0 0 rgba(16,185,129,.6);animation:pulse 2s infinite;}
  @keyframes pulse{70%{box-shadow:0 0 0 8px rgba(16,185,129,0);}100%{box-shadow:0 0 0 0 rgba(16,185,129,0);}}
  [x-cloak]{display:none!important;}
</style>
</head>
<body class="min-h-screen pb-8">

<header class="glass sticky top-0 z-30 border-b border-white/5">
    <div class="max-w-2xl mx-auto px-4 py-3 flex items-center gap-3">
        <img src="/assets/img/logo.jpeg" class="w-10 h-10 rounded-xl object-cover" alt="">
        <div class="flex-1 min-w-0">
            <div class="font-bold text-lg leading-tight">Transfer</div>
            <div class="text-xs text-slate-400 flex items-center gap-2">
                <span class="pulse-dot"></span>
                <span><?= htmlspecialchars($tenantName) ?></span>
            </div>
        </div>
        <button onclick="location.reload()" class="p-2 rounded-xl bg-white/5 hover:bg-white/10" title="Aggiorna">🔄</button>
    </div>
</header>

<main class="max-w-2xl mx-auto px-3 py-4" x-data="driverApp()" x-init="init()">

    <!-- Tabs giorno -->
    <div class="flex gap-2 mb-4 overflow-x-auto pb-1">
        <template x-for="d in days" :key="d.iso">
            <button @click="day=d.iso" :class="day===d.iso?'bg-brand-500 text-white':'bg-white/5 text-slate-300'" class="flex-shrink-0 px-3 py-2 rounded-xl text-sm font-semibold flex flex-col items-center gap-0.5 min-w-[60px] transition">
                <span class="text-xs opacity-70" x-text="d.dayName"></span>
                <span class="text-lg leading-none" x-text="d.dayNum"></span>
                <span x-show="d.count>0" class="text-[10px] font-bold" :class="day===d.iso?'text-white':'text-brand-400'" x-text="d.count+' corse'"></span>
            </button>
        </template>
    </div>

    <!-- Lista transfer del giorno -->
    <div class="space-y-3">
        <template x-for="t in todayTransfers" :key="t.id">
            <div class="card p-4">
                <div class="flex items-start gap-3 mb-3">
                    <div class="flex-shrink-0 w-16 text-center">
                        <div class="text-2xl font-bold leading-none" :class="isPast(t.pickup_when)?'text-rose-400':'text-brand-400'" x-text="fmtTime(t.pickup_when)"></div>
                        <div class="text-[10px] uppercase tracking-wider mt-1" :class="isPast(t.pickup_when)?'text-rose-400':'text-slate-500'" x-text="timeAgo(t.pickup_when)"></div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-bold text-lg" x-text="t.customer_name"></span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase" :class="dirClass(t.direction)" x-text="dirLabel(t.direction)"></span>
                        </div>
                        <div class="text-xs text-slate-400 mt-1 flex flex-wrap gap-x-3 gap-y-1">
                            <span x-show="t.passengers" x-text="'👤 '+t.passengers+' pax'"></span>
                            <span x-show="t.luggage>0" x-text="'🧳 '+t.luggage+' bag'"></span>
                            <span x-show="t.flight_no" class="font-mono text-sky-300" x-text="'✈ '+t.flight_no"></span>
                            <span x-show="t.language" x-text="'🗣 '+langLabel(t.language)"></span>
                        </div>
                    </div>
                </div>

                <!-- Pickup -->
                <div class="bg-emerald-500/8 border border-emerald-500/20 rounded-xl p-3 mb-2">
                    <div class="text-[10px] text-emerald-300 font-bold uppercase tracking-wider mb-1">📍 Ritiro</div>
                    <div class="font-semibold text-sm" x-text="t.pickup_location || '—'"></div>
                    <div class="text-xs text-slate-400" x-show="t.pickup_address" x-text="t.pickup_address"></div>
                    <a x-show="t.pickup_address || t.pickup_location" :href="mapsUrl(t.pickup_address||t.pickup_location)" target="_blank" class="inline-flex items-center gap-1 mt-2 text-xs font-semibold text-emerald-300 hover:text-emerald-200">
                        🗺 Apri in Maps →
                    </a>
                </div>

                <!-- Dropoff -->
                <div x-show="t.dropoff_location || t.dropoff_address" class="bg-rose-500/8 border border-rose-500/20 rounded-xl p-3 mb-2">
                    <div class="text-[10px] text-rose-300 font-bold uppercase tracking-wider mb-1">🏁 Destinazione</div>
                    <div class="font-semibold text-sm" x-text="t.dropoff_location || t.dropoff_address"></div>
                    <div class="text-xs text-slate-400" x-show="t.dropoff_address && t.dropoff_location" x-text="t.dropoff_address"></div>
                    <a x-show="t.dropoff_address || t.dropoff_location" :href="mapsUrl(t.dropoff_address||t.dropoff_location)" target="_blank" class="inline-flex items-center gap-1 mt-2 text-xs font-semibold text-rose-300 hover:text-rose-200">
                        🗺 Apri in Maps →
                    </a>
                </div>

                <!-- Note -->
                <div x-show="t.notes" class="bg-amber-500/8 border border-amber-500/20 rounded-xl p-3 mb-2">
                    <div class="text-[10px] text-amber-300 font-bold uppercase tracking-wider mb-1">📝 Note</div>
                    <div class="text-sm" x-text="t.notes"></div>
                </div>

                <!-- Azioni contatto -->
                <div x-show="t.phone" class="grid grid-cols-2 gap-2 mb-2">
                    <a :href="'tel:'+t.phone" class="flex items-center justify-center gap-2 py-2.5 rounded-xl bg-sky-500/20 text-sky-300 font-semibold text-sm hover:bg-sky-500/30">
                        📞 Chiama
                    </a>
                    <a :href="waUrl(t.phone, t.language)" target="_blank" class="flex items-center justify-center gap-2 py-2.5 rounded-xl bg-emerald-500/20 text-emerald-300 font-semibold text-sm hover:bg-emerald-500/30">
                        💬 WhatsApp
                    </a>
                </div>

                <!-- Status workflow -->
                <div class="mt-3">
                    <div class="text-[10px] text-slate-500 uppercase tracking-wider mb-2">Stato corsa</div>
                    <div class="grid grid-cols-4 gap-1.5">
                        <button @click="setStatus(t, 'scheduled')" :class="t.status==='scheduled'?'bg-white/15 ring-2 ring-white/20':'bg-white/5'" class="py-2 rounded-lg text-[11px] font-semibold">⏰ Da fare</button>
                        <button @click="setStatus(t, 'on_way')" :class="t.status==='on_way'?'bg-amber-500/30 ring-2 ring-amber-500/40 text-amber-200':'bg-white/5'" class="py-2 rounded-lg text-[11px] font-semibold">🚗 In viaggio</button>
                        <button @click="setStatus(t, 'picked_up')" :class="t.status==='picked_up'?'bg-brand-500/30 ring-2 ring-brand-500/40 text-brand-200':'bg-white/5'" class="py-2 rounded-lg text-[11px] font-semibold">✅ A bordo</button>
                        <button @click="setStatus(t, 'completed')" :class="t.status==='completed'?'bg-emerald-500/30 ring-2 ring-emerald-500/40 text-emerald-200':'bg-white/5'" class="py-2 rounded-lg text-[11px] font-semibold">🏁 Fatto</button>
                    </div>
                    <button @click="setStatus(t, 'no_show')" :class="t.status==='no_show'?'bg-rose-500/30 text-rose-200':'text-rose-400/60 hover:text-rose-300'" class="mt-1.5 w-full py-1.5 rounded-lg text-[11px] font-semibold">❌ Cliente non si è presentato</button>
                </div>

                <div x-show="t.vehicle || t.driver_name" class="mt-3 pt-3 border-t border-white/5 text-xs text-slate-500">
                    <span x-show="t.vehicle" x-text="'🚐 '+t.vehicle"></span>
                    <span x-show="t.vehicle && t.driver_name"> · </span>
                    <span x-show="t.driver_name" x-text="'🧑‍✈️ '+t.driver_name"></span>
                </div>
            </div>
        </template>
        <div x-show="!todayTransfers.length" class="text-center py-16 text-slate-400">
            <div class="text-6xl mb-3 opacity-50">😴</div>
            <div class="font-semibold">Nessun transfer per oggi</div>
            <div class="text-xs mt-1">Goditi la pausa.</div>
        </div>
    </div>

    <div class="text-center text-xs text-slate-600 mt-8">
        Aggiornato in automatico ogni 30 secondi · <button @click="load" class="underline">aggiorna ora</button>
    </div>
</main>

<script>
const TOKEN = <?= json_encode($token) ?>;
function driverApp(){return {
    transfers: [],
    day: new Date().toISOString().slice(0,10),
    get days(){
        const out = [];
        const today = new Date();
        for (let i=0;i<7;i++){
            const d = new Date(today);
            d.setDate(today.getDate()+i);
            const iso = d.toISOString().slice(0,10);
            const count = this.transfers.filter(t => (t.pickup_when||'').slice(0,10)===iso).length;
            out.push({
                iso,
                dayName: i===0?'oggi':(i===1?'dom':d.toLocaleDateString('it-IT',{weekday:'short'})).replace('.',''),
                dayNum: d.getDate(),
                count
            });
        }
        return out;
    },
    get todayTransfers(){
        return this.transfers
            .filter(t => (t.pickup_when||'').slice(0,10) === this.day)
            .sort((a,b) => (a.pickup_when||'').localeCompare(b.pickup_when||''));
    },
    async init(){
        await this.load();
        setInterval(()=>this.load(), 30000);
    },
    async load(){
        try {
            const r = await fetch('/api/driver_transfers.php?action=list&t='+encodeURIComponent(TOKEN));
            const d = await r.json();
            if (d.transfers) this.transfers = d.transfers;
        } catch(e){}
    },
    async setStatus(t, s){
        t.status = s; // ottimistico
        await fetch('/api/driver_transfers.php?action=set_status&t='+encodeURIComponent(TOKEN), {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({id:t.id, status:s})
        });
    },
    fmtTime(s){ return s ? new Date(s.replace(' ','T')).toLocaleTimeString('it-IT',{hour:'2-digit',minute:'2-digit'}) : ''; },
    timeAgo(s){
        if (!s) return '';
        const diff = (new Date(s.replace(' ','T')) - new Date())/60000;
        if (diff < -60) return Math.abs(Math.round(diff/60))+'h fa';
        if (diff < 0) return Math.abs(Math.round(diff))+'min fa';
        if (diff < 60) return 'tra '+Math.round(diff)+'min';
        return 'tra '+Math.round(diff/60)+'h';
    },
    isPast(s){ return s ? new Date(s.replace(' ','T')) < new Date() : false; },
    mapsUrl(q){ return 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(q); },
    dirLabel(d){ return ({arrival:'🛬 Arrivo', departure:'🛫 Partenza', internal:'🏨 Interno'})[d] || d; },
    dirClass(d){ return ({arrival:'bg-emerald-500/15 text-emerald-300', departure:'bg-sky-500/15 text-sky-300', internal:'bg-brand-500/15 text-brand-400'})[d] || 'bg-white/10'; },
    langLabel(l){ return ({it:'Italiano',en:'English',es:'Español',fr:'Français',de:'Deutsch',ar:'العربية'})[l] || l; },
    waUrl(phone, lang){
        const msg = ({
            it: 'Salve, sono l autista del Lollapalooza. La sto raggiungendo al punto di ritiro.',
            en: 'Hello, I am the Lollapalooza driver. I am on my way to pick you up.',
            es: 'Hola, soy el conductor de Lollapalooza. Estoy en camino para recogerle.',
            fr: 'Bonjour, je suis le chauffeur du Lollapalooza. J arrive pour vous chercher.',
            de: 'Hallo, ich bin der Lollapalooza-Fahrer. Ich bin auf dem Weg zu Ihnen.',
            ar: 'مرحبًا، أنا سائق لولابالوزا. أنا في طريقي إليك.'
        })[lang] || 'Hello, this is Lollapalooza driver. On my way.';
        const clean = (phone||'').replace(/[^0-9+]/g,'').replace(/^\+/,'');
        return 'https://wa.me/'+clean+'?text='+encodeURIComponent(msg);
    }
}}
</script>
</body>
</html>
