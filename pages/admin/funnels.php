<?php
// Sezione Funnel — gestione delle landing page marketing.
// Ogni funnel è una cartella statica in /funnels/{slug}/ (index.html + assets).
// Da qui: caricamento (ZIP), titolo interno, attivazione, link da sponsorizzare.

$pdo = db();
$funnelsDir = BASE_PATH . '/funnels';
if (!is_dir($funnelsDir)) @mkdir($funnelsDir, 0775, true);

function slugify(string $s): string {
    $s = mb_strtolower(trim($s));
    $s = preg_replace('/[àáâãä]/u', 'a', $s);
    $s = preg_replace('/[èéêë]/u', 'e', $s);
    $s = preg_replace('/[ìíîï]/u', 'i', $s);
    $s = preg_replace('/[òóôõö]/u', 'o', $s);
    $s = preg_replace('/[ùúûü]/u', 'u', $s);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-') ?: 'funnel';
}
function rrmdir(string $d): void {
    if (!is_dir($d)) return;
    foreach (array_diff(scandir($d), ['.', '..']) as $f) {
        $p = "$d/$f";
        is_dir($p) ? rrmdir($p) : @unlink($p);
    }
    @rmdir($d);
}

// ───────────────────────── POST ─────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) { flash('error', 'Sessione scaduta, riprova.'); header('Location: /index.php?p=funnels'); exit; }
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $title = trim($_POST['title'] ?? '');
        $slug  = slugify($_POST['slug'] ?: $title);
        $desc  = trim($_POST['description'] ?? '');
        try {
            if ($title === '') throw new Exception('Inserisci un titolo.');
            if (empty($_FILES['zip']['tmp_name']) || $_FILES['zip']['error'] !== UPLOAD_ERR_OK)
                throw new Exception('Carica il file .zip del funnel.');

            $ex = $pdo->prepare('SELECT id FROM funnels WHERE slug = ?');
            $ex->execute([$slug]);
            if ($ex->fetchColumn()) throw new Exception("Esiste già un funnel con slug «$slug». Cambia titolo o slug.");

            $tmp = $funnelsDir . '/.tmp_' . bin2hex(random_bytes(5));
            @mkdir($tmp, 0775, true);
            $zip = new ZipArchive();
            if ($zip->open($_FILES['zip']['tmp_name']) !== true) { rrmdir($tmp); throw new Exception('ZIP non valido.'); }
            $zip->extractTo($tmp);
            $zip->close();

            // Trova index.html: alla radice oppure dentro un'unica sottocartella
            $root = $tmp;
            if (!is_file("$root/index.html")) {
                $subs = array_values(array_filter(glob("$tmp/*", GLOB_ONLYDIR) ?: [],
                    fn($d) => is_file("$d/index.html")));
                if (count($subs) === 1) $root = $subs[0];
            }
            if (!is_file("$root/index.html")) { rrmdir($tmp); throw new Exception('Lo ZIP deve contenere un file index.html.'); }

            $dest = $funnelsDir . '/' . $slug;
            rrmdir($dest);
            if (!rename($root, $dest)) {
                // fallback: copia ricorsiva
                @mkdir($dest, 0775, true);
                $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
                foreach ($it as $item) {
                    $target = $dest . '/' . $it->getSubPathName();
                    $item->isDir() ? @mkdir($target, 0775, true) : @copy($item, $target);
                }
            }
            rrmdir($tmp);

            $now = date('Y-m-d H:i:s');
            file_put_contents("$dest/funnel.json", json_encode(
                ['title' => $title, 'slug' => $slug, 'description' => $desc, 'created_at' => $now],
                JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

            $pdo->prepare('INSERT INTO funnels (tenant_id, slug, title, description, active, views, created_at, updated_at) VALUES (?,?,?,?,1,0,?,?)')
                ->execute([tenant_id(), $slug, $title, $desc, $now, $now]);
            audit('funnel_create', 'funnels', (int)$pdo->lastInsertId());
            flash('success', "Funnel «$title» caricato.");
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        header('Location: /index.php?p=funnels'); exit;
    }

    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $desc  = trim($_POST['description'] ?? '');
        if ($id && $title !== '') {
            $pdo->prepare('UPDATE funnels SET title=?, description=?, updated_at=? WHERE id=? AND tenant_id=?')
                ->execute([$title, $desc, date('Y-m-d H:i:s'), $id, tenant_id()]);
            flash('success', 'Funnel aggiornato.');
        }
        header('Location: /index.php?p=funnels'); exit;
    }

    if ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare('UPDATE funnels SET active = 1 - active, updated_at=? WHERE id=? AND tenant_id=?')
            ->execute([date('Y-m-d H:i:s'), $id, tenant_id()]);
        header('Location: /index.php?p=funnels'); exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $st = $pdo->prepare('SELECT slug FROM funnels WHERE id=? AND tenant_id=?');
        $st->execute([$id, tenant_id()]);
        if ($slug = $st->fetchColumn()) {
            $pdo->prepare('DELETE FROM funnels WHERE id=?')->execute([$id]);
            rrmdir($funnelsDir . '/' . $slug);
            audit('funnel_delete', 'funnels', $id);
            flash('success', 'Funnel eliminato.');
        }
        header('Location: /index.php?p=funnels'); exit;
    }
}

