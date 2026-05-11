<?php
require_once __DIR__ . '/../../includes/config.php';

$tn = db()->prepare('SELECT * FROM tenants WHERE active=1 ORDER BY id LIMIT 1');
$tn->execute();
$tenant = $tn->fetch();
if (!$tenant) { http_response_code(404); exit('Locale non disponibile'); }

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $guests = (int)($_POST['guests'] ?? 2);
    $notes = trim($_POST['notes'] ?? '');

    if (!$name) $error = 'Inserisci il nome';
    elseif (!$phone && !$email) $error = 'Inserisci almeno un contatto (telefono o email)';
    elseif (!$date || $date < date('Y-m-d')) $error = 'Data non valida';
    elseif (!$time) $error = 'Seleziona un orario';
    elseif ($guests < 1 || $guests > 20) $error = 'Numero coperti non valido';
    else {
        db()->prepare('INSERT INTO reservations (tenant_id, customer_name, phone, email, guests, date, time, notes, status, created_at) VALUES (?,?,?,?,?,?,?,?,?,?)')
            ->execute([$tenant['id'], $name, $phone, $email, $guests, $date, $time, $notes, 'pending', date('Y-m-d H:i:s')]);
        $resId = db()->lastInsertId();
        db()->prepare('INSERT INTO notifications (tenant_id, role, type, title, body, link, created_at) VALUES (?,?,?,?,?,?,?)')
            ->execute([$tenant['id'], 'manager', 'booking', '🗓 Nuova prenotazione', "$name · $guests coperti · $date $time", '/index.php?p=reservations', date('Y-m-d H:i:s')]);
        $success = true;
        $bookedAt = "$date alle $time";
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Prenota un tavolo — <?= e($tenant['name']) ?></title>
<link rel="icon" type="image/jpeg" href="/assets/img/logo.jpeg">
<script>document.documentElement.setAttribute('data-theme','light');</script>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  html,body{overflow-x:hidden;max-width:100vw;}
  body{font-family:'Inter',sans-serif;background:linear-gradient(160deg,#fafafa 0%,#f3f4f6 100%);color:#0f172a;min-height:100vh;}
  .card{background:#fff;border:1px solid rgba(15,23,42,0.06);box-shadow:0 8px 32px -8px rgba(15,23,42,0.08);}
  .btn-primary{background:linear-gradient(135deg,#7c3aed,#8b5cf6);color:white;}
  .btn-primary:hover{box-shadow:0 8px 24px -4px rgba(139,92,246,0.4);}
  input,select,textarea{background:#f9fafb;border:1px solid #e5e7eb;color:#0f172a;}
  input:focus,select:focus,textarea:focus{outline:none;border-color:#8b5cf6;background:#fff;box-shadow:0 0 0 3px rgba(139,92,246,0.1);}
  .chip{padding:8px 14px;border:1px solid #e5e7eb;background:#fff;border-radius:10px;font-weight:600;font-size:14px;cursor:pointer;transition:all 0.15s;}
  .chip.active{background:linear-gradient(135deg,#7c3aed,#8b5cf6);color:#fff;border-color:transparent;box-shadow:0 4px 12px -2px rgba(139,92,246,0.3);}
  .chip:hover:not(.active){border-color:#a78bfa;}
</style>
</head>
<body>
<div class="min-h-screen flex items-center justify-center p-4 py-8">
  <div class="w-full max-w-md">
    <div class="text-center mb-6">
      <img src="/assets/img/logo.jpeg" class="w-16 h-16 mx-auto rounded-2xl object-cover ring-2 ring-violet-200 shadow-lg" alt="Logo">
      <h1 class="text-2xl font-bold mt-3 tracking-tight"><?= e($tenant['name']) ?></h1>
      <p class="text-sm text-slate-500 mt-1">Prenota un tavolo</p>
    </div>

    <?php if ($success): ?>
      <div class="card rounded-2xl p-8 text-center">
        <div class="text-5xl mb-3">✅</div>
        <h2 class="text-xl font-bold mb-2">Prenotazione ricevuta!</h2>
        <p class="text-sm text-slate-500 mb-4">Ti aspettiamo <strong class="text-slate-900"><?= e($bookedAt) ?></strong> per <strong class="text-slate-900"><?= (int)$guests ?> persone</strong>.</p>
        <p class="text-xs text-slate-400 mb-6">Riceverai conferma al più presto via <?= $phone ? 'telefono' : 'email' ?>. Se devi modificare o cancellare contattaci direttamente.</p>
        <?php if (!empty($tenant['phone'])): ?>
          <a href="tel:<?= e($tenant['phone']) ?>" class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-violet-50 text-violet-700 font-semibold text-sm mb-2">📞 <?= e($tenant['phone']) ?></a><br>
        <?php endif; ?>
        <a href="?p=prenota" class="inline-block text-sm text-violet-600 hover:text-violet-700 mt-3">← Nuova prenotazione</a>
      </div>
    <?php else: ?>
      <form method="post" class="card rounded-2xl p-6 space-y-4" x-data="{guests:2}">
        <?php if ($error): ?>
          <div class="p-3 rounded-xl bg-rose-50 text-rose-700 text-sm border border-rose-200">⚠ <?= e($error) ?></div>
        <?php endif; ?>

        <div>
          <label class="text-xs font-semibold text-slate-600 mb-1 block">NOME E COGNOME</label>
          <input type="text" name="name" required value="<?= e($_POST['name'] ?? '') ?>" placeholder="Mario Rossi" class="w-full px-4 py-3 rounded-xl">
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="text-xs font-semibold text-slate-600 mb-1 block">DATA</label>
            <input type="date" name="date" required min="<?= date('Y-m-d') ?>" value="<?= e($_POST['date'] ?? date('Y-m-d')) ?>" class="w-full px-3 py-3 rounded-xl">
          </div>
          <div>
            <label class="text-xs font-semibold text-slate-600 mb-1 block">ORARIO</label>
            <select name="time" required class="w-full px-3 py-3 rounded-xl">
              <?php
              $current = $_POST['time'] ?? '20:00';
              foreach (['12:00','12:30','13:00','13:30','14:00','19:00','19:30','20:00','20:30','21:00','21:30','22:00'] as $t):
              ?>
                <option value="<?= $t ?>" <?= $current === $t ? 'selected' : '' ?>><?= $t ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div>
          <label class="text-xs font-semibold text-slate-600 mb-2 block">COPERTI</label>
          <div class="grid grid-cols-6 gap-2 mb-2">
            <?php foreach ([1,2,3,4,5,6] as $n): ?>
              <button type="button" @click="guests=<?= $n ?>" :class="guests===<?= $n ?>?'chip active':'chip'" class="chip text-center"><?= $n ?></button>
            <?php endforeach; ?>
          </div>
          <input type="number" name="guests" :value="guests" min="1" max="20" class="w-full px-4 py-2 rounded-xl text-sm" placeholder="Più di 6...">
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="text-xs font-semibold text-slate-600 mb-1 block">TELEFONO</label>
            <input type="tel" name="phone" value="<?= e($_POST['phone'] ?? '') ?>" placeholder="+39 333..." class="w-full px-4 py-3 rounded-xl">
          </div>
          <div>
            <label class="text-xs font-semibold text-slate-600 mb-1 block">EMAIL</label>
            <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" placeholder="email@..." class="w-full px-4 py-3 rounded-xl">
          </div>
        </div>
        <p class="text-xs text-slate-500 -mt-2">Almeno uno tra telefono e email è richiesto</p>

        <div>
          <label class="text-xs font-semibold text-slate-600 mb-1 block">NOTE (allergie, occasioni speciali...)</label>
          <textarea name="notes" rows="2" class="w-full px-4 py-3 rounded-xl"><?= e($_POST['notes'] ?? '') ?></textarea>
        </div>

        <button type="submit" class="w-full btn-primary py-3.5 rounded-xl font-bold text-base shadow-lg">Prenota →</button>
      </form>
    <?php endif; ?>

    <div class="text-center mt-6 text-xs text-slate-400">
      <?php if (!empty($tenant['address'])): ?>📍 <?= e($tenant['address']) ?><br><?php endif; ?>
      <?php if (!empty($tenant['phone'])): ?>📞 <?= e($tenant['phone']) ?><?php endif; ?>
    </div>
  </div>
</div>
<script src="https://unpkg.com/alpinejs@3.13.5/dist/cdn.min.js" defer></script>
</body></html>
