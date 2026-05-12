<?php
// Wrapper pubblico per lanciare /includes/install.php (la cartella includes/
// non è accessibile dal web su Hostinger per motivi di sicurezza).
// Apri https://tuo-sito/install.php → applica schema + migrazioni + traduzioni.

header('Content-Type: text/plain; charset=utf-8');
echo "=== Lollapalooza POS — Installazione / Migrazione ===\n\n";
require __DIR__ . '/includes/install.php';
echo "\n=== Fatto. Ora puoi tornare al sito. ===\n";
