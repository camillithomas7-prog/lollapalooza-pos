<?php
/**
 * Genera al volo l'immagine PNG di una gift card.
 * URL pubblico (no auth): /gift_card.php?code=LOL-XXXX-XXXX[&dl=1]
 *
 * Design: 1080x1350 (4:5, ideale per WhatsApp / Instagram feed)
 * Nero + dorato, logo + nome + data + codice univoco + scritta
 * "VALIDA SOLO PER QUESTO GIORNO" molto evidente.
 */
require_once __DIR__ . '/includes/config.php';

if (!extension_loaded('gd')) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'GD extension required';
    exit;
}

$code = strtoupper(trim($_GET['code'] ?? ''));
if (!$code) { http_response_code(400); exit('Code missing'); }

$st = db()->prepare('SELECT g.*, t.name AS tenant_name FROM gift_cards g LEFT JOIN tenants t ON t.id=g.tenant_id WHERE g.code=?');
$st->execute([$code]);
$g = $st->fetch();
if (!$g) { http_response_code(404); exit('Not found'); }

// === Dimensioni e colori ===================================================
$W = 1080;
$H = 1350;
$im = imagecreatetruecolor($W, $H);
imagesavealpha($im, true);

// Palette
$black   = imagecolorallocate($im, 10, 9, 8);
$black2  = imagecolorallocate($im, 19, 17, 14);
$panel   = imagecolorallocate($im, 26, 23, 20);
$gold    = imagecolorallocate($im, 201, 162, 75);
$gold_l  = imagecolorallocate($im, 227, 197, 133);
$gold_d  = imagecolorallocate($im, 154, 122, 50);
$cream   = imagecolorallocate($im, 243, 236, 226);
$muted   = imagecolorallocate($im, 201, 189, 166);
$red     = imagecolorallocate($im, 192, 81, 46);

// === Sfondo gradient verticale =============================================
for ($y = 0; $y < $H; $y++) {
    $t = $y / $H;
    $r = (int)(10 + (26 - 10) * $t);
    $gn = (int)(9  + (23 - 9)  * $t);
    $b = (int)(8  + (20 - 8)  * $t);
    $c = imagecolorallocate($im, $r, $gn, $b);
    imageline($im, 0, $y, $W, $y, $c);
}

// === Cornice dorata interna ================================================
$pad = 38;
imagesetthickness($im, 2);
imagerectangle($im, $pad, $pad, $W - $pad, $H - $pad, $gold_d);
// cornice doppia (interna sottile)
$pad2 = $pad + 10;
imagesetthickness($im, 1);
imagerectangle($im, $pad2, $pad2, $W - $pad2, $H - $pad2, $gold_d);

// === Decorazioni angolari (piccoli archi dorati) ===========================
$cornerSize = 60;
imagesetthickness($im, 3);
// top-left
imageline($im, $pad+24, $pad+8, $pad+24+$cornerSize, $pad+8, $gold);
imageline($im, $pad+8,  $pad+24, $pad+8, $pad+24+$cornerSize, $gold);
// top-right
imageline($im, $W-$pad-24-$cornerSize, $pad+8, $W-$pad-24, $pad+8, $gold);
imageline($im, $W-$pad-8, $pad+24, $W-$pad-8, $pad+24+$cornerSize, $gold);
// bottom-left
imageline($im, $pad+24, $H-$pad-8, $pad+24+$cornerSize, $H-$pad-8, $gold);
imageline($im, $pad+8,  $H-$pad-24-$cornerSize, $pad+8, $H-$pad-24, $gold);
// bottom-right
imageline($im, $W-$pad-24-$cornerSize, $H-$pad-8, $W-$pad-24, $H-$pad-8, $gold);
imageline($im, $W-$pad-8, $H-$pad-24-$cornerSize, $W-$pad-8, $H-$pad-24, $gold);

// === Font ==================================================================
$F_DISPLAY = __DIR__ . '/assets/fonts/Gloock-Regular.ttf';
$F_BOLD    = __DIR__ . '/assets/fonts/WorkSans-Bold.ttf';
$F_REG     = __DIR__ . '/assets/fonts/WorkSans-Regular.ttf';

// Helper: testo centrato
function text_centered($im, $font, $size, $y, $text, $color, $W) {
    $bbox = imagettfbbox($size, 0, $font, $text);
    $textWidth = $bbox[2] - $bbox[0];
    $x = (int)(($W - $textWidth) / 2 - $bbox[0]);
    imagettftext($im, $size, 0, $x, $y, $color, $font, $text);
}

