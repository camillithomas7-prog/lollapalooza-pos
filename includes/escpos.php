<?php
// ESC/POS ticket builder.
//
// Genera la sequenza di bytes raw che una stampante termica 58/80mm
// con linguaggio ESC/POS (Epson, Star, Xprinter, Munbyn, ecc.) sa stampare.
// Restituiamo la stringa come Base64 nel campo print_jobs.payload, e il
// ponte (browser via Bluetooth, oppure agente locale via USB/LAN) la
// decodifica e la invia byte per byte alla stampante.

namespace LollabEscPos;

const ESC = "\x1b";
const GS  = "\x1d";
const LF  = "\n";

function init(): string      { return ESC . "@"; }                 // Reset stampante

/**
 * Cambia il codepage attivo sulla stampante (ESC t n).
 * Codepage utili:
 *   0  = CP437  (Latin USA)
 *   16 = CP1252 (Latin Windows EU)
 *   19 = CP858  (Latin con €) — default per scontrini italiani
 *   22 = CP864  (Arabo OEM) — diffuso su stampanti egiziane
 *   29 = CP1256 (Arabo Windows) — alternativa moderna
 * NB: il numero esatto dipende dalla stampante. Per le Xprinter/Goojprt
 *     vendute in Egitto di solito 22 (CP864) funziona out-of-the-box.
 */
function setCodepage(int $n): string { return ESC . "t" . chr(max(0, min(255, $n))); }

/**
 * Mappa nome leggibile codepage → numero ESC t + nome iconv.
 */
const CODEPAGE_MAP = [
    'cp858'  => ['esc' => 19, 'iconv' => 'CP858'],   // Latin1 + euro
    'cp1252' => ['esc' => 16, 'iconv' => 'CP1252'],
    'cp437'  => ['esc' => 0,  'iconv' => 'CP437'],
    'cp864'  => ['esc' => 22, 'iconv' => 'CP864'],   // Arabo OEM (default Egitto)
    'cp1256' => ['esc' => 29, 'iconv' => 'CP1256'],  // Arabo Windows
];

function isArabicCodepage(string $cp): bool {
    return in_array(strtolower($cp), ['cp864', 'cp1256'], true);
}

/**
 * Converte testo UTF-8 nei byte attesi dal codepage della stampante.
 * Se la conversione fallisce (carattere non rappresentabile), usa //IGNORE.
 */
function encodeForPrinter(string $utf8, string $codepage = 'cp858'): string {
    $cp = CODEPAGE_MAP[strtolower($codepage)] ?? CODEPAGE_MAP['cp858'];
    $target = $cp['iconv'];
    if ($target === 'CP858' || $target === 'CP1252' || $target === 'CP437') {
        // Codepage latini: prima asciify (rimuove accenti italiani che non sono in CP437)
        $utf8 = asciify($utf8);
    }
    $out = @iconv('UTF-8', $target . '//IGNORE', $utf8);
    return $out === false ? $utf8 : $out;
}
function alignLeft(): string  { return ESC . "a" . "\x00"; }
function alignCenter(): string{ return ESC . "a" . "\x01"; }
function alignRight(): string { return ESC . "a" . "\x02"; }
function boldOn(): string     { return ESC . "E" . "\x01"; }
function boldOff(): string    { return ESC . "E" . "\x00"; }
function double(): string     { return GS  . "!" . "\x11"; }       // Doppio H + V
function doubleH(): string    { return GS  . "!" . "\x10"; }       // Doppio H
function normal(): string     { return GS  . "!" . "\x00"; }
function underlineOn(): string{ return ESC . "-" . "\x01"; }
function underlineOff(): string{return ESC . "-" . "\x00"; }
function feed(int $n = 1): string { return str_repeat(LF, max(1, $n)); }
function cut(): string        { return GS  . "V" . "\x00"; }       // Taglio completo
function partialCut(): string { return GS  . "V" . "\x01"; }
function beep(int $times = 1, int $ms = 4): string {
    // ESC B n t : n beep ognuno di durata t*100ms (Epson/compatibili)
    return ESC . "B" . chr(min(9, max(1, $times))) . chr(min(9, max(1, $ms)));
}

// Linea separatore. 32 caratteri = 58mm, 48 caratteri = 80mm. Default 32 (safe).
function separator(int $cols = 32, string $char = '-'): string {
    return str_repeat($char, $cols) . LF;
}

// Spalla riga: nome a sinistra, valore a destra, padding con spazi.
function row(string $left, string $right, int $cols = 32): string {
    $left  = mb_substr($left, 0, $cols - mb_strlen($right) - 1);
    $pad   = $cols - mb_strlen($left) - mb_strlen($right);
    if ($pad < 1) $pad = 1;
    return $left . str_repeat(' ', $pad) . $right . LF;
}

// Word-wrap a $cols caratteri (utile per nomi articolo / note lunghe).
function wrap(string $text, int $cols = 32): string {
    $text = preg_replace('/\s+/', ' ', trim($text));
    if ($text === '') return '';
    return wordwrap($text, $cols, LF, true) . LF;
}

// Codifica ASCII estesa per stampanti termiche (sostituisce accenti).
// La maggior parte delle stampanti supporta CP858/CP437; per evitare problemi
// di codepage convertiamo gli accenti italiani a equivalenti senza accento.
function asciify(string $s): string {
    $map = [
        'à'=>'a','á'=>'a','â'=>'a','ã'=>'a','ä'=>'a','å'=>'a',
        'è'=>'e','é'=>'e','ê'=>'e','ë'=>'e',
        'ì'=>'i','í'=>'i','î'=>'i','ï'=>'i',
        'ò'=>'o','ó'=>'o','ô'=>'o','õ'=>'o','ö'=>'o',
        'ù'=>'u','ú'=>'u','û'=>'u','ü'=>'u',
        'ñ'=>'n','ç'=>'c',
        'À'=>'A','Á'=>'A','È'=>'E','É'=>'E','Ì'=>'I','Í'=>'I','Ò'=>'O','Ó'=>'O','Ù'=>'U','Ú'=>'U',
        '€'=>'EUR','’'=>"'", '‘'=>"'", '“'=>'"', '”'=>'"', '–'=>'-', '—'=>'-',
    ];
    return strtr($s, $map);
}

