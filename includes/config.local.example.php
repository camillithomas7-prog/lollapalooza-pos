<?php
// Lollapalooza POS - Configurazione locale (NON committare)
// Copia in config.local.php e personalizza
//
// • In LOCALE: lascia driver "sqlite", il DB viene creato in /data/lollab.sqlite
// • Su HOSTINGER: usa "mysql" con le credenziali del DB creato dal pannello
return [
    'db' => [
        'driver'   => 'sqlite',     // 'sqlite' (default locale) | 'mysql' (Hostinger)
        'host'     => 'localhost',
        'database' => '',           // es. u749757264_LPapp
        'username' => '',           // es. u749757264_LPapp
        'password' => '',
        'charset'  => 'utf8mb4',
    ],
    // Auto-traduzione menu via OpenAI (gpt-4o-mini)
    'openai_key' => '', // sk-...
];
