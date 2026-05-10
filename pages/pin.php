<?php layout_head('PIN'); ?>
<div class="fixed top-4 right-4 z-50"><?= theme_switcher() ?></div>
<div class="min-h-screen flex items-center justify-center p-4 grad-bg" x-data="{pin:''}">
    <div class="w-full max-w-sm">
        <div class="text-center mb-6">
            <img src="/assets/img/logo.jpeg" class="w-16 h-16 mx-auto rounded-2xl object-cover" alt="Lollapalooza">
            <h1 class="text-2xl font-bold mt-3">Inserisci PIN</h1>
        </div>
        <div class="card p-6">
            <?php layout_flashes(); ?>
            <form method="post" x-ref="form">
                <input type="text" name="pin" x-model="pin" inputmode="numeric" maxlength="4" readonly
                    class="w-full text-center text-3xl tracking-[0.5em] py-4 rounded-xl bg-white/5 border border-white/10 mb-4">
                <div class="grid grid-cols-3 gap-2">
                    <?php foreach ([1,2,3,4,5,6,7,8,9] as $n): ?>
                    <button type="button" @click="if(pin.length<4)pin+='<?= $n ?>'" class="py-4 text-xl font-bold rounded-xl bg-white/5 hover:bg-brand-500/20 active:scale-95 transition"><?= $n ?></button>
                    <?php endforeach; ?>
                    <button type="button" @click="pin=''" class="py-4 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-400">C</button>
                    <button type="button" @click="if(pin.length<4)pin+='0'" class="py-4 text-xl font-bold rounded-xl bg-white/5 hover:bg-brand-500/20 active:scale-95 transition">0</button>
                    <button type="submit" :disabled="pin.length<4" class="py-4 rounded-xl btn-primary disabled:opacity-40">→</button>
                </div>
            </form>
            <div class="mt-4 text-center">
                <a href="/index.php?p=login" class="text-xs text-slate-400">Login con email</a>
            </div>
        </div>
    </div>
</div>
<?php layout_foot(); ?>
