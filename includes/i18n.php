<?php
// i18n minimal — usato in menu pubblico + admin + KDS cucina/bar
const SUPPORTED_LANGS = ['it','en','es','fr','de','ar'];
const LANG_LABELS = ['it'=>'🇮🇹 Italiano','en'=>'🇬🇧 English','es'=>'🇪🇸 Español','fr'=>'🇫🇷 Français','de'=>'🇩🇪 Deutsch','ar'=>'🇸🇦 العربية'];
// Lingue right-to-left
const RTL_LANGS = ['ar'];

function is_rtl(?string $lang = null): bool {
    return in_array($lang ?: current_lang(), RTL_LANGS, true);
}

function current_lang(): string {
    if (isset($_GET['lang']) && in_array($_GET['lang'], SUPPORTED_LANGS, true)) {
        setcookie('lollab_lang', $_GET['lang'], time()+86400*365, '/');
        return $_GET['lang'];
    }
    $c = $_COOKIE['lollab_lang'] ?? '';
    if (in_array($c, SUPPORTED_LANGS, true)) return $c;
    return 'it';
}

function t(string $key, ?string $lang = null): string {
    static $cache = [];
    $lang = $lang ?: current_lang();
    if (!isset($cache[$lang])) {
        $f = __DIR__ . "/lang/$lang.php";
        $cache[$lang] = file_exists($f) ? require $f : [];
        if ($lang !== 'it') {
            $fit = __DIR__ . '/lang/it.php';
            if (file_exists($fit)) $cache[$lang] += require $fit;
        }
    }
    return $cache[$lang][$key] ?? $key;
}

/** Estrai una traduzione da un array translations JSON di un prodotto/categoria */
function tr_field(?string $translationsJson, string $field, string $fallback = '', ?string $lang = null): string {
    $lang = $lang ?: current_lang();
    if ($lang === 'it') return $fallback;
    if (!$translationsJson) return $fallback;
    $tr = json_decode($translationsJson, true);
    if (!is_array($tr)) return $fallback;
    return $tr[$lang][$field] ?? $fallback;
}
