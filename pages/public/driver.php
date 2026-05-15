<?php
$token = $_GET['t'] ?? '';
if (!$token) { http_response_code(401); echo 'Invalid link'; exit; }

$st = db()->prepare("SELECT s.tenant_id, t.name FROM settings s LEFT JOIN tenants t ON t.id=s.tenant_id WHERE s.`key`='transfer_driver_token' AND s.value=?");
$st->execute([$token]);
$row = $st->fetch();
if (!$row) { http_response_code(403); echo 'Invalid or expired link'; exit; }
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
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  body{font-family:'Inter',sans-serif;background:#0a0a13;color:#e2e8f0;-webkit-font-smoothing:antialiased;}
  html[dir="rtl"] body{font-family:'Cairo','Inter',sans-serif;}
  .card{background:linear-gradient(135deg,rgba(255,255,255,0.045),rgba(255,255,255,0.01));border:1px solid rgba(255,255,255,0.09);border-radius:18px;}
  .glass{backdrop-filter:blur(16px);background:rgba(16,16,28,0.78);}
  .pulse-dot{width:8px;height:8px;border-radius:50%;background:#10b981;box-shadow:0 0 0 0 rgba(16,185,129,.6);animation:pulse 2s infinite;}
  @keyframes pulse{70%{box-shadow:0 0 0 8px rgba(16,185,129,0);}100%{box-shadow:0 0 0 0 rgba(16,185,129,0);}}
  [x-cloak]{display:none!important;}
  .lang-chip{padding:5px 9px;border-radius:99px;font-size:10.5px;font-weight:700;letter-spacing:.8px;background:rgba(255,255,255,.06);color:#94a3b8;cursor:pointer;border:none;}
  .lang-chip.active{background:#8b5cf6;color:#fff;}
</style>
</head>
<body class="min-h-screen pb-8" x-data="driverApp()" x-init="init()">

<header class="glass sticky top-0 z-30 border-b border-white/5">
    <div class="max-w-2xl mx-auto px-4 py-3 flex items-center gap-3">
        <img src="/assets/img/logo.jpeg" class="w-10 h-10 rounded-xl object-cover" alt="">
        <div class="flex-1 min-w-0">
            <div class="font-bold text-lg leading-tight" x-text="t('header_title')"></div>
            <div class="text-xs text-slate-400 flex items-center gap-2">
                <span class="pulse-dot"></span>
                <span><?= htmlspecialchars($tenantName) ?></span>
            </div>
        </div>
        <div class="flex items-center gap-1">
            <button class="lang-chip" :class="lang==='it'?'active':''" @click="setLang('it')">🇮🇹 IT</button>
            <button class="lang-chip" :class="lang==='en'?'active':''" @click="setLang('en')">🇬🇧 EN</button>
            <button class="lang-chip" :class="lang==='ar'?'active':''" @click="setLang('ar')">🇸🇦 AR</button>
        </div>
        <button onclick="location.reload()" class="p-2 rounded-xl bg-white/5 hover:bg-white/10" :title="t('refresh')">🔄</button>
    </div>
</header>

<main class="max-w-2xl mx-auto px-3 py-4">

    <!-- Tabs giorno -->
    <div class="flex gap-2 mb-4 overflow-x-auto pb-1">
        <template x-for="d in days" :key="d.iso">
            <button @click="day=d.iso" :class="day===d.iso?'bg-brand-500 text-white':'bg-white/5 text-slate-300'" class="flex-shrink-0 px-3 py-2 rounded-xl text-sm font-semibold flex flex-col items-center gap-0.5 min-w-[60px] transition">
                <span class="text-xs opacity-70" x-text="d.dayName"></span>
                <span class="text-lg leading-none" x-text="d.dayNum"></span>
                <span x-show="d.count>0" class="text-[10px] font-bold" :class="day===d.iso?'text-white':'text-brand-400'" x-text="d.count+' '+(d.count==1?t('ride'):t('rides'))"></span>
            </button>
        </template>
    </div>

    <!-- Lista transfer del giorno -->
    <div class="space-y-3">
        <template x-for="t_ in todayTransfers" :key="t_.id">
            <div class="card p-4">
                <div class="flex items-start gap-3 mb-3">
                    <div class="flex-shrink-0 w-16 text-center">
                        <div class="text-2xl font-bold leading-none" :class="isPast(t_.pickup_when)?'text-rose-400':'text-brand-400'" x-text="fmtTime(t_.pickup_when)"></div>
                        <div class="text-[10px] uppercase tracking-wider mt-1" :class="isPast(t_.pickup_when)?'text-rose-400':'text-slate-500'" x-text="timeAgo(t_.pickup_when)"></div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-bold text-lg" x-text="t_.customer_name"></span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase" :class="dirClass(t_.direction)" x-text="dirLabel(t_.direction)"></span>
                        </div>
                        <div class="text-xs text-slate-400 mt-1 flex flex-wrap gap-x-3 gap-y-1">
                            <span x-show="t_.passengers" x-text="'👤 '+t_.passengers+' '+(t_.passengers==1?t('person'):t('persons'))"></span>
                            <span x-show="t_.language" x-text="'🗣 '+langLabel(t_.language)"></span>
                        </div>
                    </div>
                </div>

                <!-- Pickup -->
                <div class="bg-emerald-500/8 border border-emerald-500/20 rounded-xl p-3 mb-2">
                    <div class="text-[10px] text-emerald-300 font-bold uppercase tracking-wider mb-1" x-text="'📍 '+t('pickup')"></div>
                    <div class="font-semibold text-sm" x-text="t_.pickup_location || '—'"></div>
                    <div class="text-xs text-slate-400" x-show="t_.pickup_address" x-text="t_.pickup_address"></div>
                    <a x-show="t_.pickup_address || t_.pickup_location" :href="mapsUrl(t_.pickup_address||t_.pickup_location)" target="_blank" class="inline-flex items-center gap-1 mt-2 text-xs font-semibold text-emerald-300 hover:text-emerald-200">
                        <span x-text="'🗺 '+t('open_maps')+' →'"></span>
                    </a>
                </div>

                <!-- Dropoff -->
                <div x-show="t_.dropoff_location || t_.dropoff_address" class="bg-rose-500/8 border border-rose-500/20 rounded-xl p-3 mb-2">
                    <div class="text-[10px] text-rose-300 font-bold uppercase tracking-wider mb-1" x-text="'🏁 '+t('destination')"></div>
                    <div class="font-semibold text-sm" x-text="t_.dropoff_location || t_.dropoff_address"></div>
                    <div class="text-xs text-slate-400" x-show="t_.dropoff_address && t_.dropoff_location" x-text="t_.dropoff_address"></div>
                    <a x-show="t_.dropoff_address || t_.dropoff_location" :href="mapsUrl(t_.dropoff_address||t_.dropoff_location)" target="_blank" class="inline-flex items-center gap-1 mt-2 text-xs font-semibold text-rose-300 hover:text-rose-200">
                        <span x-text="'🗺 '+t('open_maps')+' →'"></span>
                    </a>
                </div>

                <!-- Note -->
                <div x-show="t_.notes" class="bg-amber-500/8 border border-amber-500/20 rounded-xl p-3 mb-2">
                    <div class="text-[10px] text-amber-300 font-bold uppercase tracking-wider mb-1" x-text="'📝 '+t('notes')"></div>
                    <div class="text-sm" x-text="t_.notes"></div>
                </div>

                <!-- Azioni contatto -->
                <div x-show="t_.phone" class="grid grid-cols-2 gap-2 mb-2">
                    <a :href="'tel:'+t_.phone" class="flex items-center justify-center gap-2 py-2.5 rounded-xl bg-sky-500/20 text-sky-300 font-semibold text-sm hover:bg-sky-500/30">
                        <span x-text="'📞 '+t('call')"></span>
                    </a>
                    <a :href="waUrl(t_.phone, t_.language)" target="_blank" class="flex items-center justify-center gap-2 py-2.5 rounded-xl bg-emerald-500/20 text-emerald-300 font-semibold text-sm hover:bg-emerald-500/30">
                        <span x-text="'💬 '+t('whatsapp')"></span>
                    </a>
                </div>

                <!-- Status workflow -->
                <div class="mt-3">
                    <div class="text-[10px] text-slate-500 uppercase tracking-wider mb-2" x-text="t('ride_status')"></div>
                    <div class="grid grid-cols-4 gap-1.5">
                        <button @click="setStatus(t_, 'scheduled')" :class="t_.status==='scheduled'?'bg-white/15 ring-2 ring-white/20':'bg-white/5'" class="py-2 rounded-lg text-[11px] font-semibold" x-text="'⏰ '+t('s_todo')"></button>
                        <button @click="setStatus(t_, 'on_way')" :class="t_.status==='on_way'?'bg-amber-500/30 ring-2 ring-amber-500/40 text-amber-200':'bg-white/5'" class="py-2 rounded-lg text-[11px] font-semibold" x-text="'🚗 '+t('s_onway')"></button>
                        <button @click="setStatus(t_, 'picked_up')" :class="t_.status==='picked_up'?'bg-brand-500/30 ring-2 ring-brand-500/40 text-brand-200':'bg-white/5'" class="py-2 rounded-lg text-[11px] font-semibold" x-text="'✅ '+t('s_picked')"></button>
                        <button @click="setStatus(t_, 'completed')" :class="t_.status==='completed'?'bg-emerald-500/30 ring-2 ring-emerald-500/40 text-emerald-200':'bg-white/5'" class="py-2 rounded-lg text-[11px] font-semibold" x-text="'🏁 '+t('s_done')"></button>
                    </div>
                    <button @click="setStatus(t_, 'no_show')" :class="t_.status==='no_show'?'bg-rose-500/30 text-rose-200':'text-rose-400/60 hover:text-rose-300'" class="mt-1.5 w-full py-1.5 rounded-lg text-[11px] font-semibold" x-text="'❌ '+t('s_noshow')"></button>
                </div>

                <div x-show="t_.vehicle || t_.driver_name" class="mt-3 pt-3 border-t border-white/5 text-xs text-slate-500">
                    <span x-show="t_.vehicle" x-text="'🚐 '+t_.vehicle"></span>
                    <span x-show="t_.vehicle && t_.driver_name"> · </span>
                    <span x-show="t_.driver_name" x-text="'🧑‍✈️ '+t_.driver_name"></span>
                </div>
            </div>
        </template>
        <div x-show="!todayTransfers.length" class="text-center py-16 text-slate-400">
            <div class="text-6xl mb-3 opacity-50">😴</div>
            <div class="font-semibold" x-text="t('no_transfers')"></div>
            <div class="text-xs mt-1" x-text="t('enjoy_break')"></div>
        </div>
    </div>

    <div class="text-center text-xs text-slate-600 mt-8">
        <span x-text="t('auto_refresh')"></span> · <button @click="load" class="underline" x-text="t('refresh_now')"></button>
    </div>
</main>

<script>
const TOKEN = <?= json_encode($token) ?>;

const I18N = {
  it: {
    header_title:'Transfer', refresh:'Aggiorna',
    ride:'corsa', rides:'corse',
    pickup:'Ritiro', destination:'Destinazione', notes:'Note',
    open_maps:'Apri in Maps',
    call:'Chiama', whatsapp:'WhatsApp',
    ride_status:'Stato corsa',
    s_todo:'Da fare', s_onway:'In viaggio', s_picked:'A bordo', s_done:'Fatto', s_noshow:'Cliente non si è presentato',
    no_transfers:'Nessun transfer per oggi', enjoy_break:'Goditi la pausa.',
    auto_refresh:'Aggiornato in automatico ogni 30 secondi', refresh_now:'aggiorna ora',
    person:'persona', persons:'persone',
    today:'oggi',
    dir_to_venue:'Verso il locale', dir_to_hotel:'Ritorno hotel', dir_transfer:'Transfer',
    h_ago:'h fa', min_ago:'min fa', in_min:'tra', in_h:'tra'
  },
  en: {
    header_title:'Transfer', refresh:'Refresh',
    ride:'ride', rides:'rides',
    pickup:'Pickup', destination:'Destination', notes:'Notes',
    open_maps:'Open in Maps',
    call:'Call', whatsapp:'WhatsApp',
    ride_status:'Ride status',
    s_todo:'To do', s_onway:'On the way', s_picked:'On board', s_done:'Done', s_noshow:'Customer no-show',
    no_transfers:'No transfers for today', enjoy_break:'Enjoy your break.',
    auto_refresh:'Auto-refreshes every 30 seconds', refresh_now:'refresh now',
    person:'person', persons:'people',
    today:'today',
    dir_to_venue:'To restaurant', dir_to_hotel:'Back to hotel', dir_transfer:'Transfer',
    h_ago:'h ago', min_ago:'min ago', in_min:'in', in_h:'in'
  },
  ar: {
    header_title:'النقل', refresh:'تحديث',
    ride:'رحلة', rides:'رحلات',
    pickup:'الاستلام', destination:'الوجهة', notes:'ملاحظات',
    open_maps:'فتح في الخرائط',
    call:'اتصل', whatsapp:'واتساب',
    ride_status:'حالة الرحلة',
    s_todo:'قيد الانتظار', s_onway:'في الطريق', s_picked:'على متن السيارة', s_done:'تم', s_noshow:'العميل لم يحضر',
    no_transfers:'لا توجد رحلات اليوم', enjoy_break:'استمتع باستراحتك.',
    auto_refresh:'تحديث تلقائي كل 30 ثانية', refresh_now:'تحديث الآن',
    person:'شخص', persons:'أشخاص',
    today:'اليوم',
    dir_to_venue:'إلى المطعم', dir_to_hotel:'العودة إلى الفندق', dir_transfer:'نقل',
    h_ago:'س مضت', min_ago:'د مضت', in_min:'خلال', in_h:'خلال'
  }
};

function driverApp(){return {
    transfers: [],
    day: new Date().toISOString().slice(0,10),
    lang: (function(){
        try { return localStorage.getItem('driver_lang') || 'en'; } catch(e){ return 'en'; }
    })(),
    t(key){ return (I18N[this.lang] && I18N[this.lang][key]) || I18N.en[key] || key; },
    setLang(l){
        this.lang = l;
        try { localStorage.setItem('driver_lang', l); } catch(e){}
        document.documentElement.lang = l;
        document.documentElement.dir = (l === 'ar') ? 'rtl' : 'ltr';
    },
    get locale(){ return ({it:'it-IT', en:'en-GB', ar:'ar-EG'})[this.lang] || 'en-GB'; },
    get days(){
        const out = [];
        const today = new Date();
        for (let i=0;i<7;i++){
            const d = new Date(today);
            d.setDate(today.getDate()+i);
            const iso = d.toISOString().slice(0,10);
            const count = this.transfers.filter(x => (x.pickup_when||'').slice(0,10)===iso).length;
            let dayName;
            if (i===0) dayName = this.t('today');
            else dayName = d.toLocaleDateString(this.locale, {weekday:'short'}).replace('.','');
            out.push({ iso, dayName, dayNum: d.getDate(), count });
        }
        return out;
    },
    get todayTransfers(){
        return this.transfers
            .filter(x => (x.pickup_when||'').slice(0,10) === this.day)
            .sort((a,b) => (a.pickup_when||'').localeCompare(b.pickup_when||''));
    },
    async init(){
        // applica la lingua iniziale (RTL/LTR)
        this.setLang(this.lang);
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
    async setStatus(t_, s){
        t_.status = s; // ottimistico
        await fetch('/api/driver_transfers.php?action=set_status&t='+encodeURIComponent(TOKEN), {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({id:t_.id, status:s})
        });
    },
    fmtTime(s){ return s ? new Date(s.replace(' ','T')).toLocaleTimeString(this.locale,{hour:'2-digit',minute:'2-digit'}) : ''; },
    timeAgo(s){
        if (!s) return '';
        const diff = (new Date(s.replace(' ','T')) - new Date())/60000;
        if (diff < -60) return Math.abs(Math.round(diff/60))+' '+this.t('h_ago');
        if (diff < 0) return Math.abs(Math.round(diff))+' '+this.t('min_ago');
        if (diff < 60) return this.t('in_min')+' '+Math.round(diff)+'min';
        return this.t('in_h')+' '+Math.round(diff/60)+'h';
    },
    isPast(s){ return s ? new Date(s.replace(' ','T')) < new Date() : false; },
    mapsUrl(q){ return 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(q); },
    dirLabel(d){
        const key = ({to_venue:'dir_to_venue', to_hotel:'dir_to_hotel', arrival:'dir_to_venue', departure:'dir_to_hotel', internal:'dir_to_venue'})[d] || 'dir_transfer';
        const icon = (key==='dir_to_hotel') ? '🏨' : '🍽️';
        return icon + ' ' + this.t(key);
    },
    dirClass(d){ return ({to_venue:'bg-emerald-500/15 text-emerald-300', to_hotel:'bg-sky-500/15 text-sky-300', arrival:'bg-emerald-500/15 text-emerald-300', departure:'bg-sky-500/15 text-sky-300', internal:'bg-emerald-500/15 text-emerald-300'})[d] || 'bg-white/10'; },
    langLabel(l){ return ({it:'Italiano',en:'English',es:'Español',fr:'Français',de:'Deutsch',ar:'العربية'})[l] || l; },
    // Messaggio WhatsApp generato nella lingua del CLIENTE (non dell'autista)
    waUrl(phone, lang){
        const msg = ({
            it: 'Salve, sono l autista del Lollapalooza. La sto raggiungendo all hotel per portarla al ristorante.',
            en: 'Hello, I am the Lollapalooza driver. I am on my way to your hotel to take you to the restaurant.',
            es: 'Hola, soy el conductor de Lollapalooza. Estoy en camino a su hotel para llevarle al restaurante.',
            fr: 'Bonjour, je suis le chauffeur du Lollapalooza. J arrive à votre hôtel pour vous emmener au restaurant.',
            de: 'Hallo, ich bin der Lollapalooza-Fahrer. Ich bin auf dem Weg zu Ihrem Hotel, um Sie zum Restaurant zu bringen.',
            ar: 'مرحبًا، أنا سائق لولابالوزا. أنا في طريقي إلى فندقكم لأخذكم إلى المطعم.'
        })[lang] || 'Hello, this is Lollapalooza driver. On my way to your hotel.';
        const clean = (phone||'').replace(/[^0-9+]/g,'').replace(/^\+/,'');
        return 'https://wa.me/'+clean+'?text='+encodeURIComponent(msg);
    }
}}
</script>
</body>
</html>
