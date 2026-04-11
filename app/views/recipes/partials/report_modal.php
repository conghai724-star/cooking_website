<div id="report-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm">
    <div class="mx-4 w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
        <h3 class="mb-4 text-xl font-black text-slate-800">BĂ¡o cĂ¡o cĂ´ng thA�»©c</h3>
        <form id="report-form" method="post" action="<?= URLROOT; ?>/recipes/report" data-ajax-form data-close-target="#report-modal" data-success-toast="ĐA� gửi bA�o cA�o cA�ng thức." data-error-toast="KhA�ng thể gửi bA�o cA�o cA�ng thức lA�c nA�y.">
            <?= csrf_field(); ?>
            <input type="hidden" name="recipe_id" value="<?= (int) ($recipe['id'] ?? 0); ?>">
            <div class="mb-4">
                <label class="mb-2 block text-sm font-semibold text-slate-600">LĂ"â€šĂ¢â'¬ÂžÄ'Â¢Ă¢â€šÂ¬Ă'šĂ"'Ă'¢Ä'Â¢Ă¢â'¬ÂšĂ'Â¬Ă…Â¾Ä'"'Ä''Ă'¢Ă"'Ă'¢Ä'Â¢Ă¢â€šÂ¬Ă…Â¡Ä''Ă'¬Ă"''Ă…Â¡Ă"'"Ä'Â¢Ă¢â€šÂ¬Ă…Â¡Ă"'Ă'¢Ä'¢'Ă'¬Ä'â€šĂ…Â¡Ä'"''Ä''Ă'½ do bÄ'"Ä'Â¢Ă¢â€šÂ¬Ă…Â¡Ă"'Ă'¢Ä'¢'Ă'¬Ä'â€šĂ…Â¾Ä'"'Ä''Ă'¢Ă"'Ă'¢Ä'Â¢Ă¢â€šÂ¬Ă…Â¡Ä''Ă'¬Ă"''Ă…Â¡Ă"'"Ä'Â¢Ă¢â€šÂ¬Ă…Â¡Ă"'Ă'¢Ä'¢'Ă'¬Ä'â€šĂ…Â¡Ä'"''Ä''Ă'¡o cÄ'"Ä'Â¢Ă¢â€šÂ¬Ă…Â¡Ă"'Ă'¢Ä'¢'Ă'¬Ä'â€šĂ…Â¾Ä'"'Ä''Ă'¢Ă"'Ă'¢Ä'Â¢Ă¢â€šÂ¬Ă…Â¡Ä''Ă'¬Ă"''Ă…Â¡Ă"'"Ä'Â¢Ă¢â€šÂ¬Ă…Â¡Ă"'Ă'¢Ä'¢'Ă'¬Ä'â€šĂ…Â¡Ä'"''Ä''Ă'¡o:</label>
                <select id="report-reason" name="reason" data-report-reason-select data-report-other-target="#other-reason-container" class="w-full rounded-xl border border-slate-200 p-3 focus:border-primary focus:ring-primary" required>
                    <option value="">-- ChA�»n lĂ½ do --</option>
                    <option value="Spam">Spam</option>
                    <option value="NA�»™i dung khĂ´ng phĂ¹ hA�»£p">NA�»™i dung khĂ´ng phĂ¹ hA�»£p</option>
                    <option value="Vi phA�º¡m bA�º£n quyA�»n">Vi phA�º¡m bA�º£n quyA�»n</option>
                    <option value="ThĂ´ng tin sai lA�»‡ch">ThĂ´ng tin sai lA�»‡ch</option>
                    <option value="KhĂ"â€šĂ¢â'¬ÂžÄ'Â¢Ă¢â€šÂ¬Ă'šĂ"'Ă'¢Ä'Â¢Ă¢â'¬ÂšĂ'Â¬Ă…Â¾Ä'"'Ä''Ă'¢Ă"'Ă'¢Ä'Â¢Ă¢â€šÂ¬Ă…Â¡Ä''Ă'¬Ă"''Ă…Â¡Ă"'"Ä'Â¢Ă¢â€šÂ¬Ă…Â¡Ă"'Ă'¢Ä'¢'Ă'¬Ä'â€šĂ…Â¡Ä'"''Ä''Ă'¡c">KhĂ"â€šĂ¢â'¬ÂžÄ'Â¢Ă¢â€šÂ¬Ă'šĂ"'Ă'¢Ä'Â¢Ă¢â'¬ÂšĂ'Â¬Ă…Â¾Ä'"'Ä''Ă'¢Ă"'Ă'¢Ä'Â¢Ă¢â€šÂ¬Ă…Â¡Ä''Ă'¬Ă"''Ă…Â¡Ă"'"Ä'Â¢Ă¢â€šÂ¬Ă…Â¡Ă"'Ă'¢Ä'¢'Ă'¬Ä'â€šĂ…Â¡Ä'"''Ä''Ă'¡c</option>
                </select>
            </div>
            <div class="mb-4 hidden" id="other-reason-container">
                <label class="mb-2 block text-sm font-semibold text-slate-600">MĂ´ tA�º£ chi tiA�º¿t:</label>
                <textarea id="report-reason-other" name="reason_other" class="w-full rounded-xl border border-slate-200 p-3 focus:border-primary focus:ring-primary" rows="3" placeholder="MĂ´ tA�º£ chi tiA�º¿t..."></textarea>
            </div>
            <div class="flex gap-3">
                <button type="button" id="btn-cancel-report" class="flex-1 rounded-xl border border-slate-300 px-4 py-2 font-semibold text-slate-600 hover:bg-slate-50">
                    HA�»§y
                </button>
                <button type="submit" class="flex-1 rounded-xl bg-red-500 px-4 py-2 font-semibold text-white hover:bg-red-600">
                    GA�»­i bĂ¡o cĂ¡o
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const isLoggedIn = <?= $is_logged_in ? 'true' : 'false'; ?>;
    const btnReportTrigger = document.getElementById('btn-report-trigger');
    const reportModal = document.getElementById('report-modal');
    const btnCancelReport = document.getElementById('btn-cancel-report');
    const otherReasonContainer = document.getElementById('other-reason-container');

    if (btnReportTrigger && reportModal) {
        btnReportTrigger.addEventListener('click', function() {
            if (!isLoggedIn) {
                window.location.href = '<?= URLROOT; ?>/auth/login';
                return;
            }
            reportModal.classList.remove('hidden');
            reportModal.classList.add('flex');
        });

        btnCancelReport.addEventListener('click', function() {
            reportModal.classList.add('hidden');
            reportModal.classList.remove('flex');
            const reportForm = document.getElementById('report-form');
            if (reportForm) reportForm.reset();
            if (otherReasonContainer) otherReasonContainer.classList.add('hidden');
        });

        reportModal.addEventListener('click', function(e) {
            if (e.target === reportModal) {
                reportModal.classList.add('hidden');
                reportModal.classList.remove('flex');
                const reportForm = document.getElementById('report-form');
                if (reportForm) reportForm.reset();
                if (otherReasonContainer) otherReasonContainer.classList.add('hidden');
            }
        });
    }
});
</script>