// ───────────────────────── LIST ─────────────────────────
$funnels = $pdo->query('SELECT * FROM funnels WHERE tenant_id = ' . tenant_id() . ' ORDER BY created_at DESC')->fetchAll();
$scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
$publicBase = "$scheme://$host/f.php?s=";
$csrf = csrf_token();

layout_head('Funnel'); layout_sidebar('funnels'); layout_topbar('Funnel', 'Landing page marketing da sponsorizzare');
?>
<main class="md:ml-64 lg:ml-72 p-4 md:p-8 pb-24 md:pb-8" x-data="{ newModal:false, edit:null, copied:null,
    copy(t,id){ navigator.clipboard.writeText(t); this.copied=id; setTimeout(()=>this.copied=null,1500); } }">

    <?php layout_flashes(); ?>

    <div class="flex items-center justify-between gap-3 mb-5">
        <div>
            <h2 class="font-bold text-lg">I tuoi funnel</h2>
            <p class="text-xs text-slate-400 mt-0.5">Carica una landing, ottieni il link, sponsorizzalo. Chi clicca vede solo il funnel.</p>
        </div>
        <button @click="newModal=true" class="px-4 py-2 rounded-lg btn-primary text-sm font-semibold whitespace-nowrap">+ Nuovo funnel</button>
    </div>

    <?php if (!$funnels): ?>
        <div class="card p-10 text-center">
            <div class="text-4xl mb-3">🎯</div>
            <div class="font-semibold">Ancora nessun funnel</div>
            <p class="text-sm text-slate-400 mt-1">Carica il primo .zip per iniziare a sponsorizzare un evento.</p>
        </div>
    <?php else: ?>
    <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-4">
        <?php foreach ($funnels as $f):
            $link = $publicBase . $f['slug'];
            $dirOk = is_file($funnelsDir . '/' . $f['slug'] . '/index.html');
        ?>
        <div class="card p-5 flex flex-col">
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <div class="font-bold leading-tight truncate"><?= e($f['title']) ?></div>
                    <div class="text-xs text-slate-500 mt-0.5 font-mono truncate"><?= e($f['slug']) ?></div>
                </div>
                <span class="text-[11px] font-bold px-2 py-1 rounded-full whitespace-nowrap <?= $f['active'] ? 'bg-emerald-500/15 text-emerald-300' : 'bg-slate-500/15 text-slate-400' ?>">
                    <?= $f['active'] ? '● Attivo' : '○ Spento' ?>
                </span>
            </div>

            <?php if ($f['description']): ?>
                <p class="text-sm text-slate-400 mt-2 line-clamp-2"><?= e($f['description']) ?></p>
            <?php endif; ?>
            <?php if (!$dirOk): ?>
                <p class="text-xs text-rose-400 mt-2">⚠ File del funnel non trovati sul server.</p>
            <?php endif; ?>

            <div class="flex items-center gap-4 text-xs text-slate-500 mt-3">
                <span>👁 <?= (int)$f['views'] ?> visite</span>
                <span><?= e(substr((string)$f['created_at'], 0, 10)) ?></span>
            </div>

            <!-- Link da sponsorizzare -->
            <div class="mt-3 flex items-stretch gap-1.5">
                <input type="text" readonly value="<?= e($link) ?>"
                    class="flex-1 min-w-0 px-2.5 py-2 rounded-lg bg-white/5 border border-white/10 text-xs font-mono text-slate-300"
                    onclick="this.select()">
                <button @click="copy('<?= e($link) ?>', <?= (int)$f['id'] ?>)"
                    class="px-3 rounded-lg bg-white/5 hover:bg-white/10 text-sm whitespace-nowrap"
                    x-text="copied === <?= (int)$f['id'] ?> ? '✓' : '📋'"></button>
            </div>

            <div class="flex gap-2 mt-3 pt-3 border-t border-white/5">
                <a href="<?= e($link) ?>" target="_blank" rel="noopener"
                   class="flex-1 text-center px-3 py-2 rounded-lg btn-primary text-xs font-semibold">Apri ↗</a>
                <button @click='edit = <?= json_encode(["id"=>(int)$f["id"],"title"=>$f["title"],"description"=>$f["description"]], JSON_HEX_APOS|JSON_HEX_QUOT) ?>'
                   class="px-3 py-2 rounded-lg bg-white/5 hover:bg-white/10 text-xs">Modifica</button>
                <form method="post" class="contents">
                    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                    <input type="hidden" name="action" value="toggle">
                    <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
                    <button type="submit" class="px-3 py-2 rounded-lg bg-white/5 hover:bg-white/10 text-xs" title="Attiva/Disattiva">
                        <?= $f['active'] ? '⏸' : '▶' ?>
                    </button>
                </form>
                <form method="post" class="contents" onsubmit="return confirm('Eliminare definitivamente questo funnel e i suoi file?')">
                    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
                    <button type="submit" class="px-3 py-2 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-300 text-xs">🗑</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Modale NUOVO funnel -->
    <div x-show="newModal" x-cloak @click.self="newModal=false" class="fixed inset-0 z-50 bg-black/70 flex items-center justify-center p-4">
        <div class="card p-6 w-full max-w-md">
            <h3 class="text-lg font-bold mb-1">Nuovo funnel</h3>
            <p class="text-xs text-slate-400 mb-4">Carica il .zip con dentro <code>index.html</code> e la cartella <code>assets/</code>.</p>
            <form method="post" enctype="multipart/form-data" class="space-y-3">
                <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                <input type="hidden" name="action" value="create">
                <div>
                    <label class="text-xs text-slate-400">Titolo interno (per riconoscerlo)</label>
                    <input name="title" required placeholder="Es. Evento Ahmad Sami — 16 Maggio"
                        class="w-full mt-1 px-3 py-2 rounded-lg bg-white/5 border border-white/10">
                </div>
                <div>
                    <label class="text-xs text-slate-400">Slug URL (opzionale, generato dal titolo)</label>
                    <input name="slug" placeholder="ahmad-sami-16-maggio"
                        class="w-full mt-1 px-3 py-2 rounded-lg bg-white/5 border border-white/10 font-mono text-sm">
                </div>
                <div>
                    <label class="text-xs text-slate-400">Nota (opzionale)</label>
                    <textarea name="description" rows="2" placeholder="A cosa serve questo funnel"
                        class="w-full mt-1 px-3 py-2 rounded-lg bg-white/5 border border-white/10"></textarea>
                </div>
                <div>
                    <label class="text-xs text-slate-400">File .zip del funnel</label>
                    <input type="file" name="zip" accept=".zip" required
                        class="w-full mt-1 text-sm text-slate-300 file:mr-3 file:px-3 file:py-2 file:rounded-lg file:border-0 file:bg-brand-500 file:text-white file:text-sm">
                </div>
                <div class="flex gap-2 pt-1">
                    <button type="submit" class="flex-1 btn-primary py-2.5 rounded-lg font-semibold">Carica funnel</button>
                    <button type="button" @click="newModal=false" class="px-4 py-2.5 rounded-lg bg-white/5">Annulla</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modale MODIFICA funnel -->
    <div x-show="edit" x-cloak @click.self="edit=null" class="fixed inset-0 z-50 bg-black/70 flex items-center justify-center p-4">
        <div class="card p-6 w-full max-w-md" x-show="edit">
            <h3 class="text-lg font-bold mb-4">Modifica funnel</h3>
            <form method="post" class="space-y-3">
                <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" :value="edit?.id">
                <div>
                    <label class="text-xs text-slate-400">Titolo interno</label>
                    <input name="title" required x-model="edit.title"
                        class="w-full mt-1 px-3 py-2 rounded-lg bg-white/5 border border-white/10">
                </div>
                <div>
                    <label class="text-xs text-slate-400">Nota</label>
                    <textarea name="description" rows="2" x-model="edit.description"
                        class="w-full mt-1 px-3 py-2 rounded-lg bg-white/5 border border-white/10"></textarea>
                </div>
                <div class="flex gap-2 pt-1">
                    <button type="submit" class="flex-1 btn-primary py-2.5 rounded-lg font-semibold">Salva</button>
                    <button type="button" @click="edit=null" class="px-4 py-2.5 rounded-lg bg-white/5">Annulla</button>
                </div>
            </form>
        </div>
    </div>
</main>
<?php layout_mobile_nav('funnels'); layout_foot(); ?>