/**
 * Genera la comanda di cucina/bar.
 *
 * @param array $order  Riga della tabella orders + dati joinati.
 *                      Chiavi attese: code, table_code, waiter_name, customer_name, guests, notes
 * @param array $items  Lista order_items: [{name, qty, notes, variants, extras}, ...]
 * @param string $destLabel  'CUCINA' o 'BAR'
 * @param array $opts   ['cols' => 32, 'tenant_name' => 'Lollab', 'beep' => true]
 * @return string  Sequenza ESC/POS pronta da inviare alla stampante.
 */
function buildKitchenTicket(array $order, array $items, string $destLabel = 'CUCINA', array $opts = []): string {
    $cols     = (int)($opts['cols'] ?? 32);
    $tenant   = (string)($opts['tenant_name'] ?? 'Ristorante');
    $useBeep  = (bool)($opts['beep'] ?? true);
    $codepage = strtolower((string)($opts['codepage'] ?? 'cp858'));
    $rtl      = isArabicCodepage($codepage);

    // Etichette localizzate in arabo se la stampa è in arabo
    if ($rtl) {
        $L_table   = 'طاولة';
        $L_guests  = 'الضيوف';
        $L_order   = 'الطلب';
        $L_waiter  = 'النادل';
        $L_notes   = 'ملاحظات الطاولة';
        $L_extra   = '+';
        $L_itnote  = '←';
    } else {
        $L_table   = 'TAVOLO';
        $L_guests  = 'Coperti';
        $L_order   = 'Ord.';
        $L_waiter  = 'Cam.';
        $L_notes   = 'NOTE TAVOLO';
        $L_extra   = '+';
        $L_itnote  = '>>';
    }

    $tableCode = $order['table_code'] ?? ($order['code'] ?? '—');
    $waiter    = $order['waiter_name'] ?? '—';
    $orderCode = $order['code'] ?? ('#' . ($order['id'] ?? '?'));
    $guests    = (int)($order['guests'] ?? 0);
    $note      = trim((string)($order['notes'] ?? ''));
    $now       = date('d/m H:i');

    // Helper interno: encode una stringa UTF-8 nel codepage della stampante
    $enc = function (string $s) use ($codepage) { return encodeForPrinter($s, $codepage); };

    $out = init();
    // Imposta codepage sulla stampante (essenziale per arabo)
    $cpNum = CODEPAGE_MAP[$codepage]['esc'] ?? 19;
    $out .= setCodepage($cpNum);
    if ($useBeep) $out .= beep(2, 3);

    // Intestazione locale
    $out .= alignCenter() . boldOn() . $enc($tenant) . LF . boldOff();
    $out .= alignCenter() . separator($cols, '=');

    // Banner destinazione (CUCINA / BAR / المطبخ / البار) in caratteri doppi
    $out .= alignCenter() . double() . boldOn() . $enc($destLabel) . LF . boldOff() . normal();
    $out .= alignCenter() . separator($cols, '=');

    // Tavolo in caratteri doppi (l'informazione più importante)
    $out .= alignLeft() . double() . boldOn() . $enc($L_table . ' ' . $tableCode) . LF . boldOff() . normal();
    if ($guests > 0) {
        $out .= alignLeft() . $enc($L_guests . ': ' . $guests) . LF;
    }

    $out .= alignLeft() . $enc($L_order . ' ' . $orderCode) . '  ' . $now . LF;
    $out .= alignLeft() . $enc($L_waiter . ' ' . $waiter) . LF;
    $out .= separator($cols, '-');

    // Articoli
    foreach ($items as $it) {
        $qty  = (float)($it['qty'] ?? 1);
        $name = (string)($it['name'] ?? '');
        $qtyStr = ($qty == floor($qty) ? (string)(int)$qty : rtrim(rtrim(number_format($qty, 3, '.', ''), '0'), '.'));
        // Riga articolo: qty x nome (corpo doppio altezza)
        $out .= boldOn() . doubleH();
        $out .= $qtyStr . "x " . $enc(mb_substr($name, 0, max(8, $cols - 6)));
        $out .= LF . normal() . boldOff();
        // Se il nome è lungo, scrivi il resto a normale (può capitare con nomi arabi)
        $remaining = mb_substr($name, max(8, $cols - 6));
        if ($remaining !== '') {
            $out .= "   " . $enc(trim($remaining)) . LF;
        }
        // Varianti / extra
        foreach (['variants', 'extras'] as $extraKey) {
            $extra = trim((string)($it[$extraKey] ?? ''));
            if ($extra !== '') {
                $out .= "   " . $L_extra . ' ' . $enc($extra) . LF;
            }
        }
        // Note per riga
        $itNote = trim((string)($it['notes'] ?? ''));
        if ($itNote !== '') {
            $out .= "   " . $L_itnote . ' ' . $enc($itNote) . LF;
        }
        $out .= LF;
    }

    // Note generali dell'ordine
    if ($note !== '') {
        $out .= separator($cols, '-');
        $out .= boldOn() . $enc($L_notes . ':') . LF . boldOff();
        $out .= $enc(trim($note)) . LF;
    }

    $out .= separator($cols, '=');
    $out .= alignCenter() . $now . LF;
    $out .= feed(4);
    $out .= cut();

    return $out;
}
