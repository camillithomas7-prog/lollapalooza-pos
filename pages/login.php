<?php layout_head('Accedi'); ?>
<div class="fixed top-4 right-4 z-50"><?= theme_switcher() ?></div>
<div class="min-h-screen flex items-center justify-center p-4 grad-bg">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <img src="/assets/img/logo.jpeg" class="w-20 h-20 mx-auto rounded-2xl object-cover ring-2 ring-brand-500/30 shadow-glow" alt="Lollapalooza">
            <h1 class="text-3xl font-bold mt-4 tracking-tight">Lollapalooza POS</h1>
            <p class="text-slate-400 text-sm mt-1">Gestionale ristoranti & lounge bar</p>
        </div>
        <div class="card p-8">
            <?php layout_flashes(); ?>
            <form method="post" class="space-y-4">
                <div>
                    <label class="text-sm text-slate-400 mb-1.5 block">Email</label>
                    <input type="email" name="email" required autofocus
                        value="admin@lollab.it"
                        class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none transition">
                </div>
                <div>
                    <label class="text-sm text-slate-400 mb-1.5 block">Password</label>
                    <input type="password" name="password" required
                        value="lollab2026"
                        class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none transition">
                </div>
                <button type="submit" class="btn-primary w-full py-3 rounded-xl font-semibold">Accedi</button>
            </form>
            <div class="mt-6 text-center">
                <a href="/index.php?p=pin" class="text-sm text-brand-400 hover:text-brand-300">Accesso rapido con PIN →</a>
            </div>
        </div>
        <div class="mt-6 card p-4 text-xs text-slate-400">
            <div class="font-semibold text-slate-300 mb-2">🔑 Credenziali demo</div>
            <div class="grid grid-cols-[auto_auto] gap-x-4 gap-y-1">
                <div>👑 admin@lollab.it</div><div>PIN: 0000</div>
                <div>👔 manager@lollab.it</div><div>PIN: 1111</div>
                <div>💰 cassa@lollab.it</div><div>PIN: 2222</div>
                <div>🤵 luca@lollab.it (Luca cameriere)</div><div>PIN: 3333</div>
                <div>👩 anna@lollab.it (Anna cameriere)</div><div>PIN: 3334</div>
                <div>👨‍🍳 cucina@lollab.it</div><div>PIN: 4444</div>
                <div>🍸 bar@lollab.it</div><div>PIN: 5555</div>
                <div>📦 magazzino@lollab.it</div><div>PIN: 6666</div>
            </div>
            <div class="mt-2 text-slate-500">Password unica: <code>lollab2026</code></div>
        </div>
    </div>
</div>
<?php layout_foot(); ?>
