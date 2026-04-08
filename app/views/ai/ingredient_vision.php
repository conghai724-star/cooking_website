<?php
$csrf = csrf_token();
?>
<section class="w-full">
    <div class="mx-auto w-full max-w-6xl space-y-6">
        <div class="rounded-2xl border border-primary/10 bg-white p-6 shadow-sm">
            <h1 class="text-2xl font-black tracking-tight text-slate-900">Demo AI Nhận Diện Nguyên Liệu</h1>
            <p class="mt-2 text-sm text-slate-600">
                Flow demo hoàn chỉnh: <strong>Upload ảnh</strong> → <strong>AI nhận diện nguyên liệu</strong> → <strong>gợi ý món ăn phù hợp</strong>.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <div class="space-y-4 rounded-2xl border border-primary/10 bg-white p-5 shadow-sm xl:col-span-2">
                <h2 class="text-lg font-bold text-slate-900">1) Upload ảnh nguyên liệu</h2>

                <div id="ai-dropzone" class="rounded-2xl border-2 border-dashed border-amber-300 bg-amber-50/50 p-6 text-center transition hover:border-primary">
                    <input id="ai-image-input" type="file" accept="image/png,image/jpeg" class="hidden">
                    <p class="text-sm font-semibold text-slate-700">Kéo thả ảnh vào đây hoặc</p>
                    <div class="mt-3 flex flex-wrap items-center justify-center gap-2">
                        <button id="ai-pick-image" type="button" class="rounded-xl bg-primary px-4 py-2 text-sm font-bold text-white hover:opacity-90">Chọn ảnh</button>
                        <button id="ai-capture-image" type="button" class="rounded-xl border border-primary/40 px-4 py-2 text-sm font-semibold text-primary hover:bg-amber-50">Chụp ảnh</button>
                    </div>
                    <p class="mt-3 text-xs text-slate-500">Hỗ trợ JPG / PNG</p>
                </div>

                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <div>
                        <h3 class="mb-2 text-sm font-semibold text-slate-700">2) Preview ảnh</h3>
                        <div id="ai-preview-wrap" class="relative overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                            <img id="ai-preview-image" alt="Ảnh preview" class="h-72 w-full object-cover opacity-0 transition">
                            <canvas id="ai-preview-canvas" class="pointer-events-none absolute inset-0 h-full w-full"></canvas>
                            <div id="ai-preview-empty" class="absolute inset-0 flex items-center justify-center text-sm text-slate-400">Chưa có ảnh</div>
                        </div>
                    </div>

                    <div>
                        <h3 class="mb-2 text-sm font-semibold text-slate-700">3) Kết quả nhận diện AI</h3>
                        <div id="ai-detections-list" class="h-72 overflow-auto rounded-xl border border-slate-200 bg-white p-3 text-sm text-slate-600">
                            Chưa nhận diện.
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div>
                        <label for="ai-limit" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Số món gợi ý</label>
                        <input id="ai-limit" type="number" min="1" max="20" value="8" class="w-full rounded-xl border-slate-300 text-sm focus:border-primary focus:ring-primary">
                    </div>
                    <div>
                        <label for="ai-max-calories" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Max Calories</label>
                        <input id="ai-max-calories" type="number" min="0" step="1" class="w-full rounded-xl border-slate-300 text-sm focus:border-primary focus:ring-primary" placeholder="VD: 550">
                    </div>
                    <div>
                        <label for="ai-keyword" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Từ khóa món</label>
                        <input id="ai-keyword" type="text" class="w-full rounded-xl border-slate-300 text-sm focus:border-primary focus:ring-primary" placeholder="xào, canh, chay...">
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <button id="ai-detect-btn" type="button" class="rounded-xl border border-primary/40 px-5 py-2.5 text-sm font-semibold text-primary hover:bg-amber-50">Nhận diện AI</button>
                    <button id="ai-run-btn" type="button" class="rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-white transition hover:opacity-90">Gợi ý món ăn</button>
                </div>

                <p id="ai-status" class="text-sm text-slate-500">Sẵn sàng.</p>
            </div>

            <div class="space-y-4 rounded-2xl border border-primary/10 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-bold text-slate-900">Flow Demo</h2>
                <ol class="space-y-2 text-sm text-slate-600">
                    <li>1. Upload ảnh nguyên liệu</li>
                    <li>2. AI detect + confidence + bounding box</li>
                    <li>3. Map nhãn sang tiếng Việt</li>
                    <li>4. Gợi ý công thức phù hợp</li>
                </ol>
                <div id="ai-meta" class="rounded-xl bg-slate-50 p-3 text-sm text-slate-600">Chưa có dữ liệu.</div>
                <div id="ai-error" class="hidden rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>
            </div>
        </div>

        <div class="rounded-2xl border border-primary/10 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-bold text-slate-900">4) Món gợi ý</h2>
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

    let selectedImageFile = null;
    let currentDetections = [];

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
            detectionsListEl.textContent = 'Chưa nhận diện.';
            return;
        }
        detectionsListEl.innerHTML = `
            <p class="mb-2 font-semibold text-slate-800">Nguyên liệu phát hiện:</p>
            <ul class="space-y-2">
                ${detections.map((d) => `<li class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2"><span>${d.vi_label || d.label}</span><span class="text-xs font-semibold text-primary">${Math.round(Number(d.confidence || 0) * 100)}%</span></li>`).join('')}
            </ul>
        `;
    };

    const detectIngredients = async () => {
        setError('');
        if (!selectedImageFile) {
            throw new Error('Bạn cần upload ảnh trước khi nhận diện.');
        }

        const body = new FormData();
        body.append('image', selectedImageFile);

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
            throw new Error((data && data.message) ? data.message : 'Không thể nhận diện ảnh.');
        }

        const out = data.data || {};
        currentDetections = Array.isArray(out.detections) ? out.detections : [];
        renderDetections(currentDetections);
        drawBoxes(currentDetections);
        setStatus('Đã nhận diện xong.');
        metaEl.textContent = `Đã detect ${currentDetections.length} nguyên liệu từ ảnh.`;
    };

    const toPayload = () => {
        const ingredientTexts = Array.from(new Set(
            currentDetections.flatMap((d) => ([
                String(d.label || '').trim(),
                String(d.vi_label || '').trim(),
            ])).filter(Boolean)
        ));

        const payload = {
            ingredients: ingredientTexts,
            detections: currentDetections.map((d) => ({
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
            resultGridEl.innerHTML = '<div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500">Chưa có món phù hợp.</div>';
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
                ${imageUrl ? `<img src="${imageUrl}" alt="Ảnh món ăn" class="h-40 w-full object-cover">` : ''}
                <div class="p-4">
                    <p class="mb-1 text-xs font-semibold text-slate-400">Món gợi ý #${index + 1}</p>
                    <h3 class="text-base font-bold text-slate-900">${recipe.title || 'Công thức'}</h3>
                    <p class="mt-2 line-clamp-2 text-sm text-slate-600">${recipe.description || ''}</p>
                    <div class="mt-3 flex items-center justify-between gap-2">
                        <span class="text-xs font-semibold text-primary">${match != null ? `Match: ${match}%` : 'Match: N/A'}</span>
                        <a class="rounded-lg bg-amber-100 px-3 py-1.5 text-xs font-bold text-amber-700 hover:bg-amber-200" href="${recipe.url || '#'}">Xem chi tiết</a>
                    </div>
                </div>
            `;
            resultGridEl.appendChild(card);
        });
    };

    const runSuggestion = async () => {
        setError('');
        runBtn.disabled = true;
        setStatus('Đang gợi ý món ăn...');

        try {
            if (!currentDetections.length) {
                throw new Error('Bạn cần chạy bước nhận diện AI trước.');
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
                throw new Error((data && data.message) ? data.message : 'Không thể gợi ý công thức.');
            }

            const out = data.data || {};
            const recipes = Array.isArray(out.recipes) ? out.recipes : [];
            const resolvedIngredients = Array.isArray(out.resolved_ingredients) ? out.resolved_ingredients : [];
            renderCards(recipes);
            metaEl.textContent = `Map được ${resolvedIngredients.length} nguyên liệu, tìm thấy ${recipes.length} món ăn.`;
            setStatus('Hoàn tất flow demo.');
        } catch (err) {
            setError((err && err.message) ? String(err.message) : 'Có lỗi xảy ra.');
            setStatus('Thất bại.');
            renderCards([]);
        } finally {
            runBtn.disabled = false;
        }
    };

    const setFile = (file) => {
        if (!file) return;
        const ok = ['image/png', 'image/jpeg'].includes(file.type);
        if (!ok) {
            setError('Chỉ hỗ trợ JPG/PNG.');
            return;
        }
        selectedImageFile = file;
        currentDetections = [];
        renderDetections([]);
        loadPreview(file);
        setError('');
        setStatus(`Đã chọn ảnh: ${file.name}`);
        metaEl.textContent = 'Sẵn sàng nhận diện AI.';
    };

    pickBtn.addEventListener('click', () => inputEl.click());
    inputEl.addEventListener('change', (e) => {
        const file = e.target.files && e.target.files[0] ? e.target.files[0] : null;
        setFile(file);
    });

    dropzone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropzone.classList.add('border-primary');
    });
    dropzone.addEventListener('dragleave', () => dropzone.classList.remove('border-primary'));
    dropzone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropzone.classList.remove('border-primary');
        const file = e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0] ? e.dataTransfer.files[0] : null;
        setFile(file);
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
            setError((err && err.message) ? String(err.message) : 'Không thể nhận diện ảnh.');
            setStatus('Thất bại.');
        } finally {
            detectBtn.disabled = false;
        }
    });

    runBtn.addEventListener('click', runSuggestion);

    window.addEventListener('resize', () => {
        if (currentDetections.length) {
            drawBoxes(currentDetections);
        }
    });

    renderCards([]);
})();
</script>



