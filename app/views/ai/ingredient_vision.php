<?php
$csrf = csrf_token();
?>
<section class="w-full">
    <div class="mx-auto w-full max-w-6xl space-y-6">
        <div class="rounded-2xl border border-primary/10 bg-white p-6 shadow-sm">
            <h1 class="text-2xl font-black tracking-tight text-slate-900">Demo AI NhA�º­n DiA�»‡n NguyĂªn LiA�»‡u</h1>
            <p class="mt-2 text-sm text-slate-600">
                Flow demo hoĂ n chA�»‰nh: <strong>Upload A�º£nh</strong> A�†’ <strong>AI nhA�º­n diA�»‡n nguyĂªn liA�»‡u</strong> A�†’ <strong>gA�»£i Ă½ mĂ³n A�ƒn phĂ¹ hA�»£p</strong>.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <div class="space-y-4 rounded-2xl border border-primary/10 bg-white p-5 shadow-sm xl:col-span-2">
                <h2 class="text-lg font-bold text-slate-900">1) Upload A�º£nh nguyĂªn liA�»‡u</h2>

                <div id="ai-dropzone" class="rounded-2xl border-2 border-dashed border-amber-300 bg-amber-50/50 p-6 text-center transition hover:border-primary">
                    <input id="ai-image-input" type="file" accept="image/png,image/jpeg" multiple class="hidden">
                    <p class="text-sm font-semibold text-slate-700">KĂ©o thA�º£ 1 hoA�º·c nhiA�»u A�º£nh nguyĂªn liA�»‡u vĂ o A�‘Ă¢y hoA�º·c</p>
                    <div class="mt-3 flex flex-wrap items-center justify-center gap-2">
                        <button id="ai-pick-image" type="button" class="rounded-xl bg-primary px-4 py-2 text-sm font-bold text-white hover:opacity-90">ChA�»n A�º£nh</button>
                        <button id="ai-capture-image" type="button" class="rounded-xl border border-primary/40 px-4 py-2 text-sm font-semibold text-primary hover:bg-amber-50">ChA�»¥p A�º£nh</button>
                    </div>
                    <p class="mt-3 text-xs text-slate-500">HA�»— trA�»£ JPG / PNG. CĂ³ thA�»ƒ chA�»n nhiA�»u A�º£nh A�‘A�»ƒ tĂ¬m mĂ³n tA�»± A�‘A�»™ng, khĂ´ng cA�º§n gĂµ bĂ n phĂ­m.</p>
                </div>

                <div>
                    <h3 class="mb-2 text-sm font-semibold text-slate-700">A�º¢nh A�‘Ă£ chA�»n</h3>
                    <div id="ai-selected-images" class="flex min-h-12 flex-wrap gap-2 rounded-xl border border-slate-200 bg-slate-50 p-2 text-xs text-slate-500">
                        ChA�°a cĂ³ A�º£nh nĂ o.
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <div>
                        <h3 class="mb-2 text-sm font-semibold text-slate-700">2) Preview A�º£nh</h3>
                        <div id="ai-preview-wrap" class="relative overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                            <img id="ai-preview-image" alt="A�º¢nh preview" class="h-72 w-full object-cover opacity-0 transition">
                            <canvas id="ai-preview-canvas" class="pointer-events-none absolute inset-0 h-full w-full"></canvas>
                            <div id="ai-preview-empty" class="absolute inset-0 flex items-center justify-center text-sm text-slate-400">ChA�°a cĂ³ A�º£nh</div>
                        </div>
                    </div>

                    <div>
                        <h3 class="mb-2 text-sm font-semibold text-slate-700">3) KA�º¿t quA�º£ nhA�º­n diA�»‡n AI</h3>
                        <div id="ai-detections-list" class="h-72 overflow-auto rounded-xl border border-slate-200 bg-white p-3 text-sm text-slate-600">
                            ChA�°a nhA�º­n diA�»‡n.
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div>
                        <label for="ai-limit" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">SA�»‘ mĂ³n gA�»£i Ă½</label>
                        <input id="ai-limit" type="number" min="1" max="20" value="8" class="w-full rounded-xl border-slate-300 text-sm focus:border-primary focus:ring-primary">
                    </div>
                    <div>
                        <label for="ai-max-calories" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Max Calories</label>
                        <input id="ai-max-calories" type="number" min="0" step="1" class="w-full rounded-xl border-slate-300 text-sm focus:border-primary focus:ring-primary" placeholder="VD: 550">
                    </div>
                    <div>
                        <label for="ai-keyword" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">TA�»« khóa mĂ³n</label>
                        <input id="ai-keyword" type="text" class="w-full rounded-xl border-slate-300 text-sm focus:border-primary focus:ring-primary" placeholder="xÄ‚Â o, canh, chay...">
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <button id="ai-detect-btn" type="button" class="rounded-xl border border-primary/40 px-5 py-2.5 text-sm font-semibold text-primary hover:bg-amber-50">NhA�º­n diA�»‡n AI</button>
                    <button id="ai-run-btn" type="button" class="rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-white transition hover:opacity-90">GA�»£i Ă½ mĂ³n A�ƒn</button>
                    <button id="ai-one-click-btn" type="button" class="rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white transition hover:opacity-90">TĂ¬m mĂ³n ngay (1 chA�º¡m)</button>
                </div>

                <p id="ai-status" aria-live="polite" class="text-sm text-slate-500">SA�ºµn sĂ ng.</p>
            </div>

            <div class="space-y-4 rounded-2xl border border-primary/10 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-bold text-slate-900">Flow Demo</h2>
                <ol class="space-y-2 text-sm text-slate-600">
                    <li>1. Upload A�º£nh nguyĂªn liA�»‡u</li>
                    <li>2. AI detect + confidence + bounding box</li>
                    <li>3. Map nhĂ£n sang tiA�º¿ng ViA�»‡t</li>
                    <li>4. GA�»£i Ă½ cĂ´ng thA�»©c phĂ¹ hA�»£p</li>
                </ol>
                <div id="ai-meta" class="rounded-xl bg-slate-50 p-3 text-sm text-slate-600">ChA�°a cĂ³ dA�»¯ liA�»‡u.</div>
                <div id="ai-error" role="alert" aria-live="assertive" class="hidden rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>
            </div>
        </div>

        <div class="rounded-2xl border border-primary/10 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-bold text-slate-900">4) Món gA�»£i Ă½</h2>
            <div id="ai-result-grid" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3"></div>
        </div>
    </div>
