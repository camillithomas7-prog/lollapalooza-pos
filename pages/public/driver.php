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

    <!-- Selettore fasce orarie del giorno -->
    <div class="flex gap-2 mb-4 overflow-x-auto pb-1" x-show="slotsForDay.length">
        <template x-for="slot in slotsForDay" :key="slot.key">
            <button @click="currentSlotKey=slot.key"
                :class="currentSlotKey===slot.key?(slot.leg==='out'?'bg-emerald-500/25 ring-2 ring-emerald-500/40 text-emerald-200':'bg-sky-500/25 ring-2 ring-sky-500/40 text-sky-200'):'bg-white/5 text-slate-300'"
                class="flex-shrink-0 px-3 py-2 rounded-xl flex flex-col items-center min-w-[68px] transition">
                <span class="text-[10px] opacity-75 leading-none" x-text="(slot.leg==='out'?'🍽️ ':'🏨 ')+(slot.leg==='out'?t('out_short'):t('ret_short'))"></span>
                <span class="text-lg font-bold leading-tight mt-0.5" x-text="slot.time"></span>
                <span class="text-[11px] font-semibold mt-0.5">
                    <span x-text="slot.totalPax"></span><span class="opacity-60">·</span><span x-text="slot.entries.length+' '+(slot.entries.length==1?t('booking_s'):t('booking_p'))"></span>
                </span>
            </button>
        </template>
    </div>

    <!-- Dettaglio della fascia selezionata -->
    <template x-if="currentSlot">
        <div>
            <!-- Big header -->
            <div class="card p-4 mb-3" :class="currentSlot.leg==='out'?'bg-emerald-500/8 border-emerald-500/25':'bg-sky-500/8 border-sky-500/25'">
                <div class="flex items-center gap-3">
                    <div class="text-4xl font-bold leading-none" :class="currentSlot.leg==='out'?'text-emerald-300':'text-sky-300'" x-text="currentSlot.time"></div>
                    <div class="flex-1 min-w-0">
                        <div class="text-[11px] uppercase tracking-wider font-bold" :class="currentSlot.leg==='out'?'text-emerald-300':'text-sky-300'" x-text="currentSlot.leg==='out'?t('outbound'):t('return_trip')"></div>
                        <div class="text-sm mt-0.5 font-semibold">
                            <span x-text="currentSlot.totalPax+' '+(currentSlot.totalPax==1?t('person'):t('persons'))"></span>
                            <span class="text-slate-500 mx-1">·</span>
                            <span x-text="currentSlot.hotelCount+' '+(currentSlot.hotelCount==1?t('hotel_s'):t('hotel_p'))"></span>
                        </div>
                    </div>
                    <!-- Avanzamento -->
                    <div class="text-right flex-shrink-0">
                        <div class="text-2xl font-bold" :class="currentSlot.doneCount===currentSlot.entries.length?'text-emerald-300':'text-slate-300'">
                            <span x-text="currentSlot.doneCount"></span><span class="text-slate-500">/</span><span x-text="currentSlot.entries.length"></span>
                        </div>
                        <div class="text-[10px] text-slate-500 uppercase tracking-wider" x-text="t('done_count')"></div>
                    </div>
                </div>
                <!-- Progress bar -->
                <div class="mt-3 h-1.5 bg-white/5 rounded-full overflow-hidden">
                    <div class="h-full transition-all" :class="currentSlot.leg==='out'?'bg-emerald-400':'bg-sky-400'" :style="'width:'+(currentSlot.entries.length?(currentSlot.doneCount/currentSlot.entries.length*100):0)+'%'"></div>
                </div>
            </div>

            <!-- ANDATA: raggruppato per HOTEL -->
            <template x-if="currentSlot.leg==='out'">
                <div class="space-y-3">
                    <template x-for="grp in currentSlot.groups" :key="grp.key">
                        <div class="card overflow-hidden">
                            <!-- Hotel header -->
                            <div class="p-3 bg-emerald-500/8 border-b border-emerald-500/15 flex items-center gap-2 flex-wrap">
                                <div class="flex-1 min-w-0">
                                    <div class="font-bold text-base leading-tight" x-text="grp.hotel || '—'"></div>
                                    <div class="text-xs text-slate-400" x-show="grp.address" x-text="grp.address"></div>
                                </div>
                                <div class="px-2.5 py-1 rounded-lg bg-emerald-500/20 text-emerald-300 text-xs font-bold">
                                    <span x-text="grp.totalPax"></span>
                                    <span x-text="' '+(grp.totalPax==1?t('person'):t('persons'))"></span>
                                </div>
                                <a x-show="grp.mapsTarget" :href="mapsUrl(grp.mapsTarget)" target="_blank" class="px-2.5 py-1 rounded-lg bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-300 text-xs font-bold">🗺 Maps</a>
                            </div>
                            <!-- Persone in questo hotel -->
                            <div class="divide-y divide-white/5">
                                <template x-for="entry in grp.entries" :key="entry.key">
                                    <div class="p-3 flex items-center gap-2">
                                        <button @click="cycleStatus(entry)" :class="stateChipClass(entry.status)" class="flex-shrink-0 w-9 h-9 rounded-full flex items-center justify-center font-bold text-base" :title="statusLabel(entry.status)">
                                            <span x-text="stateChipIcon(entry.status)"></span>
                                        </button>
                                        <div class="flex-1 min-w-0">
                                            <div class="font-semibold leading-tight" x-text="entry.customer_name"></div>
                                            <div class="text-[11px] text-slate-400 flex flex-wrap gap-x-2">
                                                <span x-show="entry.passengers" x-text="'👤×'+entry.passengers"></span>
                                                <span x-show="entry.language" x-text="langFlag(entry.language)+' '+langLabel(entry.language)"></span>
                                            </div>
                                            <div x-show="entry.notes" class="text-[11px] text-amber-300 mt-0.5 truncate" x-text="'📝 '+entry.notes"></div>
                                        </div>
                                        <a x-show="entry.phone" :href="'tel:'+entry.phone" class="w-9 h-9 rounded-full bg-sky-500/20 text-sky-300 flex items-center justify-center text-base">📞</a>
                                        <a x-show="entry.phone" :href="waUrl(entry.phone, entry.language, entry.leg)" target="_blank" class="w-9 h-9 rounded-full bg-emerald-500/20 text-emerald-300 flex items-center justify-center text-base">💬</a>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            <!-- RITORNO: tutti partono dal locale, raggruppo per HOTEL di destinazione -->
            <template x-if="currentSlot.leg==='ret'">
                <div class="space-y-3">
                    <!-- Pickup point unico (il locale) -->
                    <div class="card p-3 bg-sky-500/5 border-sky-500/20 flex items-center gap-3">
                        <div class="text-2xl">🍽️</div>
                        <div class="flex-1 min-w-0">
                            <div class="text-[10px] text-sky-300 font-bold uppercase tracking-wider" x-text="t('venue_pickup')"></div>
                            <div class="font-bold" x-text="currentSlot.entries[0]?.dropoff_location || 'Lollapalooza'"></div>
                        </div>
                    </div>
                    <!-- Lista passeggeri raggruppati per hotel di destinazione -->
                    <template x-for="grp in currentSlot.groups" :key="grp.key">
                        <div class="card overflow-hidden">
                            <div class="p-3 bg-sky-500/8 border-b border-sky-500/15 flex items-center gap-2 flex-wrap">
                                <div class="flex-1 min-w-0">
                                    <div class="text-[10px] text-sky-300 font-bold uppercase tracking-wider" x-text="t('drop_at')"></div>
                                    <div class="font-bold text-base leading-tight" x-text="grp.hotel || '—'"></div>
                                </div>
                                <div class="px-2.5 py-1 rounded-lg bg-sky-500/20 text-sky-300 text-xs font-bold">
                                    <span x-text="grp.totalPax"></span>
                                    <span x-text="' '+(grp.totalPax==1?t('person'):t('persons'))"></span>
                                </div>
                                <a x-show="grp.mapsTarget" :href="mapsUrl(grp.mapsTarget)" target="_blank" class="px-2.5 py-1 rounded-lg bg-sky-500/20 hover:bg-sky-500/30 text-sky-300 text-xs font-bold">🗺 Maps</a>
                            </div>
                            <div class="divide-y divide-white/5">
                                <template x-for="entry in grp.entries" :key="entry.key">
                                    <div class="p-3 flex items-center gap-2">
                                        <button @click="cycleStatus(entry)" :class="stateChipClass(entry.status)" class="flex-shrink-0 w-9 h-9 rounded-full flex items-center justify-center font-bold text-base" :title="statusLabel(entry.status)">
                                            <span x-text="stateChipIcon(entry.status)"></span>
                                        </button>
                                        <div class="flex-1 min-w-0">
                                            <div class="font-semibold leading-tight" x-text="entry.customer_name"></div>
                                            <div class="text-[11px] text-slate-400 flex flex-wrap gap-x-2">
                                                <span x-show="entry.passengers" x-text="'👤×'+entry.passengers"></span>
                                                <span x-show="entry.language" x-text="langFlag(entry.language)+' '+langLabel(entry.language)"></span>
                                            </div>
                                            <div x-show="entry.notes" class="text-[11px] text-amber-300 mt-0.5 truncate" x-text="'📝 '+entry.notes"></div>
                                        </div>
                                        <a x-show="entry.phone" :href="'tel:'+entry.phone" class="w-9 h-9 rounded-full bg-sky-500/20 text-sky-300 flex items-center justify-center text-base">📞</a>
                                        <a x-show="entry.phone" :href="waUrl(entry.phone, entry.language, entry.leg)" target="_blank" class="w-9 h-9 rounded-full bg-emerald-500/20 text-emerald-300 flex items-center justify-center text-base">💬</a>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            <!-- Azioni bulk -->
            <div class="mt-4 grid grid-cols-2 gap-2" x-show="currentSlot.entries.length">
                <button @click="bulkSet('picked_up')" class="py-2.5 rounded-xl bg-brand-500/20 text-brand-200 hover:bg-brand-500/30 font-semibold text-xs">
                    <span x-text="'✅ '+t('mark_all_onboard')"></span>
                </button>
                <button @click="bulkSet('completed')" class="py-2.5 rounded-xl bg-emerald-500/20 text-emerald-200 hover:bg-emerald-500/30 font-semibold text-xs">
                    <span x-text="'🏁 '+t('mark_trip_done')"></span>
                </button>
            </div>

            <div class="text-[11px] text-slate-500 text-center mt-4" x-text="t('tap_chip_hint')"></div>
        </div>
    </template>

    <div x-show="!slotsForDay.length" class="text-center py-16 text-slate-400">
        <div class="text-6xl mb-3 opacity-50">😴</div>
        <div class="font-semibold" x-text="t('no_transfers')"></div>
        <div class="text-xs mt-1" x-text="t('enjoy_break')"></div>
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
    open_maps:'Apri in Maps', notes:'Note',
    call:'Chiama', whatsapp:'WhatsApp',
    s_todo:'Da fare', s_onway:'In viaggio', s_picked:'A bordo', s_done:'Fatto', s_noshow:'No-show',
    no_transfers:'Nessun transfer per oggi', enjoy_break:'Goditi la pausa.',
    person:'persona', persons:'persone',
    today:'oggi',
    outbound:'Giro andata · Hotel → Locale',
    return_trip:'Giro ritorno · Locale → Hotel',
    out_short:'ANDATA', ret_short:'RITORNO',
    booking_s:'prenot.', booking_p:'prenot.',
    hotel_s:'hotel', hotel_p:'hotel',
    done_count:'completati',
    drop_at:'Lascia in',
    venue_pickup:'Partenza dal locale',
    mark_all_onboard:'Tutti a bordo',
    mark_trip_done:'Giro completato',
    tap_chip_hint:'Tocca il cerchietto per cambiare stato'
  },
  en: {
    header_title:'Transfer', refresh:'Refresh',
    ride:'ride', rides:'rides',
    open_maps:'Open in Maps', notes:'Notes',
    call:'Call', whatsapp:'WhatsApp',
    s_todo:'To do', s_onway:'On the way', s_picked:'On board', s_done:'Done', s_noshow:'No-show',
    no_transfers:'No transfers for today', enjoy_break:'Enjoy your break.',
    person:'person', persons:'people',
    today:'today',
    outbound:'Outbound · Hotel → Restaurant',
    return_trip:'Return · Restaurant → Hotel',
    out_short:'OUT', ret_short:'RET',
    booking_s:'booking', booking_p:'bookings',
    hotel_s:'hotel', hotel_p:'hotels',
    done_count:'done',
    drop_at:'Drop at',
    venue_pickup:'Pickup at the restaurant',
    mark_all_onboard:'All on board',
    mark_trip_done:'Trip completed',
    tap_chip_hint:'Tap the circle to change status'
  },
  ar: {
    header_title:'النقل', refresh:'تحديث',
    ride:'رحلة', rides:'رحلات',
    open_maps:'فتح في الخرائط', notes:'ملاحظات',
    call:'اتصل', whatsapp:'واتساب',
    s_todo:'قيد الانتظار', s_onway:'في الطريق', s_picked:'على متن السيارة', s_done:'تم', s_noshow:'لم يحضر',
    no_transfers:'لا توجد رحلات اليوم', enjoy_break:'استمتع باستراحتك.',
    person:'شخص', persons:'أشخاص',
    today:'اليوم',
    outbound:'الذهاب · الفندق ← المطعم',
    return_trip:'العودة · المطعم ← الفندق',
    out_short:'ذهاب', ret_short:'عودة',
    booking_s:'حجز', booking_p:'حجوزات',
    hotel_s:'فندق', hotel_p:'فنادق',
    done_count:'تم',
    drop_at:'الإنزال في',
    venue_pickup:'الانطلاق من المطعم',
    mark_all_onboard:'الكل على متن السيارة',
    mark_trip_done:'تم الجولة',
    tap_chip_hint:'انقر على الدائرة لتغيير الحالة'
  }
};

