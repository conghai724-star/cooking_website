</main>
<?php if (!empty($useRecipeHubLayout)): ?>
<footer class="mt-auto border-t border-primary/10 bg-white px-4 py-8 md:px-10 lg:px-20">
    <div class="mx-auto flex max-w-[1440px] flex-col justify-between gap-8 md:flex-row">
        
    </div>
    <div class="mx-auto mt-8 max-w-[1440px] border-t border-primary/5 pt-8 text-center text-[10px] font-medium uppercase tracking-widest text-slate-400">
        &copy; <?= date('Y'); ?> Công Thức Ngon.
    </div>
</footer>
</div>
<?php else: ?>
<footer class="site-footer">
    <div class="container">
        <small>&copy; <?= date('Y'); ?> Website Nấu Ăn</small>
    </div>
</footer>
<?php endif; ?>
<?php $mainJsVersion = @filemtime(APPROOT . '/public/assets/js/main.js') ?: time(); ?>
<script src="<?= URLROOT; ?>/assets/js/main.js?v=<?= (int) $mainJsVersion; ?>"></script>
</body>
</html>