</section>

<script>
(() => {
    const suggestEndpoint = '<?= URLROOT; ?>/ml/suggest-recipes';
    const detectEndpoint = '<?= URLROOT; ?>/ml/detect-ingredients';
    const csrfToken = '<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>';

    const dropzone = document.getElementById('ai-dropzone');
    const inputEl = document.getElementById('ai-image-input');
    const pickBtn = document.getElementById('ai-pick-image');
    const captureBtn = document.getElementById('ai-capture-image');
    const detectBtn = document.getElementById('ai-detect-btn');
    const runBtn = document.getElementById('ai-run-btn');
    const oneClickBtn = document.getElementById('ai-one-click-btn');

    const previewWrap = document.getElementById('ai-preview-wrap');
    const previewImg = document.getElementById('ai-preview-image');
    const previewCanvas = document.getElementById('ai-preview-canvas');
    const previewEmpty = document.getElementById('ai-preview-empty');

    const detectionsListEl = document.getElementById('ai-detections-list');
    const limitEl = document.getElementById('ai-limit');
    const maxCaloriesEl = document.getElementById('ai-max-calories');
    const keywordEl = document.getElementById('ai-keyword');
    const statusEl = document.getElementById('ai-status');
    const metaEl = document.getElementById('ai-meta');
    const errorEl = document.getElementById('ai-error');
    const resultGridEl = document.getElementById('ai-result-grid');
    const selectedImagesEl = document.getElementById('ai-selected-images');

    let selectedImageFiles = [];
    let currentDetections = [];
    let aggregatedDetections = [];

    const setStatus = (text) => { statusEl.textContent = text; };
    const setError = (text) => {
        if (!text) {
            errorEl.classList.add('hidden');
            errorEl.textContent = '';
            return;
        }
        errorEl.classList.remove('hidden');
        errorEl.textContent = text;
    };
    const readApiPayload = async (response) => {
        const rawText = await response.text();
        const text = String(rawText || '').trim();
        const contentType = String(response.headers.get('content-type') || '').toLowerCase();
        const looksLikeJson = text.startsWith('{') || text.startsWith('[');

        if (text !== '' && (contentType.includes('application/json') || looksLikeJson)) {
            try {
                return JSON.parse(text);
            } catch (err) {
                return {
                    success: false,
                    message: 'Server tra ve JSON khong hop le.',
                    raw: text,
                };
            }
        }

        if (text === '') {
            return {
                success: false,
                message: 'Server khong tra ve du lieu.',
            };
        }

        return {
            success: false,
            message: text,
        };
    };

    const loadPreview = (file) => {
        if (!file) return;
        const reader = new FileReader();
        reader.onload = () => {
            previewImg.src = String(reader.result || '');
            previewImg.classList.remove('opacity-0');
            previewEmpty.classList.add('hidden');
            clearCanvas();
        };
        reader.readAsDataURL(file);
    };

    const clearCanvas = () => {
        const ctx = previewCanvas.getContext('2d');
        if (!ctx) return;
        ctx.clearRect(0, 0, previewCanvas.width, previewCanvas.height);
    };

    const resizeCanvasToImage = () => {
        const rect = previewWrap.getBoundingClientRect();
        previewCanvas.width = Math.max(1, Math.floor(rect.width));
        previewCanvas.height = Math.max(1, Math.floor(rect.height));
    };

    const drawBoxes = (detections) => {
        resizeCanvasToImage();
        const ctx = previewCanvas.getContext('2d');
        if (!ctx) return;
        clearCanvas();
        ctx.lineWidth = 2;
        ctx.font = '12px Work Sans, sans-serif';

        detections.forEach((d) => {
            if (!d.box) return;
            const x = d.box.x * previewCanvas.width;
            const y = d.box.y * previewCanvas.height;
            const w = d.box.w * previewCanvas.width;
            const h = d.box.h * previewCanvas.height;

            ctx.strokeStyle = '#f59f0a';
            ctx.fillStyle = 'rgba(245, 159, 10, 0.12)';
            ctx.strokeRect(x, y, w, h);
            ctx.fillRect(x, y, w, h);

            const label = `${d.vi_label || d.label} (${Math.round(Number(d.confidence || 0) * 100)}%)`;
            const textW = ctx.measureText(label).width + 10;
            ctx.fillStyle = '#0f172a';
            ctx.fillRect(x, Math.max(0, y - 20), textW, 18);
            ctx.fillStyle = '#ffffff';
            ctx.fillText(label, x + 5, Math.max(12, y - 7));
        });
    };

    const renderDetections = (detections) => {
        if (!detections.length) {
            detectionsListEl.textContent = 'ChA�°a nhA�º­n diA�»‡n.';
            return;
        }
        detectionsListEl.innerHTML = `
            <p class="mb-2 font-semibold text-slate-800">Nguyên liệu phĂ¡t hiA�»‡n:</p>
            <ul class="space-y-2">
                ${detections.map((d) => `<li class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2"><span>${d.vi_label || d.label}</span><span class="text-xs font-semibold text-primary">${Math.round(Number(d.confidence || 0) * 100)}%</span></li>`).join('')}
            </ul>
        `;
    };

    const renderSelectedImages = () => {
        if (!selectedImageFiles.length) {
            selectedImagesEl.textContent = 'ChA�°a cĂ³ A�º£nh nĂ o.';
            return;
        }
        selectedImagesEl.innerHTML = selectedImageFiles.map((file, idx) => `
            <button type="button" data-preview-idx="${idx}" class="rounded-lg border border-slate-300 bg-white px-2 py-1 text-left text-xs text-slate-700 hover:border-primary">
                A�º¢nh ${idx + 1}: ${String(file.name || 'image').slice(0, 26)}
            </button>
        `).join('');
        selectedImagesEl.querySelectorAll('[data-preview-idx]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const idx = Number(btn.getAttribute('data-preview-idx') || 0);
                const file = selectedImageFiles[idx] || null;
                loadPreview(file);
                const singleDetections = Array.isArray(file && file._detections) ? file._detections : [];
                currentDetections = singleDetections;
                renderDetections(singleDetections);
                drawBoxes(singleDetections);
            });
        });
    };

    const detectSingleImage = async (file) => {
        const body = new FormData();
        body.append('image', file);

        const response = await fetch(detectEndpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': csrfToken,
            },
            body,
        });

        const data = await readApiPayload(response);
        if (!response.ok || !data.success) {
            throw new Error((data && data.message) ? data.message : 'Không thA�»ƒ nhA�º­n diA�»‡n A�º£nh.');
        }
        const out = data.data || {};
        return Array.isArray(out.detections) ? out.detections : [];
    };

    const detectIngredients = async () => {
        setError('');
        if (!selectedImageFiles.length) {
            throw new Error('BA�º¡n cA�º§n chA�»n Ă­t nhA�º¥t 1 A�º£nh nguyĂªn liA�»‡u.');
        }

        const merged = new Map();
        for (let i = 0; i < selectedImageFiles.length; i += 1) {
            setStatus(`Đang nhận diện ảnh ${i + 1}/${selectedImageFiles.length}...`);
            const file = selectedImageFiles[i];
            const detections = await detectSingleImage(file);
            file._detections = detections;
            detections.forEach((d) => {
                const key = String(d.vi_label || d.label || '').toLowerCase().trim();
                if (!key) return;
                const existing = merged.get(key);
                if (!existing || Number(d.confidence || 0) > Number(existing.confidence || 0)) {
                    merged.set(key, d);
                }
            });
        }

        aggregatedDetections = Array.from(merged.values());
        currentDetections = Array.isArray(selectedImageFiles[0]?._detections) ? selectedImageFiles[0]._detections : [];
        renderDetections(aggregatedDetections);
        drawBoxes(currentDetections);
        setStatus('A�Ă£ nhA�º­n diA�»‡n xong.');
        metaEl.textContent = `A�Ă£ nhA�º­n diA�»‡n ${selectedImageFiles.length} A�º£nh, tĂ¬m A�‘A�°A�»£c ${aggregatedDetections.length} nguyĂªn liA�»‡u khĂ¡c nhau.`;
    };

    const toPayload = () => {
        const ingredientTexts = Array.from(new Set(
            aggregatedDetections.flatMap((d) => ([
                String(d.label || '').trim(),
                String(d.vi_label || '').trim(),
            ])).filter(Boolean)
        ));

        const payload = {
            ingredients: ingredientTexts,
            detections: aggregatedDetections.map((d) => ({
                label: String(d.vi_label || d.label || '').trim(),
                confidence: Number(d.confidence || 0),
            })).filter((d) => d.label !== ''),
            limit: Number(limitEl.value || 8),
            keyword: String(keywordEl.value || '').trim(),
        };

        const maxCalories = Number(maxCaloriesEl.value || 0);
        if (Number.isFinite(maxCalories) && maxCalories > 0) {
            payload.max_calories = maxCalories;
        }

        return payload;
    };

    const renderCards = (recipes) => {
        resultGridEl.innerHTML = '';
        if (!Array.isArray(recipes) || recipes.length === 0) {
            resultGridEl.innerHTML = '<div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500">ChA�°a cĂ³ mĂ³n phĂ¹ hA�»£p.</div>';
            return;
        }

        recipes.forEach((recipe, index) => {
            const card = document.createElement('article');
            card.className = 'overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm';

            const image = String(recipe.image || '').trim();
            const imageUrl = image !== ''
                ? (image.startsWith('http') ? image : `<?= URLROOT; ?>/uploads/${image.replace(/^\/+/, '')}`)
                : '';

            const match = (recipe.total_ingredients && recipe.matched_count != null)
                ? Math.round((Number(recipe.matched_count) / Number(recipe.total_ingredients)) * 100)
                : null;

            card.innerHTML = `
                ${imageUrl ? `<img src="${imageUrl}" alt="A�º¢nh mĂ³n A�ƒn" class="h-40 w-full object-cover">` : ''}
                <div class="p-4">
                    <p class="mb-1 text-xs font-semibold text-slate-400">Món gA�»£i Ă½ #${index + 1}</p>
                    <h3 class="text-base font-bold text-slate-900">${recipe.title || 'CĂ´ng thA�»©c'}</h3>
                    <p class="mt-2 line-clamp-2 text-sm text-slate-600">${recipe.description || ''}</p>
                    <div class="mt-3 flex items-center justify-between gap-2">
                        <span class="text-xs font-semibold text-primary">${match != null ? `Match: ${match}%` : 'Match: N/A'}</span>
                        <a class="rounded-lg bg-amber-100 px-3 py-1.5 text-xs font-bold text-amber-700 hover:bg-amber-200" href="${recipe.url || '#'}">Xem chi tiA�º¿t</a>
                    </div>
                </div>
            `;
            resultGridEl.appendChild(card);
        });
    };

    const runSuggestion = async () => {
        setError('');
        runBtn.disabled = true;
        setStatus('A�ang gA�»£i Ă½ mĂ³n A�ƒn...');

        try {
            if (!aggregatedDetections.length) {
                throw new Error('BA�º¡n cA�º§n chA�º¡y bA�°A�»›c nhA�º­n diA�»‡n AI trA�°A�»›c.');
            }

            const response = await fetch(suggestEndpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': csrfToken,
                },
                body: JSON.stringify(toPayload()),
            });

            const data = await readApiPayload(response);

            if (!response.ok || !data.success) {
                throw new Error((data && data.message) ? data.message : 'Không thA�»ƒ gA�»£i Ă½ cĂ´ng thA�»©c.');
            }

            const out = data.data || {};
            const recipes = Array.isArray(out.recipes) ? out.recipes : [];
            const resolvedIngredients = Array.isArray(out.resolved_ingredients) ? out.resolved_ingredients : [];
            renderCards(recipes);
            metaEl.textContent = `Map A�‘A�°A�»£c ${resolvedIngredients.length} nguyĂªn liA�»‡u, tĂ¬m thA�º¥y ${recipes.length} mĂ³n A�ƒn.`;
            setStatus('HoĂ n tA�º¥t flow demo.');
        } catch (err) {
            setError((err && err.message) ? String(err.message) : 'CĂ³ lA�»—i xA�º£y ra.');
            setStatus('ThA�º¥t bA�º¡i.');
            renderCards([]);
        } finally {
            runBtn.disabled = false;
        }
    };

    const setFiles = (files) => {
        const list = Array.from(files || []);
        if (!list.length) return;
        const imageFiles = list.filter((f) => ['image/png', 'image/jpeg'].includes(f.type));
        if (!imageFiles.length) {
            setError('ChA�»‰ hA�»— trA�»£ JPG/PNG.');
            return;
        }
        selectedImageFiles = imageFiles.slice(0, 10);
        currentDetections = [];
        aggregatedDetections = [];
        renderDetections([]);
        renderSelectedImages();
        loadPreview(selectedImageFiles[0]);
        setError('');
        setStatus(`A�Ă£ chA�»n ${selectedImageFiles.length} A�º£nh. SA�ºµn sĂ ng nhA�º­n diA�»‡n AI.`);
        metaEl.textContent = 'MA�º¹o: bA�º¥m "TĂ¬m mĂ³n ngay (1 chA�º¡m)" A�‘A�»ƒ chA�º¡y tA�»± A�‘A�»™ng toĂ n bA�»™ flow.';
    };

    pickBtn.addEventListener('click', () => inputEl.click());
    inputEl.addEventListener('change', (e) => {
        setFiles(e.target.files);
    });

    dropzone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropzone.classList.add('border-primary');
    });
    dropzone.addEventListener('dragleave', () => dropzone.classList.remove('border-primary'));
    dropzone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropzone.classList.remove('border-primary');
        setFiles(e.dataTransfer && e.dataTransfer.files ? e.dataTransfer.files : []);
    });

    captureBtn.addEventListener('click', () => {
        inputEl.setAttribute('capture', 'environment');
        inputEl.click();
    });

    detectBtn.addEventListener('click', async () => {
        detectBtn.disabled = true;
        setStatus('Đang nhận diện...');
        try {
            await detectIngredients();
        } catch (err) {
            setError((err && err.message) ? String(err.message) : 'Không thA�»ƒ nhA�º­n diA�»‡n A�º£nh.');
            setStatus('ThA�º¥t bA�º¡i.');
        } finally {
            detectBtn.disabled = false;
        }
    });

    runBtn.addEventListener('click', runSuggestion);
    oneClickBtn.addEventListener('click', async () => {
        oneClickBtn.disabled = true;
        detectBtn.disabled = true;
        runBtn.disabled = true;
        setError('');
        try {
            await detectIngredients();
            await runSuggestion();
            setStatus('HoĂ n tA�º¥t tĂ¬m mĂ³n tA�»± A�‘A�»™ng.');
        } catch (err) {
            setError((err && err.message) ? String(err.message) : 'Không thA�»ƒ tĂ¬m mĂ³n tA�»± A�‘A�»™ng.');
            setStatus('ThA�º¥t bA�º¡i.');
        } finally {
            oneClickBtn.disabled = false;
            detectBtn.disabled = false;
            runBtn.disabled = false;
        }
    });

    window.addEventListener('resize', () => {
        if (currentDetections.length) {
            drawBoxes(currentDetections);
        }
    });

    renderCards([]);
    renderSelectedImages();
})();
</script>




