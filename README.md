# 🍽 Lollab POS

Gestionale completo per ristoranti, pub, lounge bar.

## Stack

- PHP 8+ / SQLite
- TailwindCSS + Alpine.js + Chart.js (CDN)
- Mobile-first per camerieri
- Realtime via polling (3-8s)

## Avvio locale

```bash
cd ~/lollab-pos
php -S 0.0.0.0:8120
```

Apri http://localhost:8120

## Login demo

| Ruolo | Email | PIN |
|---|---|---|
| Admin | admin@lollab.it | 0000 |
| Manager | manager@lollab.it | 1111 |
| Cassa | cassa@lollab.it | 2222 |
| Cameriere | luca@lollab.it | 3333 |
| Cucina | cucina@lollab.it | 4444 |
| Bar | bar@lollab.it | 5555 |

Password: `lollab2026`

## Struttura

```
lollab-pos/
├── api/           # REST API (tables, menu, orders, cash, inventory, finance, ...)
├── assets/        # CSS, JS, immagini
├── data/          # SQLite db
├── includes/      # config, layout, schema, install
├── pages/
│   ├── admin/     # Dashboard, tavoli, menu, cassa, magazzino, finanza, etc
│   ├── waiter/    # App cameriere mobile
│   ├── kitchen/   # KDS cucina
│   ├── bar/       # KDS bar
│   └── public/    # QR menu pubblico
├── index.php      # Router
└── .htaccess
```

## Deploy Hostinger

1. Comprimi tutta la cartella `~/lollab-pos/` in zip
2. Upload via File Manager Hostinger
3. Estrai nella public_html
4. Visita il dominio → ti porta a `index.php?p=login`
5. Hostinger ha PHP 8 + SQLite di default (nessuna config)

## Funzionalità

- ✅ 7 ruoli con permessi
- ✅ Mappa tavoli drag-and-drop
- ✅ Realtime cucina/bar
- ✅ App cameriere mobile (2 tap → ordine)
- ✅ Cassa con apertura/chiusura
- ✅ Pagamenti split (contanti, carta, POS, voucher)
- ✅ Magazzino con scarico automatico
- ✅ Bilancio entrate/uscite con grafici
- ✅ Report top prodotti, performance camerieri, vendite per ora
- ✅ Prenotazioni
- ✅ CRM clienti con fidelity
- ✅ Multi-locale (tenant)
- ✅ QR menu pubblico per tavolo
- ✅ Notifiche realtime
- ✅ Audit log
- ✅ Multi-lingua e multi-valuta