// === Logo (in alto centrato) ===============================================
$logoPath = __DIR__ . '/assets/img/logo.jpeg';
if (file_exists($logoPath)) {
    $logo = @imagecreatefromjpeg($logoPath);
    if ($logo) {
        $lw = imagesx($logo);
        $lh = imagesy($logo);
        $size = 140;
        $lx = (int)(($W - $size) / 2);
        $ly = $pad + 50;
        imagecopyresampled($im, $logo, $lx, $ly, 0, 0, $size, $size, $lw, $lh);
        imagedestroy($logo);
        // bordo dorato sotto il logo
        $cx = (int)($W / 2);
        $ly2 = $ly + $size + 14;
        imagefilledrectangle($im, $cx - 26, $ly2, $cx + 26, $ly2 + 2, $gold);
    }
}

// === Header LOLLAPALOOZA ===================================================
text_centered($im, $F_DISPLAY, 34, $pad + 50 + 140 + 60, 'LOLLAPALOOZA', $cream, $W);
text_centered($im, $F_REG, 13, $pad + 50 + 140 + 80, 'SHARM EL SHEIKH · CUCINA ITALIANA', $muted, $W);

// === Titolo GIFT CARD ======================================================
text_centered($im, $F_REG, 16, 460, 'G I F T   C A R D', $gold, $W);

// linea decorativa
imagesetthickness($im, 1);
imageline($im, (int)($W/2)-100, 482, (int)($W/2)+100, 482, $gold_d);

// === Sconto -10% (BIG) =====================================================
$pct = number_format((float)$g['percent'], 0);
text_centered($im, $F_DISPLAY, 180, 650, '-' . $pct . '%', $gold, $W);
text_centered($im, $F_REG, 18, 690, 'di sconto sul conto', $cream, $W);

// === Nome cliente ==========================================================
$name = mb_strtoupper($g['customer_name'], 'UTF-8');
// se il nome è troppo lungo, riduci il font
$nameSize = 36;
$bbox = imagettfbbox($nameSize, 0, $F_DISPLAY, $name);
while (($bbox[2] - $bbox[0]) > ($W - 200) && $nameSize > 20) {
    $nameSize -= 2;
    $bbox = imagettfbbox($nameSize, 0, $F_DISPLAY, $name);
}
text_centered($im, $F_REG, 12, 780, 'INTESTATA A', $gold, $W);
text_centered($im, $F_DISPLAY, $nameSize, 830, $name, $cream, $W);

// === Validità (BEN EVIDENTE) ===============================================
$dateFmt = date('d/m/Y', strtotime($g['valid_date']));
$weekday = ['Domenica','Lunedì','Martedì','Mercoledì','Giovedì','Venerdì','Sabato'][date('w', strtotime($g['valid_date']))];

// box rosso con scritta valida solo per la data
$boxY1 = 900;
$boxY2 = 1050;
$boxX1 = 110;
$boxX2 = $W - 110;
// sfondo box (translucido oro)
imagefilledrectangle($im, $boxX1, $boxY1, $boxX2, $boxY2, $panel);
imagesetthickness($im, 2);
imagerectangle($im, $boxX1, $boxY1, $boxX2, $boxY2, $gold);

text_centered($im, $F_BOLD, 16, $boxY1 + 35, '⚠  VALIDA SOLO PER QUESTA DATA', $gold_l, $W);
text_centered($im, $F_DISPLAY, 44, $boxY1 + 100, $weekday . ' ' . $dateFmt, $cream, $W);

// === Codice ================================================================
text_centered($im, $F_REG, 12, 1110, 'CODICE GIFT CARD', $gold, $W);
text_centered($im, $F_BOLD, 30, 1155, $g['code'], $cream, $W);

// === Footer ================================================================
text_centered($im, $F_REG, 14, 1230, 'Mostrare alla cassa il giorno della visita', $muted, $W);
text_centered($im, $F_REG, 11, 1258, 'Non cumulabile · 1 sola consumazione · non convertibile in denaro', $muted, $W);

// === Output ================================================================
if (!empty($_GET['dl'])) {
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="GiftCard_' . $g['code'] . '.png"');
} else {
    header('Content-Type: image/png');
    // cache: l'immagine è statica per codice
    header('Cache-Control: public, max-age=86400');
}
imagepng($im);
imagedestroy($im);
