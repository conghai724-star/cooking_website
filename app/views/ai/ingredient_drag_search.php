<?php
$ingredientGroups = is_array($ingredientGroups ?? null) ? $ingredientGroups : [];
$csrf = csrf_token();
?>
<section class="w-full">
    <div class="mx-auto w-full max-w-7xl space-y-6">
        <div class="rounded-2xl border border-primary/10 bg-white p-6 shadow-sm">
            <h1 class="text-2xl font-black tracking-tight text-slate-900">TĂ¬m mĂ³n bA�º±ng kĂ©o thA�º£ nguyĂªn liA�»‡u</h1>
            <p class="mt-2 text-sm text-slate-600">KĂ©o thA�º£ vĂ o khay bĂªn phA�º£i, hoA�º·c <strong class="font-semibold text-slate-800">nhA�º¥p A�‘Ăºp</strong> vĂ o nguyĂªn liA�»‡u A�‘A�»ƒ thĂªm vĂ o khay / nhA�º¥p A�‘Ăºp lA�º§n nA�»¯a A�‘A�»ƒ bA�». LA�°A�»›i mA�»—i trang: 5 cA�»™t Ă— 2 hĂ ng (10 mA�»¥c).</p>
            <div class="mt-4 max-w-xl">
                <label for="ingredient-search" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">TĂ¬m nhanh nguyĂªn liA�»‡u</label>
                <input id="ingredient-search" name="keyword" type="search" placeholder="VĂ­ dA�»¥: cĂ , thA�»‹t, tĂ´m..." class="w-full rounded-xl border-slate-300 text-sm focus:border-primary focus:ring-primary">
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-4">
            <div class="space-y-4 xl:col-span-3">
                <?php if ($ingredientGroups === []): ?>
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-500">
                        ChA�°a cĂ³ dA�»¯ liA�»‡u nguyĂªn liA�»‡u A�‘A�»ƒ hiA�»ƒn thA�»‹.
                    </div>
                <?php else: ?>
                    <?php
                    $groupEntries = [];
                    foreach ($ingredientGroups as $category => $items) {
                        $categoryName = (string) $category;
                        $emoji = match (true) {
                            $categoryName === 'Rau cA�»§' => 'đŸ¥¬',
                            $categoryName === 'ThA�»‹t' => 'đŸ¥©',
                            $categoryName === 'HA�º£i sA�º£n' => 'đŸŸ',
                            $categoryName === 'Gia vA�»‹' => 'đŸ§�?',
                            strcasecmp($categoryName, 'Spices') === 0 => 'đŸ§'',
                            default => 'đŸ§º',
                        };
                        $groupEntries[] = ['name' => $categoryName, 'emoji' => $emoji, 'items' => $items];
                    }
                    ?>
                    <div class="overflow-hidden rounded-2xl border border-primary/10 bg-white shadow-sm">
                        <div class="flex gap-1 overflow-x-auto border-b border-slate-200 bg-slate-50/90 px-2 py-2" role="tablist" aria-label="ChA�»n nhĂ³m nguyĂªn liA�»‡u">
                            <?php foreach ($groupEntries as $ti => $g): ?>
                                <?php
                                $tabId = 'ingredient-drag-tab-' . $ti;
                                $panelId = 'ingredient-drag-panel-' . $ti;
                                $isFirstTab = $ti === 0;
                                ?>
                                <button
                                    type="button"
                                    id="<?= htmlspecialchars($tabId, ENT_QUOTES, 'UTF-8'); ?>"
                                    role="tab"
                                    aria-selected="<?= $isFirstTab ? 'true' : 'false'; ?>"
                                    aria-controls="<?= htmlspecialchars($panelId, ENT_QUOTES, 'UTF-8'); ?>"
                                    data-ingredient-tab="<?= (int) $ti; ?>"
                                    class="ingredient-drag-tab shrink-0 rounded-t-lg px-3 py-2.5 text-left text-sm font-semibold transition-colors <?= $isFirstTab ? 'border-b-2 border-primary bg-white text-primary' : 'border-b-2 border-transparent text-slate-600 hover:bg-white hover:text-slate-900'; ?>"
                                >
                                    <span class="mr-1" aria-hidden="true"><?= htmlspecialchars($g['emoji'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?= htmlspecialchars($g['name'], ENT_QUOTES, 'UTF-8'); ?>
                                    <span class="ml-1 text-xs font-medium opacity-80">(<?= count($g['items']); ?>)</span>
                                </button>
                            <?php endforeach; ?>
                        </div>

                        <?php foreach ($groupEntries as $ti => $g): ?>
                            <?php
                            $panelId = 'ingredient-drag-panel-' . $ti;
                            $tabId = 'ingredient-drag-tab-' . $ti;
                            $items = $g['items'];
                            $isFirstPanel = $ti === 0;
                            ?>
                            <div
                                id="<?= htmlspecialchars($panelId, ENT_QUOTES, 'UTF-8'); ?>"
                                role="tabpanel"
                                aria-labelledby="<?= htmlspecialchars($tabId, ENT_QUOTES, 'UTF-8'); ?>"
                                data-ingredient-panel="<?= (int) $ti; ?>"
                                class="<?= $isFirstPanel ? '' : 'hidden'; ?> p-4"
                            >
                                <section class="rounded-xl border border-slate-100 bg-slate-50/40 p-3" data-group-section>
                                    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                                        <p class="text-sm text-slate-600">
                                            <span class="font-bold text-slate-800"><?= htmlspecialchars($g['emoji'] . ' ' . $g['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                            A�€” <span data-group-count><?= count($items); ?></span> mA�»¥c (cuA�»™n ngang tab phĂ­a trĂªn A�‘A�»ƒ A�‘A�»•i nhĂ³m)
                                        </p>
                                    </div>
                                    <div class="relative grid grid-cols-5 gap-2 sm:gap-3">
                                        <p data-group-empty class="col-span-full hidden rounded-lg border border-dashed border-slate-200 bg-white px-4 py-6 text-center text-sm text-slate-500">
                                            Không cĂ³ nguyĂªn liA�»‡u khA�»›p Ă´ tĂ¬m nhanh A�€” thA�»­ tA�»« khóa khĂ¡c hoA�º·c chA�»n tab nhĂ³m khĂ¡c.
                                        </p>
                                        <?php foreach ($items as $item): ?>
                                            <?php
                                            $id = (int) ($item['id'] ?? 0);
                                            $name = (string) ($item['name'] ?? '');
                                            $image = trim((string) ($item['image'] ?? ''));
                                            $imageUrl = $image !== '' ? (str_starts_with($image, 'http') ? $image : (URLROOT . '/uploads/' . ltrim($image, '/'))) : '';
                                            ?>
                                            <article class="rounded-xl border border-slate-200 bg-white p-2 transition-shadow" data-ingredient-item data-name="<?= htmlspecialchars(mb_strtolower($name, 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?>">
                                                <button
                                                    type="button"
                                                    draggable="true"
                                                    data-ingredient-card
                                                    data-ingredient-id="<?= $id; ?>"
                                                    data-ingredient-name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>"
                                                    title="NhA�º¥p A�‘Ăºp A�‘A�»ƒ thĂªm hoA�º·c bA�» khA�»i khay"
                                                    class="w-full rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                                                >
                                                    <?php if ($imageUrl !== ''): ?>
                                                        <img src="<?= htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>" class="h-24 w-full rounded-lg object-cover">
                                                    <?php else: ?>
                                                        <div class="flex h-24 w-full items-center justify-center rounded-lg bg-amber-50 text-sm font-semibold text-amber-700"><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></div>
                                                    <?php endif; ?>
                                                </button>
                                                <p class="mt-2 line-clamp-2 text-xs font-semibold text-slate-700"><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></p>
                                            </article>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="mt-3 flex items-center justify-end gap-2">
                                        <button type="button" data-page-prev class="rounded border border-slate-300 px-2 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-40">TrA�°A�»›c</button>
                                        <span class="text-xs text-slate-500" data-page-label>Trang 1/1</span>
                                        <button type="button" data-page-next class="rounded border border-slate-300 px-2 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-40">Sau</button>
                                    </div>
                                </section>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <aside class="space-y-4 xl:col-span-1">
                <div id="drop-basket" class="rounded-2xl border-2 border-dashed border-amber-300 bg-amber-50/60 p-4">
                    <h3 class="text-base font-bold text-slate-900">Khay nguyĂªn liA�»‡u</h3>
                    <p class="mt-1 text-xs text-slate-600">KĂ©o thA�º£ vĂ o A�‘Ă¢y; hoA�º·c nhA�º¥p A�‘Ăºp nguyĂªn liA�»‡u A�»Ÿ lA�°A�»›i. Trong khay, nhA�º¥p A�‘Ăºp tĂªn A�‘A�»ƒ bA�».</p>
                    <div id="selected-ingredients" class="mt-3 min-h-20 space-y-2">
                        <p class="text-sm text-slate-500">ChA�°a cĂ³ nguyĂªn liA�»‡u.</p>
                    </div>
                    <button id="clear-selected" type="button" class="mt-3 w-full rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">XĂ³a tA�º¥t cA�º£</button>
                </div>

                <div class="rounded-2xl border border-primary/10 bg-white p-4 shadow-sm">
                    <h3 class="text-base font-bold text-slate-900">Món A�ƒn gA�»£i Ă½</h3>
                    <p id="search-status" aria-live="polite" class="mt-1 text-xs text-slate-500">SA�ºµn sĂ ng.</p>
                    <div id="search-error" role="alert" class="mt-2 hidden rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700"></div>
                </div>
            </aside>
        </div>

        <div class="rounded-2xl border border-primary/10 bg-white p-5 shadow-sm">
            <div id="result-grid" class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4"></div>
        </div>
    </div>
</section>

<script>
(() => {
    const suggestEndpoint = '<?= URLROOT; ?>/ml/suggest-recipes';
    const csrfToken = '<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>';
    const selected = new Map();

    const dropBasket = document.getElementById('drop-basket');
    const selectedEl = document.getElementById('selected-ingredients');
    const clearBtn = document.getElementById('clear-selected');
    const statusEl = document.getElementById('search-status');
    const errorEl = document.getElementById('search-error');
    const resultGridEl = document.getElementById('result-grid');
    const searchInputEl = document.getElementById('ingredient-search');
    const groupSections = Array.from(document.querySelectorAll('[data-group-section]'));
    const tabButtons = Array.from(document.querySelectorAll('[data-ingredient-tab]'));
    const tabPanels = Array.from(document.querySelectorAll('[data-ingredient-panel]'));
    const pageSize = 10;

    const activateIngredientTab = (index) => {
        const idx = String(index);
        tabButtons.forEach((btn) => {
            const on = btn.getAttribute('data-ingredient-tab') === idx;
            btn.setAttribute('aria-selected', on ? 'true' : 'false');
            btn.classList.toggle('border-primary', on);
            btn.classList.toggle('border-b-2', on);
            btn.classList.toggle('bg-white', on);
            btn.classList.toggle('text-primary', on);
            btn.classList.toggle('border-transparent', !on);
            btn.classList.toggle('text-slate-600', !on);
            btn.classList.toggle('hover:bg-white', !on);
            btn.classList.toggle('hover:text-slate-900', !on);
        });
        tabPanels.forEach((panel) => {
            panel.classList.toggle('hidden', panel.getAttribute('data-ingredient-panel') !== idx);
        });
        const activePanel = tabPanels.find((p) => p.getAttribute('data-ingredient-panel') === idx);
        const activeSection = activePanel && activePanel.querySelector('[data-group-section]');
        if (activeSection && typeof activeSection._renderPage === 'function') {
            activeSection._renderPage();
        }
    };

    tabButtons.forEach((btn) => {
        btn.addEventListener('click', () => activateIngredientTab(btn.getAttribute('data-ingredient-tab')));
    });

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
        if (text === '') {
            return { success: false, message: 'Server khĂ´ng trA�º£ dA�»¯ liA�»‡u.' };
        }
        try {
            return JSON.parse(text);
        } catch (_) {
            return { success: false, message: text };
        }
    };

    const syncIngredientGridSelection = () => {
        document.querySelectorAll('[data-ingredient-item]').forEach((article) => {
            const card = article.querySelector('[data-ingredient-card]');
            if (!card) return;
            const id = Number(card.getAttribute('data-ingredient-id') || 0);
            const on = id > 0 && selected.has(id);
            article.classList.toggle('border-primary', on);
            article.classList.toggle('ring-2', on);
            article.classList.toggle('ring-primary', on);
        });
    };

    const renderSelected = () => {
        const values = Array.from(selected.values());
        if (!values.length) {
            selectedEl.innerHTML = '<p class="text-sm text-slate-500">ChA�°a cĂ³ nguyĂªn liA�»‡u.</p>';
            syncIngredientGridSelection();
            return;
        }
        selectedEl.innerHTML = values.map((item) => `
            <div
                class="cursor-pointer select-none rounded-lg border border-slate-200 bg-white px-2 py-1.5 hover:bg-slate-50"
                data-basket-ingredient-id="${item.id}"
                title="NhA�º¥p A�‘Ăºp A�‘A�»ƒ bA�» khA�»i khay"
            >
                <span class="text-xs font-semibold text-slate-700">${item.name}</span>
            </div>
        `).join('');
        selectedEl.querySelectorAll('[data-basket-ingredient-id]').forEach((row) => {
            row.addEventListener('dblclick', () => {
                const id = Number(row.getAttribute('data-basket-ingredient-id') || 0);
                if (id > 0) {
                    selected.delete(id);
                    renderSelected();
                    runSearch();
                }
            });
        });
        syncIngredientGridSelection();
    };

    const renderCards = (recipes) => {
        if (!Array.isArray(recipes) || recipes.length === 0) {
            resultGridEl.innerHTML = '<div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500">ChA�°a cĂ³ mĂ³n phĂ¹ hA�»£p.</div>';
            return;
        }
        resultGridEl.innerHTML = recipes.map((recipe, index) => {
            const image = String(recipe.image || '').trim();
            const imageUrl = image !== ''
                ? (image.startsWith('http') ? image : `<?= URLROOT; ?>/uploads/${image.replace(/^\/+/, '')}`)
                : '';
            const title = String(recipe.title || 'CĂ´ng thA�»©c');
            const desc = String(recipe.description || '');
            const url = String(recipe.url || '#');
            const mc = recipe.matched_count != null ? Number(recipe.matched_count) : null;
            const ti = recipe.total_ingredients != null ? Number(recipe.total_ingredients) : null;
            let pctRecipe = recipe.ingredient_match_percent != null ? Number(recipe.ingredient_match_percent) : null;
            let pctSel = recipe.selection_match_percent != null ? Number(recipe.selection_match_percent) : null;
            if (pctRecipe == null && mc != null && ti != null && ti > 0) {
                pctRecipe = Math.round((mc / ti) * 100);
            }
            const basketN = selected.size;
            if (pctSel == null && mc != null && basketN > 0) {
                pctSel = Math.round((mc / basketN) * 100);
            }
            const parts = [];
            if (mc != null && ti != null && ti > 0) {
                parts.push(`${mc}/${ti} trong mĂ³n`);
            }
            if (pctSel != null && mc != null) {
                parts.push(`${pctSel}% nguyĂªn liA�»‡u bA�º¡n chA�»n (${mc}/${basketN})`);
            }
            const metaLine = parts.length ? `<p class="mt-2 text-[11px] leading-snug text-slate-500">${parts.join(' · ')}</p>` : '';
            return `
                <article class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    ${imageUrl ? `<img src="${imageUrl}" alt="A�º¢nh mĂ³n A�ƒn" class="h-36 w-full object-cover">` : ''}
                    <div class="p-3">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <p class="text-[11px] font-semibold text-slate-400">MĂ³n #${index + 1}</p>
                            ${pctRecipe != null ? `<span class="rounded-full bg-primary/10 px-2 py-0.5 text-[11px] font-bold text-primary">${pctRecipe}% nguyĂªn liA�»‡u mĂ³n</span>` : ''}
                        </div>
                        <h4 class="mt-1 line-clamp-2 text-sm font-bold text-slate-900">${title}</h4>
                        <p class="mt-1 line-clamp-2 text-xs text-slate-600">${desc}</p>
                        ${metaLine}
                        <a href="${url}" class="mt-2 inline-flex rounded-lg bg-amber-100 px-3 py-1.5 text-xs font-bold text-amber-700 hover:bg-amber-200">Xem chi tiA�º¿t</a>
                    </div>
                </article>
            `;
        }).join('');
    };

    const runSearch = async () => {
        const ingredients = Array.from(selected.values()).map((v) => v.name).filter(Boolean);
        if (!ingredients.length) {
            setStatus('KĂ©o thA�º£ nguyĂªn liA�»‡u A�‘A�»ƒ bA�º¯t A�‘A�º§u.');
            setError('');
            renderCards([]);
            return;
        }

        setStatus('Ă„ang tìm mĂ³n...');
        setError('');

        try {
            const response = await fetch(suggestEndpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': csrfToken,
                },
                body: JSON.stringify({
                    ingredients,
                    limit: 12,
                }),
            });
            const data = await readApiPayload(response);
            if (!response.ok || !data.success) {
                throw new Error((data && data.message) ? data.message : 'Không thA�»ƒ tĂ¬m mĂ³n lĂºc nĂ y.');
            }
            const recipes = Array.isArray(data.data && data.data.recipes) ? data.data.recipes : [];
            renderCards(recipes);
            setStatus(`A�Ă£ tĂ¬m thA�º¥y ${recipes.length} mĂ³n phĂ¹ hA�»£p.`);
        } catch (err) {
            setStatus('TĂ¬m kiA�º¿m thA�º¥t bA�º¡i.');
            setError((err && err.message) ? String(err.message) : 'CĂ³ lA�»—i xA�º£y ra.');
        }
    };

    const addIngredient = (id, name) => {
        if (!id || !name) return;
        selected.set(Number(id), { id: Number(id), name: String(name) });
        renderSelected();
        runSearch();
    };

    const toggleIngredient = (id, name) => {
        if (!id || !name) return;
        const n = Number(id);
        if (selected.has(n)) {
            selected.delete(n);
        } else {
            selected.set(n, { id: n, name: String(name) });
        }
        renderSelected();
        runSearch();
    };

    document.addEventListener('dragstart', (e) => {
        const el = e.target && e.target.closest ? e.target.closest('[data-ingredient-card]') : null;
        if (!el) return;
        const id = Number(el.getAttribute('data-ingredient-id') || 0);
        const name = String(el.getAttribute('data-ingredient-name') || '');
        e.dataTransfer.setData('text/plain', JSON.stringify({ id, name }));
        e.dataTransfer.effectAllowed = 'copy';
    });

    document.addEventListener('dblclick', (e) => {
        const card = e.target && e.target.closest ? e.target.closest('[data-ingredient-card]') : null;
        if (!card) return;
        e.preventDefault();
        const id = Number(card.getAttribute('data-ingredient-id') || 0);
        const name = String(card.getAttribute('data-ingredient-name') || '');
        toggleIngredient(id, name);
    });

    const setupGroupPagination = () => {
        groupSections.forEach((section) => {
            const cards = Array.from(section.querySelectorAll('[data-ingredient-item]'));
            const prevBtn = section.querySelector('[data-page-prev]');
            const nextBtn = section.querySelector('[data-page-next]');
            const pageLabel = section.querySelector('[data-page-label]');
            const countEl = section.querySelector('[data-group-count]');
            const emptyEl = section.querySelector('[data-group-empty]');

            let currentPage = 1;
            const renderPage = () => {
                const keyword = String(searchInputEl && searchInputEl.value ? searchInputEl.value : '').trim().toLowerCase();
                const filtered = cards.filter((card) => {
                    const normalized = String(card.getAttribute('data-name') || '');
                    return keyword === '' || normalized.includes(keyword);
                });
                const totalPages = filtered.length === 0 ? 0 : Math.max(1, Math.ceil(filtered.length / pageSize));
                if (totalPages === 0) {
                    currentPage = 1;
                } else if (currentPage > totalPages) {
                    currentPage = totalPages;
                }
                const start = (currentPage - 1) * pageSize;
                const end = start + pageSize;
                const visible = new Set(filtered.slice(start, end));

                cards.forEach((card) => {
                    card.classList.toggle('hidden', !visible.has(card));
                });

                if (filtered.length === 0) {
                    if (emptyEl) emptyEl.classList.remove('hidden');
                    if (countEl) countEl.textContent = '0';
                    if (pageLabel) pageLabel.textContent = 'Trang 0/0';
                    if (prevBtn) prevBtn.disabled = true;
                    if (nextBtn) nextBtn.disabled = true;
                    return;
                }
                if (emptyEl) emptyEl.classList.add('hidden');
                if (countEl) countEl.textContent = String(filtered.length);
                if (pageLabel) pageLabel.textContent = `Trang ${currentPage}/${totalPages}`;
                if (prevBtn) prevBtn.disabled = currentPage <= 1;
                if (nextBtn) nextBtn.disabled = currentPage >= totalPages;
            };

            section._resetToFirstPage = () => {
                currentPage = 1;
            };

            if (prevBtn) {
                prevBtn.addEventListener('click', () => {
                    currentPage = Math.max(1, currentPage - 1);
                    renderPage();
                });
            }
            if (nextBtn) {
                nextBtn.addEventListener('click', () => {
                    currentPage += 1;
                    renderPage();
                });
            }

            section._renderPage = renderPage;
            renderPage();
        });
    };

    if (searchInputEl) {
        searchInputEl.addEventListener('input', () => {
            groupSections.forEach((section) => {
                if (typeof section._resetToFirstPage === 'function') {
                    section._resetToFirstPage();
                }
                if (typeof section._renderPage === 'function') {
                    section._renderPage();
                }
            });
        });
    }

    dropBasket.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropBasket.classList.add('border-primary');
    });
    dropBasket.addEventListener('dragleave', () => {
        dropBasket.classList.remove('border-primary');
    });
    dropBasket.addEventListener('drop', (e) => {
        e.preventDefault();
        dropBasket.classList.remove('border-primary');
        const raw = e.dataTransfer.getData('text/plain');
        if (!raw) return;
        try {
            const payload = JSON.parse(raw);
            addIngredient(Number(payload.id || 0), String(payload.name || ''));
        } catch (_) {}
    });

    clearBtn.addEventListener('click', () => {
        selected.clear();
        renderSelected();
        runSearch();
    });

    setupGroupPagination();
    renderCards([]);
})();
</script>

