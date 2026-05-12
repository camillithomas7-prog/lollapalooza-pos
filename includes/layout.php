<?php
function layout_head(string $title = '', string $variant = 'admin') {
    $u = user();
    // i18n: legge cookie e capisce se serve RTL
    if (file_exists(__DIR__ . '/i18n.php')) {
        require_once __DIR__ . '/i18n.php';
        $__lang = current_lang();
        $__rtl  = is_rtl($__lang);
    } else {
        $__lang = 'it';
        $__rtl  = false;
    }
?><!DOCTYPE html>
<html lang="<?= e($__lang) ?>"<?= $__rtl ? ' dir="rtl"' : '' ?>>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title><?= e($title ? "$title – ".APP_NAME : APP_NAME) ?></title>
<link rel="icon" type="image/jpeg" href="/assets/img/logo.jpeg">
<script>
// Applica tema PRIMA del paint per evitare flash
(function(){
  var t = localStorage.getItem('lollab-theme') || 'dark';
  if (t === 'auto') t = matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  document.documentElement.setAttribute('data-theme', t);
  if (t === 'dark') document.documentElement.classList.add('dark');
  document.querySelector('meta[name="theme-color"]')?.setAttribute('content', t==='dark'?'#0a0a13':'#f8fafc');
})();
</script>
<meta name="theme-color" content="#0a0a13">
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/alpinejs@3.13.5/dist/cdn.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
tailwind.config = {
  darkMode: 'class',
  theme: { extend: {
    colors: {
      brand: { 50:'#f5f3ff', 100:'#ede9fe', 200:'#ddd6fe', 400:'#a78bfa', 500:'#8b5cf6', 600:'#7c3aed', 700:'#6d28d9', 900:'#4c1d95' },
      ink: { 900:'#0a0a13', 800:'#10101c', 700:'#1a1a28', 600:'#252535', 500:'#3a3a4d' }
    },
    fontFamily: { sans: ['Inter','system-ui','-apple-system','Segoe UI','Roboto','sans-serif'] },
    boxShadow: { glow: '0 0 0 1px rgba(139,92,246,0.3), 0 8px 32px -4px rgba(139,92,246,0.25)' }
  }}
}
</script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<?php if ($__rtl): ?>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  html[dir="rtl"] body { font-family: 'Cairo', 'Inter', sans-serif; }
  /* Tailwind 3 ha già il supporto logico per molte cose, ma alcune classi
     necessitano flip manuale in RTL */
  html[dir="rtl"] .text-left { text-align: right; }
  html[dir="rtl"] .text-right { text-align: left; }
  html[dir="rtl"] .ml-1, html[dir="rtl"] .ml-2, html[dir="rtl"] .ml-3, html[dir="rtl"] .ml-4 { margin-left:0; }
  html[dir="rtl"] .ml-1 { margin-right: 0.25rem; }
  html[dir="rtl"] .ml-2 { margin-right: 0.5rem; }
  html[dir="rtl"] .ml-3 { margin-right: 0.75rem; }
  html[dir="rtl"] .ml-4 { margin-right: 1rem; }
  html[dir="rtl"] .mr-1, html[dir="rtl"] .mr-2, html[dir="rtl"] .mr-3, html[dir="rtl"] .mr-4 { margin-right:0; }
  html[dir="rtl"] .mr-1 { margin-left: 0.25rem; }
  html[dir="rtl"] .mr-2 { margin-left: 0.5rem; }
  html[dir="rtl"] .mr-3 { margin-left: 0.75rem; }
  html[dir="rtl"] .mr-4 { margin-left: 1rem; }
</style>
<?php endif; ?>
<style>
  /* Variabili tema */
  :root[data-theme="dark"] {
    --bg: #0a0a13; --surface: #10101c; --surface-2: rgba(255,255,255,0.04);
    --text: #e2e8f0; --text-muted: #94a3b8; --text-faint: #64748b;
    --border: rgba(255,255,255,0.08); --border-strong: rgba(255,255,255,0.14);
    --glass: rgba(16,16,28,0.7); --hover: rgba(255,255,255,0.05);
    --grad-1: rgba(139,92,246,0.15); --grad-2: rgba(14,165,233,0.1);
    --shadow: 0 8px 32px -4px rgba(0,0,0,0.4);
    color-scheme: dark;
  }
  :root[data-theme="light"] {
    --bg: #f5f7fb; --surface: #ffffff; --surface-2: rgba(15,23,42,0.03);
    --text: #0f172a; --text-muted: #475569; --text-faint: #64748b;
    --border: rgba(15,23,42,0.08); --border-strong: rgba(15,23,42,0.14);
    --glass: rgba(255,255,255,0.85); --hover: rgba(15,23,42,0.04);
    --grad-1: rgba(139,92,246,0.08); --grad-2: rgba(14,165,233,0.06);
    --shadow: 0 4px 16px -2px rgba(15,23,42,0.08);
    color-scheme: light;
  }
  html, body { overflow-x: hidden; max-width: 100vw; }
  body { font-family: 'Inter', sans-serif; -webkit-font-smoothing: antialiased; background: var(--bg); color: var(--text); min-height: 100vh; transition: background 0.2s, color 0.2s; }
  /* Anti-overflow rules */
  main, header, .card { max-width: 100%; min-width: 0; }
  .card { overflow: hidden; }
  /* Charts responsive (lascia altezza gestita da Chart.js) */
  canvas { max-width: 100% !important; }
  .glass { backdrop-filter: blur(16px); background: var(--glass); border-color: var(--border)!important; }
  .scrollbar-thin::-webkit-scrollbar { width: 6px; height: 6px; }
  .scrollbar-thin::-webkit-scrollbar-thumb { background: rgba(139,92,246,0.3); border-radius: 3px; }
  .anim-pulse-slow { animation: pulse 3s cubic-bezier(0.4,0,0.6,1) infinite; }
  .grad-bg { background: radial-gradient(at 20% 0%, var(--grad-1) 0px, transparent 50%), radial-gradient(at 80% 100%, var(--grad-2) 0px, transparent 50%), var(--bg); }
  .card { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; box-shadow: var(--shadow); }
  :root[data-theme="dark"] .card { background: linear-gradient(135deg, rgba(255,255,255,0.04), rgba(255,255,255,0.01)); }
  .card:hover { border-color: rgba(139,92,246,0.3); }
  .btn-primary { background: linear-gradient(135deg,#7c3aed,#8b5cf6); color: white; }
  .btn-primary:hover { box-shadow: 0 8px 32px -4px rgba(139,92,246,0.4); }
  .menu-link { display:flex; align-items:center; gap:12px; padding:10px 14px; border-radius:10px; color: var(--text-muted); transition:all 0.15s; }
  .menu-link:hover { background: var(--hover); color: var(--text); }
  .menu-link.active { background:linear-gradient(135deg,rgba(124,58,237,0.18),rgba(139,92,246,0.08)); color: var(--text); box-shadow: inset 3px 0 0 #8b5cf6; }
  :root[data-theme="dark"] .menu-link.active { color: #fff; }
  .table-card { transition: all 0.2s; cursor: pointer; user-select: none; color: #fff; }
  .table-card.free { background: linear-gradient(135deg,#064e3b,#022c22); border-color:#10b981; }
  .table-card.occupied { background: linear-gradient(135deg,#7f1d1d,#450a0a); border-color:#ef4444; }
  .table-card.reserved { background: linear-gradient(135deg,#78350f,#451a03); border-color:#f59e0b; }
  .table-card.dirty { background: linear-gradient(135deg,#1e3a8a,#172554); border-color:#3b82f6; }
  :root[data-theme="light"] .table-card.free { background: linear-gradient(135deg,#d1fae5,#a7f3d0); color:#064e3b; }
  :root[data-theme="light"] .table-card.occupied { background: linear-gradient(135deg,#fee2e2,#fecaca); color:#7f1d1d; }
  :root[data-theme="light"] .table-card.reserved { background: linear-gradient(135deg,#fef3c7,#fde68a); color:#78350f; }
  :root[data-theme="light"] .table-card.dirty { background: linear-gradient(135deg,#dbeafe,#bfdbfe); color:#1e3a8a; }
  @media (max-width: 768px) { .hide-mobile { display: none; } }

  /* Override classi Tailwind hardcoded per tema chiaro */
  :root[data-theme="light"] .bg-white\/5 { background-color: rgba(15,23,42,0.04)!important; }
  :root[data-theme="light"] .bg-white\/10 { background-color: rgba(15,23,42,0.06)!important; }
  :root[data-theme="light"] .hover\:bg-white\/5:hover { background-color: rgba(15,23,42,0.04)!important; }
  :root[data-theme="light"] .hover\:bg-white\/10:hover { background-color: rgba(15,23,42,0.08)!important; }
  :root[data-theme="light"] .border-white\/5 { border-color: rgba(15,23,42,0.06)!important; }
  :root[data-theme="light"] .border-white\/10 { border-color: rgba(15,23,42,0.1)!important; }
  :root[data-theme="light"] .text-slate-200 { color: #1e293b!important; }
  :root[data-theme="light"] .text-slate-300 { color: #334155!important; }
  :root[data-theme="light"] .text-slate-400 { color: #64748b!important; }
  :root[data-theme="light"] .text-slate-500 { color: #94a3b8!important; }
  :root[data-theme="light"] .bg-ink-900,
  :root[data-theme="light"] .bg-ink-800 { background-color: var(--surface)!important; }
  :root[data-theme="light"] .bg-black\/70 { background-color: rgba(15,23,42,0.5)!important; }
  :root[data-theme="light"] .bg-black\/60 { background-color: rgba(15,23,42,0.4)!important; }
  :root[data-theme="light"] input, :root[data-theme="light"] select, :root[data-theme="light"] textarea { color: var(--text); }

  /* Theme switcher pill */
  .theme-toggle { display:inline-flex; align-items:center; gap:2px; padding:3px; border-radius:999px; background: var(--surface-2); border: 1px solid var(--border); }
  .theme-toggle button { width:28px; height:28px; border-radius:999px; display:flex; align-items:center; justify-content:center; font-size:13px; color: var(--text-muted); transition: all 0.15s; }
  .theme-toggle button.active { background: linear-gradient(135deg,#7c3aed,#8b5cf6); color:#fff; box-shadow: 0 2px 8px -2px rgba(139,92,246,0.4); }
  @media (max-width: 480px) {
    .theme-toggle button { width:26px; height:26px; font-size:12px; }
  }
</style>
</head>
<body class="min-h-screen grad-bg">
<script>
function setTheme(t){
  localStorage.setItem('lollab-theme', t);
  var applied = t==='auto' ? (matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light') : t;
  document.documentElement.setAttribute('data-theme', applied);
  document.documentElement.classList.toggle('dark', applied==='dark');
  document.querySelector('meta[name="theme-color"]')?.setAttribute('content', applied==='dark'?'#0a0a13':'#f8fafc');
  document.querySelectorAll('[data-theme-btn]').forEach(b => b.classList.toggle('active', b.dataset.themeBtn === t));
  window.dispatchEvent(new CustomEvent('theme-change', {detail:{theme:applied}}));
}
document.addEventListener('DOMContentLoaded', function(){
  var t = localStorage.getItem('lollab-theme') || 'dark';
  document.querySelectorAll('[data-theme-btn]').forEach(b => b.classList.toggle('active', b.dataset.themeBtn === t));
});
</script>
<?php
}

function theme_switcher(): string {
    return '<div class="theme-toggle" title="Tema">'
         . '<button data-theme-btn="light" onclick="setTheme(\'light\')" title="Chiaro">☀️</button>'
         . '<button data-theme-btn="auto" onclick="setTheme(\'auto\')" title="Auto">🖥</button>'
         . '<button data-theme-btn="dark" onclick="setTheme(\'dark\')" title="Scuro">🌙</button>'
         . '</div>';
}

/**
 * Selettore lingua compatto (bandierine).
 * $langs = array di codici lingua (es. ['it','en','ar'] per le KDS).
 * Cambio lingua: ricarica la pagina con ?lang=xx (i18n.php salva nel cookie).
 */
function lang_switcher(array $langs = ['it','en','ar']): string {
    if (!defined('SUPPORTED_LANGS')) return '';
    $cur = function_exists('current_lang') ? current_lang() : 'it';
    $flags = [
        'it' => '🇮🇹', 'en' => '🇬🇧', 'es' => '🇪🇸',
        'fr' => '🇫🇷', 'de' => '🇩🇪', 'ar' => '🇸🇦',
    ];
    $codes = ['it'=>'IT','en'=>'EN','es'=>'ES','fr'=>'FR','de'=>'DE','ar'=>'AR'];
    $html = '<div class="theme-toggle" title="Lingua / Language / اللغة" style="padding:3px 6px;">';
    foreach ($langs as $code) {
        if (!in_array($code, SUPPORTED_LANGS, true)) continue;
        $active = $code === $cur ? ' active' : '';
        $url = '?' . http_build_query(array_merge($_GET, ['lang' => $code]));
        $flag = $flags[$code] ?? '';
        $label = $codes[$code] ?? strtoupper($code);
        $html .= '<a href="' . htmlspecialchars($url, ENT_QUOTES) . '" class="' . trim($active) . '" '
              .  'style="display:inline-flex;align-items:center;gap:3px;height:28px;padding:0 8px;border-radius:999px;font-size:11px;font-weight:700;color:var(--text-muted);text-decoration:none;">'
              .  $flag . ' ' . $label
              .  '</a>';
    }
    $html .= '</div>';
    return $html;
}

function sidebar_links(string $role): array {
    $items = [
        'admin' => [
            ['dashboard', 'Dashboard', '📊'],
            ['tables', 'Tavoli', '🪑'],
            ['orders', 'Ordini', '🧾'],
            ['menu', 'Menu', '📖'],
            ['cash', 'Cassa', '💰'],
            ['inventory', 'Magazzino', '📦'],
            ['finance', 'Bilancio', '📈'],
            ['staff', 'Personale', '👥'],
            ['reservations', 'Prenotazioni', '📅'],
            ['customers', 'Clienti', '⭐'],
            ['reports', 'Report', '📄'],
            ['qrcodes', 'QR Tavoli', '📱'],
            ['settings', 'Impostazioni', '⚙️'],
        ],
        'manager' => [
            ['dashboard', 'Dashboard', '📊'],
            ['tables', 'Tavoli', '🪑'],
            ['orders', 'Ordini', '🧾'],
            ['menu', 'Menu', '📖'],
            ['cash', 'Cassa', '💰'],
            ['inventory', 'Magazzino', '📦'],
            ['staff', 'Personale', '👥'],
            ['reservations', 'Prenotazioni', '📅'],
            ['reports', 'Report', '📄'],
            ['qrcodes', 'QR Tavoli', '📱'],
        ],
        'cassiere' => [
            ['cash', 'Cassa', '💰'],
            ['orders', 'Ordini', '🧾'],
            ['tables', 'Tavoli', '🪑'],
        ],
        'cameriere' => [
            ['waiter', 'Tavoli', '🪑'],
            ['orders', 'Ordini', '🧾'],
        ],
        'cucina' => [['kitchen', 'Cucina', '👨‍🍳']],
        'bar' => [['bar', 'Bar', '🍸']],
        'magazziniere' => [
            ['inventory', 'Magazzino', '📦'],
            ['suppliers', 'Fornitori', '🚚'],
        ],
    ];
    return $items[$role] ?? [];
}

function layout_sidebar(string $current = '') {
    $u = user();
    $links = sidebar_links($u['role']);
?>
<aside class="hidden md:flex md:w-64 lg:w-72 flex-col fixed inset-y-0 left-0 glass border-r border-white/5 z-40">
    <div class="p-5 flex items-center gap-3 border-b border-white/5">
        <img src="/assets/img/logo.jpeg" class="w-10 h-10 rounded-xl object-cover ring-1 ring-brand-500/30" alt="Lollapalooza">
        <div>
            <div class="font-bold text-lg leading-tight">Lollapalooza</div>
            <div class="text-xs text-slate-400">POS Restaurant</div>
        </div>
    </div>
    <nav class="flex-1 p-3 space-y-1 overflow-y-auto scrollbar-thin">
        <?php foreach ($links as $l): ?>
        <a href="/index.php?p=<?= e($l[0]) ?>" class="menu-link <?= $current===$l[0]?'active':'' ?>">
            <span class="text-lg w-6 text-center"><?= $l[2] ?></span>
            <span class="text-sm font-medium"><?= e($l[1]) ?></span>
        </a>
        <?php endforeach; ?>
    </nav>
    <div class="p-3 border-t border-white/5">
        <div class="flex items-center gap-3 p-3 rounded-xl bg-white/5">
            <div class="w-9 h-9 rounded-full bg-gradient-to-br from-brand-500 to-brand-700 flex items-center justify-center text-sm font-bold">
                <?= strtoupper(mb_substr($u['name'],0,1)) ?>
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-sm font-semibold truncate"><?= e($u['name']) ?></div>
                <div class="text-xs text-slate-400 capitalize"><?= e($u['role']) ?></div>
            </div>
            <a href="/index.php?p=logout" class="text-slate-400 hover:text-rose-400" title="Logout">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            </a>
        </div>
    </div>
</aside>
<?php
}

function layout_topbar(string $title = '', string $subtitle = '', string $extra = '') {
    $u = user();
?>
<header class="md:ml-64 lg:ml-72 sticky top-0 z-30 glass border-b border-white/5">
    <div class="flex items-center justify-between gap-2 px-3 md:px-8 py-3 md:py-4">
        <div class="flex items-center gap-2 md:hidden min-w-0 flex-1">
            <img src="/assets/img/logo.jpeg" class="w-8 h-8 rounded-lg object-cover flex-shrink-0" alt="Lollapalooza">
            <div class="font-bold text-sm truncate"><?= e($title ?: 'Lollapalooza') ?></div>
        </div>
        <div class="hidden md:block min-w-0">
            <h1 class="text-xl font-bold tracking-tight truncate"><?= e($title) ?></h1>
            <?php if ($subtitle): ?><p class="text-xs text-slate-400 mt-0.5 truncate"><?= e($subtitle) ?></p><?php endif; ?>
        </div>
        <div class="flex items-center gap-1.5 md:gap-2 flex-shrink-0">
            <?= $extra ?>
            <div x-data="{ n: 0 }" x-init="
                async function load(){ const r = await fetch('/api/notifications.php?action=unread'); const d = await r.json(); n = d.count || 0; }
                load(); setInterval(load, 8000);"
                class="relative">
                <button class="relative p-1.5 md:p-2 rounded-xl bg-white/5 hover:bg-white/10 text-sm md:text-base">
                    🔔
                    <span x-show="n>0" x-text="n" class="absolute -top-1 -right-1 bg-rose-500 text-white text-[10px] rounded-full w-4 h-4 flex items-center justify-center font-bold"></span>
                </button>
            </div>
            <?= theme_switcher() ?>
            <a href="/index.php?p=logout" class="md:hidden p-1.5 rounded-xl bg-white/5 hover:bg-white/10 text-sm" title="Esci">⏻</a>
        </div>
    </div>
</header>
<?php
}

function layout_mobile_nav(string $current = '') {
    $u = user();
    $links = sidebar_links($u['role']);
?>
<nav class="md:hidden fixed bottom-0 inset-x-0 glass border-t border-white/5 z-40 px-2 py-2 flex gap-1 overflow-x-auto scrollbar-thin">
    <?php foreach ($links as $l): ?>
    <a href="/index.php?p=<?= e($l[0]) ?>" class="flex-shrink-0 flex flex-col items-center gap-0.5 px-3 py-1.5 rounded-lg text-xs <?= $current===$l[0]?'bg-brand-500/20 text-white':'text-slate-400' ?>">
        <span class="text-base"><?= $l[2] ?></span>
        <span><?= e($l[1]) ?></span>
    </a>
    <?php endforeach; ?>
</nav>
<?php
}

function layout_flashes() {
    foreach (flashes() as $f) {
        $cls = match ($f['type']) {
            'success' => 'bg-emerald-500/10 border-emerald-500/30 text-emerald-300',
            'error' => 'bg-rose-500/10 border-rose-500/30 text-rose-300',
            default => 'bg-sky-500/10 border-sky-500/30 text-sky-300',
        };
        echo '<div class="px-4 py-3 mb-4 rounded-xl border '.$cls.'">'.e($f['msg']).'</div>';
    }
}

function layout_foot() {
?>
</body></html>
<?php
}