function driverApp(){return {
    transfers: [],
    day: new Date().toISOString().slice(0,10),
    currentSlotKey: null,
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
            let count = 0;
            this.transfers.forEach(x => {
                if (x.pickup_when && x.pickup_when.slice(0,10) === iso && x.status !== 'cancelled') count++;
                if (x.return_when && x.return_when.slice(0,10) === iso && x.return_status !== 'cancelled') count++;
            });
            let dayName;
            if (i===0) dayName = this.t('today');
            else dayName = d.toLocaleDateString(this.locale, {weekday:'short'}).replace('.','');
            out.push({ iso, dayName, dayNum: d.getDate(), count });
        }
        return out;
    },
    // Costruisce entries (una per andata, una per ritorno) per il giorno selezionato
    get entriesForDay(){
        const out = [];
        this.transfers.forEach(x => {
            if (x.pickup_when && x.pickup_when.slice(0,10) === this.day && x.status !== 'cancelled') {
                out.push({
                    key: x.id+'_out', id: x.id, leg: 'out',
                    when: x.pickup_when, time: x.pickup_when.slice(11,16),
                    customer_name: x.customer_name, phone: x.phone, language: x.language,
                    passengers: x.passengers,
                    pickup_location: x.pickup_location, pickup_address: x.pickup_address,
                    dropoff_location: x.dropoff_location, dropoff_address: x.dropoff_address,
                    notes: x.notes, status: x.status,
                    mapsTarget: x.pickup_address || x.pickup_location
                });
            }
            if (x.return_when && x.return_when.slice(0,10) === this.day && x.return_status !== 'cancelled') {
                out.push({
                    key: x.id+'_ret', id: x.id, leg: 'ret',
                    when: x.return_when, time: x.return_when.slice(11,16),
                    customer_name: x.customer_name, phone: x.phone, language: x.language,
                    passengers: x.passengers,
                    pickup_location: x.pickup_location, pickup_address: x.pickup_address,
                    dropoff_location: x.dropoff_location, dropoff_address: x.dropoff_address,
                    notes: x.notes, status: x.return_status,
                    mapsTarget: x.dropoff_address || x.dropoff_location || x.pickup_address || x.pickup_location
                });
            }
        });
        return out;
    },
    // Raggruppa entries per fascia oraria; ogni slot raggruppa internamente per hotel
    get slotsForDay(){
        const slots = {};
        this.entriesForDay.forEach(e => {
            const sk = e.leg + '_' + e.time;
            if (!slots[sk]) slots[sk] = { key: sk, leg: e.leg, time: e.time, entries: [], hotels: {} };
            slots[sk].entries.push(e);
            // chiave hotel: per andata usa pickup_location; per ritorno usa pickup_location del cliente (dove va riportato)
            const hotelKey = (e.leg==='out' ? (e.pickup_location || '—') : (e.pickup_location || '—'));
            const hotelAddr = (e.leg==='out' ? e.pickup_address : e.pickup_address);
            if (!slots[sk].hotels[hotelKey]) {
                slots[sk].hotels[hotelKey] = {
                    key: sk+'_'+hotelKey, hotel: hotelKey, address: hotelAddr || '',
                    entries: [], totalPax: 0,
                    mapsTarget: hotelAddr || hotelKey
                };
            }
            slots[sk].hotels[hotelKey].entries.push(e);
            slots[sk].hotels[hotelKey].totalPax += (parseInt(e.passengers,10) || 1);
        });
        const slotOrder = (k) => {
            const [leg, time] = k.split('_');
            const h = parseInt(time.slice(0,2),10);
            const m = parseInt(time.slice(3,5),10);
            const minutes = h*60+m;
            const ordered = (leg==='ret' && h < 6) ? minutes + 24*60 : minutes;
            return (leg==='out' ? 0 : 100000) + ordered;
        };
        return Object.values(slots).map(s => {
            const groups = Object.values(s.hotels);
            const totalPax = s.entries.reduce((sum,e) => sum + (parseInt(e.passengers,10)||1), 0);
            const doneCount = s.entries.filter(e => e.status==='completed' || e.status==='picked_up').length;
            return { ...s, groups, totalPax, hotelCount: groups.length, doneCount };
        }).sort((a,b) => slotOrder(a.key) - slotOrder(b.key));
    },
    get currentSlot(){
        return this.slotsForDay.find(s => s.key === this.currentSlotKey) || this.slotsForDay[0] || null;
    },
    async init(){
        this.setLang(this.lang);
        // ricalcola fascia preferita quando si cambia giorno
        this.$watch('day', () => this.pickDefaultSlot());
        await this.load();
        setInterval(()=>this.load(), 30000);
    },
    async load(){
        try {
            const r = await fetch('/api/driver_transfers.php?action=list&t='+encodeURIComponent(TOKEN));
            const d = await r.json();
            if (d.transfers) this.transfers = d.transfers;
            this.pickDefaultSlot();
        } catch(e){}
    },
    pickDefaultSlot(){
        const slots = this.slotsForDay;
        if (!slots.length) { this.currentSlotKey = null; return; }
        // se la selezione corrente esiste ancora nel giorno selezionato, tienila
        if (this.currentSlotKey && slots.some(s => s.key === this.currentSlotKey)) return;
        // altrimenti scegli la prima fascia ancora "aperta" (non tutta completata) — o la prima in assoluto
        const open = slots.find(s => s.doneCount < s.entries.length);
        this.currentSlotKey = (open || slots[0]).key;
    },
    async setStatus(entry, s){
        entry.status = s; // ottimistico
        const tr = this.transfers.find(x => x.id === entry.id);
        if (tr) {
            if (entry.leg === 'ret') tr.return_status = s;
            else tr.status = s;
        }
        await fetch('/api/driver_transfers.php?action=set_status&t='+encodeURIComponent(TOKEN), {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({id: entry.id, status: s, leg: entry.leg})
        });
    },
    // Tap sul cerchietto: cicla scheduled → picked_up → completed → scheduled
    cycleStatus(entry){
        const next = entry.status === 'scheduled' ? 'picked_up'
                   : entry.status === 'picked_up' ? 'completed'
                   : entry.status === 'completed' ? 'no_show'
                   : 'scheduled';
        return this.setStatus(entry, next);
    },
    // Cambia stato di tutti i pax non ancora completati nella fascia corrente
    bulkSet(s){
        const slot = this.currentSlot;
        if (!slot) return;
        slot.entries.forEach(e => { if (e.status !== s) this.setStatus(e, s); });
    },
    statusLabel(s){
        return ({scheduled:this.t('s_todo'), on_way:this.t('s_onway'), picked_up:this.t('s_picked'), completed:this.t('s_done'), no_show:this.t('s_noshow'), cancelled:'—'})[s] || s;
    },
    stateChipIcon(s){
        return ({scheduled:'⏰', on_way:'🚗', picked_up:'✅', completed:'🏁', no_show:'❌', cancelled:'—'})[s] || '⏰';
    },
    stateChipClass(s){
        return ({
            scheduled:'bg-white/8 text-slate-300 ring-1 ring-white/10',
            on_way:'bg-amber-500/25 text-amber-200 ring-1 ring-amber-500/30',
            picked_up:'bg-brand-500/30 text-brand-200 ring-2 ring-brand-500/40',
            completed:'bg-emerald-500/30 text-emerald-200 ring-2 ring-emerald-500/40',
            no_show:'bg-rose-500/25 text-rose-200 ring-1 ring-rose-500/30',
            cancelled:'bg-white/5 text-slate-500'
        })[s] || 'bg-white/5 text-slate-400';
    },
    langFlag(l){ return ({it:'🇮🇹',en:'🇬🇧',es:'🇪🇸',fr:'🇫🇷',de:'🇩🇪',ar:'🇸🇦'})[l] || '🌐'; },
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
    // Messaggio WhatsApp generato nella lingua del CLIENTE (non dell'autista). Differenzia andata/ritorno.
    waUrl(phone, lang, leg){
        const isRet = (leg === 'ret');
        const msg = isRet ? ({
            it: 'Salve, sono l autista del Lollapalooza. Sono al locale per riportarla in hotel quando vuole.',
            en: 'Hello, I am the Lollapalooza driver. I am at the restaurant to take you back to your hotel whenever you are ready.',
            es: 'Hola, soy el conductor de Lollapalooza. Estoy en el restaurante para llevarle de vuelta al hotel cuando guste.',
            fr: 'Bonjour, je suis le chauffeur du Lollapalooza. Je suis au restaurant pour vous ramener à votre hôtel quand vous voulez.',
            de: 'Hallo, ich bin der Lollapalooza-Fahrer. Ich bin am Restaurant, um Sie zurück zum Hotel zu bringen, wann immer Sie möchten.',
            ar: 'مرحبًا، أنا سائق لولابالوزا. أنا في المطعم لإعادتكم إلى الفندق متى شئتم.'
        })[lang] : ({
            it: 'Salve, sono l autista del Lollapalooza. La sto raggiungendo all hotel per portarla al ristorante.',
            en: 'Hello, I am the Lollapalooza driver. I am on my way to your hotel to take you to the restaurant.',
            es: 'Hola, soy el conductor de Lollapalooza. Estoy en camino a su hotel para llevarle al restaurante.',
            fr: 'Bonjour, je suis le chauffeur du Lollapalooza. J arrive à votre hôtel pour vous emmener au restaurant.',
            de: 'Hallo, ich bin der Lollapalooza-Fahrer. Ich bin auf dem Weg zu Ihrem Hotel, um Sie zum Restaurant zu bringen.',
            ar: 'مرحبًا، أنا سائق لولابالوزا. أنا في طريقي إلى فندقكم لأخذكم إلى المطعم.'
        })[lang];
        const fallback = isRet ? 'Hello, this is Lollapalooza driver. Ready to take you back to your hotel.' : 'Hello, this is Lollapalooza driver. On my way to your hotel.';
        const clean = (phone||'').replace(/[^0-9+]/g,'').replace(/^\+/,'');
        return 'https://wa.me/'+clean+'?text='+encodeURIComponent(msg || fallback);
    }
}}
</script>
</body>
</html>
