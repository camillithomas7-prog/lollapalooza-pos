<?php
// Wrapper pubblico per applicare schema + migrazioni + traduzioni.
// Lo script è IDEMPOTENTE: non droppa nulla, applica solo le cose mancanti.
// Apri https://tuo-sito/install.php per lanciarlo.

header('Content-Type: text/plain; charset=utf-8');
echo "=== Lollapalooza POS — Installazione / Migrazione ===\n\n";
require __DIR__ . '/includes/install.php';
echo "\n=== Fatto. Ora puoi tornare al sito. ===\n";
