<?php
// Server pubblico dei funnel marketing.
// URL: /f.php?s={slug}  →  mostra SOLO il funnel, nessuna chrome del gestionale.
// I file statici stanno in /funnels/{slug}/ (index.html + assets/).
// La riga DB (tabella funnels) controlla titolo, stato attivo e conteggio visite.

require_once __DIR__ . '/includes/config.php';

$slug = preg_replace('/[^a-z0-9\-]/', '', strtolower((string)($_GET['s'] ?? '')));
$dir  = __DIR__ . '/funnels/' . $slug;

function funnel_404(string $msg = 'Funnel non disponibile'): void {
    http_response_code(404);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="it"><head><meta charset="UTF-8">'
       . '<meta name="viewport" content="width=device-width,initial-scale=1">'
       . '<title>Non disponibile</title><style>'
       . 'body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;'
       . 'background:#0a0908;color:#f4ede0;font-family:system-ui,sans-serif;text-align:center;padding:24px}'
       . 'h1{font-size:22px;margin:0 0 8px}p{color:#b3a892;margin:0}'
       . '</style></head><body><div><h1>' . htmlspecialchars($msg) . '</h1>'
       . '<p>Controlla il link e riprova.</p></div></body></html>';
    exit;
}

if ($slug === '' || !is_file("$dir/index.html")) {
    funnel_404();
}

try {
    $st = db()->prepare('SELECT * FROM funnels WHERE slug = ?');
    $st->execute([$slug]);
    $funnel = $st->fetch();
} catch (Throwable $e) {
    $funnel = null;
}

if (!$funnel || (int)$funnel['active'] !== 1) {
    funnel_404();
}

// Conteggio visite (best-effort, non blocca la pagina se fallisce)
try {
    db()->prepare('UPDATE funnels SET views = views + 1 WHERE id = ?')->execute([$funnel['id']]);
} catch (Throwable $e) {}

// Serve l'HTML del funnel. Inietta <base> così i path relativi (assets/...)
// puntano alla cartella reale del funnel anche se l'URL è /f.php?s=...
$html = (string)file_get_contents("$dir/index.html");
$base = '<base href="/funnels/' . $slug . '/">';
if (preg_match('/<head[^>]*>/i', $html)) {
    $html = preg_replace('/(<head[^>]*>)/i', '$1' . $base, $html, 1);
} else {
    $html = $base . $html;
}

header('Content-Type: text/html; charset=utf-8');
echo $html;
