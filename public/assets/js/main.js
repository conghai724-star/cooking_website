'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const showToast = (message) => {
        if (!message) return;
        const toast = document.createElement('div');
        toast.className = 'fixed bottom-5 right-5 z-[9999] rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-lg';
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 1500);
    };

    const closeModalBySelector = (selector) => {
        if (!selector) return;
        const modal = document.querySelector(selector);
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    };

    const parseResponseData = async (response, options = {}) => {
        const allowTextSuccess = options && options.allowTextSuccess === true;
        const rawText = await response.text();
        const text = String(rawText || '').trim();
        const contentType = String(response.headers.get('content-type') || '').toLowerCase();
        const looksLikeJson = text.startsWith('{') || text.startsWith('[');

        if (text !== '' && (contentType.includes('application/json') || looksLikeJson)) {
            try {
                return JSON.parse(text);
            } catch (_err) {
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

        if (allowTextSuccess && response.ok) {
            return {
                success: true,
                message: text,
            };
        }

        return {
            success: false,
            message: text,
        };
    };
    const setupShareButtons = () => {
        document.querySelectorAll('[data-share-btn]').forEach((btn) => {
            if (btn.dataset.shareBound === '1') return;
            btn.dataset.shareBound = '1';

            btn.addEventListener('click', async () => {
                const shareUrl = btn.getAttribute('data-share-url') || window.location.href;
                const shareText = btn.getAttribute('data-share-text') || 'Xem noi dung nay';
                const shareTitle = btn.getAttribute('data-share-title') || document.title;

                try {
                    if (navigator.share && window.isSecureContext) {
                        await navigator.share({ title: shareTitle, text: shareText, url: shareUrl });
                        return;
                    }

                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        await navigator.clipboard.writeText(shareUrl);
                        showToast('Da sao chep lien ket');
                        return;
                    }

                    window.prompt('Sao chep lien ket:', shareUrl);
                } catch (_err) {
                    window.prompt('Sao chep lien ket:', shareUrl);
                }
            });
        });
    };

    const setupCopyLinkButtons = () => {
        document.querySelectorAll('[data-copy-link-btn]').forEach((btn) => {
            if (btn.dataset.copyBound === '1') return;
            btn.dataset.copyBound = '1';

            btn.addEventListener('click', async () => {
                const link = btn.getAttribute('data-link') || window.location.href;
                try {
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        await navigator.clipboard.writeText(link);
                        showToast('Da sao chep lien ket');
                    } else {
                        window.prompt('Sao chep lien ket:', link);
                    }
                } catch (_err) {
                    window.prompt('Sao chep lien ket:', link);
                }
            });
        });
    };

    const setupReportReasonFields = () => {
        document.querySelectorAll('[data-report-reason-select]').forEach((select) => {
            if (select.dataset.reasonBound === '1') return;
            select.dataset.reasonBound = '1';

            const targetSelector = select.getAttribute('data-report-other-target');
            if (!targetSelector) return;
            const target = document.querySelector(targetSelector);
            if (!target) return;

            const sync = () => {
                const v = (select.value || '').trim().toLowerCase();
                target.classList.toggle('hidden', v !== 'khac' && v !== 'khÄ‚â€Ă¢â‚¬ÂÄ‚Â¢Ă¢â€Â¬Ă‚ÂĂ„â€Ă‚Â¢Ä‚Â¢Ă¢â‚¬ÂĂ‚Â¬Ä‚â€Ă‚ÂÄ‚â€Ă¢â‚¬ÂÄ‚Â¢Ă¢â€Â¬Ă‚ÂĂ„â€Ă¢â‚¬ÂÄ‚â€Ă‚Â¡c');
            };

            sync();
            select.addEventListener('change', sync);
        });
    };

    const setupModalToggles = () => {
        document.querySelectorAll('[data-modal-open]').forEach((btn) => {
            if (btn.dataset.modalOpenBound === '1') return;
            btn.dataset.modalOpenBound = '1';
            btn.addEventListener('click', () => {
                const selector = btn.getAttribute('data-modal-open');
                const modal = selector ? document.querySelector(selector) : null;
                if (!modal) return;
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            });
        });

        document.querySelectorAll('[data-modal-close]').forEach((btn) => {
            if (btn.dataset.modalCloseBound === '1') return;
            btn.dataset.modalCloseBound = '1';
            btn.addEventListener('click', () => closeModalBySelector(btn.getAttribute('data-modal-close')));
        });

        document.querySelectorAll('[data-modal-overlay]').forEach((modal) => {
            if (modal.dataset.modalOverlayBound === '1') return;
            modal.dataset.modalOverlayBound = '1';
            modal.addEventListener('click', (e) => {
                if (e.target !== modal) return;
                closeModalBySelector('#' + modal.id);
            });
        });
    };

    const setupConfirmActions = () => {
        document.querySelectorAll('form[data-confirm]').forEach((form) => {
            if (form.dataset.confirmBound === '1') return;
            form.dataset.confirmBound = '1';
            form.addEventListener('submit', (e) => {
                const message = form.getAttribute('data-confirm') || 'BA�º¡n cĂ³ chA�º¯c muA�»‘n tiA�º¿p tA�»¥c?';
                if (!window.confirm(message)) e.preventDefault();
            });
        });

        document.querySelectorAll('[data-confirm-click]').forEach((el) => {
            if (el.dataset.confirmBound === '1') return;
            el.dataset.confirmBound = '1';
            el.addEventListener('click', (e) => {
                const message = el.getAttribute('data-confirm-click') || 'BA�º¡n cĂ³ chA�º¯c muA�»‘n tiA�º¿p tA�»¥c?';
                if (!window.confirm(message)) e.preventDefault();
            });
        });
    };

    const setupAjaxForms = () => {
        document.querySelectorAll('form[data-ajax-form]').forEach((form) => {
            if (form.dataset.ajaxBound === '1') return;
            form.dataset.ajaxBound = '1';

            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) submitBtn.disabled = true;

                try {
                    const response = await fetch(form.action, {
                        method: (form.method || 'POST').toUpperCase(),
                        body: new FormData(form),
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    });
                    const data = await parseResponseData(response, { allowTextSuccess: true });

                    if (!response.ok || data.success === false) {
                        throw new Error(data.message || 'Request failed');
                    }

                    showToast(data.message || 'ĐA� xử lA� thA�nh cA�ng');

                    const closeTarget = form.getAttribute('data-close-target');
                    if (closeTarget) closeModalBySelector(closeTarget);
                } catch (err) {
                    showToast((err && err.message) ? String(err.message) : 'KhĂ´ng thA�»ƒ xA�»­ lĂ½ lĂºc nĂ y');
                } finally {
                    if (submitBtn) submitBtn.disabled = false;
                }
            });
        });
    };
    const setupCommentAjax = () => {
        document.querySelectorAll('form[data-comment-ajax]').forEach((form) => {
            if (form.dataset.commentAjaxBound === '1') return;
            form.dataset.commentAjaxBound = '1';

            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) submitBtn.disabled = true;

                try {
                    const response = await fetch(form.action, {
                        method: (form.method || 'POST').toUpperCase(),
                        body: new FormData(form),
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    });
                    const data = await parseResponseData(response);
                    if (!response.ok || data.success === false) {
                        throw new Error((data && data.message) ? data.message : 'KhĂ´ng thA�»ƒ gA�»­i bĂ¬nh luA�º­n');
                    }

                    const selector = form.getAttribute('data-comments-root') || '';
                    const redirectUrl = ((data && data.data && data.data.redirect) ? data.data.redirect : '').trim();

                    if (selector && redirectUrl) {
                        const htmlResponse = await fetch(redirectUrl, {
                            method: 'GET',
                            headers: { 'X-Requested-With': 'XMLHttpRequest' },
                            credentials: 'same-origin',
                        });
                        const htmlText = await htmlResponse.text();
                        const parser = new DOMParser();
                        const nextDoc = parser.parseFromString(htmlText, 'text/html');
                        const nextRoot = nextDoc.querySelector(selector);
                        const currentRoot = document.querySelector(selector);
                        if (nextRoot && currentRoot) {
                            currentRoot.replaceWith(nextRoot);
                            setupCommentAjax();
                            setupCommentVotes();
                        }
                    }

                    const textarea = form.querySelector('textarea[name="content"]');
                    if (textarea) textarea.value = '';
                    showToast((data && data.message) ? data.message : 'Da dang binh luan');
                } catch (err) {
                    showToast((err && err.message) ? String(err.message) : 'Khong the gui binh luan');
                } finally {
                    if (submitBtn) submitBtn.disabled = false;
                }
            });
        });
    };

    const setupChatWidget = () => {
        const root = document.querySelector('[data-chat-widget]');
        if (!root || root.dataset.chatBound === '1') return;
        root.dataset.chatBound = '1';

        const endpoint = root.getAttribute('data-chat-endpoint') || '';
        const csrfToken = root.getAttribute('data-csrf-token') || '';
        const toggleBtn = root.querySelector('[data-chat-toggle]');
        const closeBtn = root.querySelector('[data-chat-close]');
        const panel = root.querySelector('[data-chat-panel]');
        const form = root.querySelector('[data-chat-form]');
        const input = root.querySelector('[data-chat-input]');
        const sendBtn = root.querySelector('[data-chat-send]');
        const messages = root.querySelector('[data-chat-messages]');

        if (!endpoint || !toggleBtn || !panel || !form || !input || !sendBtn || !messages) return;
        let appBasePath = '';
        try {
            const endpointUrl = new URL(endpoint, window.location.origin);
            const endpointPath = endpointUrl.pathname.replace(/\/+$/, '');
            appBasePath = endpointPath.endsWith('/chat') ? endpointPath.slice(0, -5) : endpointPath;
        } catch (_err) {
            appBasePath = '';
        }
        const defaultSendLabel = sendBtn.textContent || 'Gui';
                const defaultInputPlaceholder = input.getAttribute('placeholder') || '';
        const localizeChatText = (text) => {
            const normalized = String(text || '').trim();
            if (!normalized) return '';
            const map = {
                'Toi chua hieu ro cau hoi. Ban thu hoi theo mau ben duoi.': 'TĂ´i chA�°a hiA�»ƒu rĂµ cĂ¢u hA�»i. BA�º¡n thA�»­ hA�»i theo mA�º«u bĂªn dA�°A�»›i.',
                'Toi muon vao tai khoan': 'TĂ´i muA�»‘n vĂ o tĂ i khoA�º£n',
                'Co mon an it calo khong?': 'CĂ„â€Ă¢â‚¬ÂÄ‚Â¢Ă¢â€Â¬Ă‚ÂĂ„â€Ă¢â‚¬ÂÄ‚â€Ă‚Â³ mĂ„â€Ă¢â‚¬ÂÄ‚Â¢Ă¢â€Â¬Ă‚ÂĂ„â€Ă¢â‚¬ÂÄ‚â€Ă‚Â³n Ä‚â€Ă¢â‚¬ÂÄ‚Â¢Ă¢â€Â¬Ă‚ÂĂ„â€Ă¢â‚¬Â Ä‚Â¢Ă¢â€Â¬Ă¢â€Â¢n Ă„â€Ă¢â‚¬ÂÄ‚Â¢Ă¢â€Â¬Ă‚ÂĂ„â€Ă¢â‚¬ÂÄ‚â€Ă‚Â­t calo khĂ„â€Ă¢â‚¬ÂÄ‚Â¢Ă¢â€Â¬Ă‚ÂĂ„â€Ă¢â‚¬ÂÄ‚â€Ă‚Â´ng?',
                'Xem ke hoach bua an o dau?': 'Xem kA�º¿ hoA�º¡ch bA�»¯a A�ƒn A�»Ÿ A�‘Ă¢u?',
                'Ban vao trang Dang nhap de truy cap tai khoan.': 'Bạn vA�o trang Đăng nhập để truy cập tA�i khoản.',
                'Ban vao trang Dang ky de tao tai khoan moi.': 'Bạn vA�o trang Đăng kA� để tạo tA�i khoản mới.',
                'Ban vao trang Quen mat khau de dat lai mat khau.': 'BA�º¡n vĂ o trang QuĂªn mA�º­t khA�º©u A�‘A�»ƒ A�‘A�º·t lA�º¡i mA�º­t khA�º©u.',
                'Ban co the xem danh sach cong thuc tai trang Cong thuc.': 'BA�º¡n cĂ³ thA�»ƒ xem danh sĂ¡ch cĂ´ng thA�»©c tA�º¡i trang CĂ´ng thA�»©c.',
                'Toi co the goi y mon an phu hop.': 'TĂ´i cĂ³ thA�»ƒ gA�»£i Ă½ mĂ³n A�ƒn phĂ¹ hA�»£p.',
                'Ban vao trang Lap ke hoach de xem va quan ly thuc don.': 'BA�º¡n vĂ o trang LA�º­p kA�º¿ hoA�º¡ch A�‘A�»ƒ xem vĂ  quA�º£n lĂ½ thA�»±c A�‘A�¡n.',
                'Toi co the goi y mon giam can theo bua sang trua toi.': 'TĂ´i cĂ³ thA�»ƒ gA�»£i Ă½ mĂ³n giA�º£m cĂ¢n theo bA�»¯a sĂ¡ng, trA�°a, tA�»‘i.',
                'Toi co the goi y mon it calo theo muc kcal ban muon.': 'TĂ´i cĂ³ thA�»ƒ gA�»£i Ă½ mĂ³n Ă­t calo theo mA�»©c kcal bA�º¡n muA�»‘n.',
                'Toi co the uoc tinh calo cho mon gan nhat ma ban dang xem.': 'TĂ´i cĂ³ thA�»ƒ A�°A�»›c tĂ­nh calo cho mĂ³n gA�º§n nhA�º¥t mĂ  bA�º¡n A�‘ang xem.',
                'Ban co the xem ho so tai trang Ho so.': 'BA�º¡n cĂ³ thA�»ƒ xem hA�»“ sA�¡ tA�º¡i trang HA�»“ sA�¡.',
                'Ban muon bua nao?': 'BA�º¡n muA�»‘n bA�»¯a nĂ o?',
                'Ban co nguyen lieu gi?': 'BA�º¡n cĂ³ nguyĂªn liA�»‡u gĂ¬?',
                'Ban muon tranh nguyen lieu nao?': 'BA�º¡n muA�»‘n trĂ¡nh nguyĂªn liA�»‡u nĂ o?',
                'Ban muon gioi han bao nhieu kcal?': 'BA�º¡n muA�»‘n giA�»›i hA�º¡n bao nhiĂªu kcal?',
                'Ban uu tien mon thit hay mon chay?': 'BA�º¡n A�°u tiĂªn mĂ³n thA�»‹t hay mĂ³n chay?',
                'Neu ban muon chinh xac hon, hay gui ten mon cu the.': 'NA�º¿u bA�º¡n muA�»‘n chĂ­nh xĂ¡c hA�¡n, hĂ£y gA�»­i tĂªn mĂ³n cA�»¥ thA�»ƒ.',
            };
            return map[normalized] || normalized;
        };
        let isSending = false;
        let typingNode = null;

        const appendMessage = (text, role = 'bot') => {
            if (!text) return;
            const item = document.createElement('div');
            item.className = role === 'user'
                ? 'ml-10 rounded-xl bg-primary px-3 py-2 text-white'
                : 'mr-10 rounded-xl bg-white px-3 py-2 text-slate-800 border border-slate-200';
            item.textContent = role === 'user' ? text : localizeChatText(text);
            messages.appendChild(item);
            messages.scrollTop = messages.scrollHeight;
        };

        const isSafeUrl = (url) => {
            if (!url) return false;
            if (url.startsWith('/')) return true;
            return /^https?:\/\//i.test(url);
        };

        const resolveActionUrl = (url) => {
            const raw = (url || '').trim();
            if (!raw) return '';
            if (/^https?:\/\//i.test(raw)) return raw;

            const path = raw.startsWith('/') ? raw : ('/' + raw);
            if (!appBasePath) return path;
            if (path === appBasePath || path.startsWith(appBasePath + '/')) return path;

            return appBasePath + path;
        };

        const appendActions = (actions) => {
            if (!Array.isArray(actions) || actions.length === 0) return;

            const row = document.createElement('div');
            row.className = 'mr-10 mt-2 flex flex-wrap gap-2';

            actions.slice(0, 4).forEach((action) => {
                const label = ((action && action.label) ? String(action.label) : '').trim();
                const url = ((action && action.url) ? String(action.url) : '').trim();
                if (!label || !isSafeUrl(url)) return;

                const link = document.createElement('a');
                link.className = 'inline-flex items-center rounded-full border border-amber-300 bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 hover:bg-amber-100';
                link.href = resolveActionUrl(url);
                link.textContent = label;
                row.appendChild(link);
            });

            if (row.childElementCount > 0) {
                messages.appendChild(row);
                messages.scrollTop = messages.scrollHeight;
            }
        };

        const togglePanel = (show) => {
            panel.hidden = !show;
            if (show && messages.childElementCount === 0) {
                appendMessage('Xin chĂ o, tĂ´i cĂ³ thA�»ƒ hA�»— trA�»£ gĂ¬ cho bA�º¡n?');
            }
        };

        const setLoading = (loading) => {
            isSending = loading;
            sendBtn.disabled = loading;
            input.disabled = loading;
            sendBtn.textContent = loading ? 'Đang gửi...' : defaultSendLabel;
            input.placeholder = loading ? 'Đang xử lA�...' : defaultInputPlaceholder;
        };

        const showTyping = () => {
            if (typingNode) return;
            typingNode = document.createElement('div');
            typingNode.className = 'mr-10 rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-500';
            typingNode.textContent = 'Đang trả lời...';
            messages.appendChild(typingNode);
            messages.scrollTop = messages.scrollHeight;
        };

        const hideTyping = () => {
            if (!typingNode) return;
            typingNode.remove();
            typingNode = null;
        };

        const sendMessage = async (text) => {
            const message = (text || '').trim();
            if (!message || isSending) return;

            appendMessage(message, 'user');
            input.value = '';
            setLoading(true);
            showTyping();

            try {
                const body = new FormData();
                body.append('message', message);
                body.append('_csrf', csrfToken);

                const response = await fetch(endpoint, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-Token': csrfToken,
                    },
                    body,
                });
                const data = await parseResponseData(response);

                if (!response.ok || !data.success) {
                    throw new Error((data && data.message) || 'KhĂ´ng thA�»ƒ xA�»­ lĂ½');
                }

                hideTyping();
                appendMessage(data.message || 'ĐA� nhận cA�u hỏi của bạn.');
                const suggestions = Array.isArray(data.suggestions) ? data.suggestions : [];
                if (suggestions.length > 0) {
                    appendMessage('GA�»£i Ă½ tiA�º¿p: ' + suggestions.slice(0, 2).map(localizeChatText).join(' | '));
                }

                const actions = Array.isArray(data.actions) ? data.actions : [];
                if (actions.length > 0) {
                    appendActions(actions);
                } else {
                    const items = Array.isArray(data.items) ? data.items : [];
                    if (items.length > 0) {
                        appendActions(items.map((item) => ({
                            label: item && item.title ? String(item.title) : 'Xem',
                            url: item && item.url ? String(item.url) : '',
                        })));
                    }
                }
            } catch (err) {
                hideTyping();
                const errMsg = (err && err.message) ? String(err.message) : 'KhĂ„â€Ă¢â‚¬ÂÄ‚Â¢Ă¢â€Â¬Ă‚ÂĂ„â€Ă¢â‚¬ÂÄ‚â€Ă‚Â´ng xĂ„â€Ă¢â‚¬ÂÄ‚Â¢Ă¢â€Â¬Ă‚ÂĂ„â€Ă¢â‚¬ÂÄ‚â€Ă‚Â¡c Ä‚â€Ă¢â‚¬ÂÄ‚Â¢Ă¢â€Â¬Ă‚ÂĂ„â€Ă‚Â¢Ä‚Â¢Ă¢â‚¬ÂĂ‚Â¬Ä‚â€¹Ă…â€œÄ‚â€Ă¢â‚¬ÂÄ‚â€Ă‚Â¡Ă„â€Ă¢â‚¬ÂÄ‚â€Ă‚Â»Ă„â€Ă‚Â¢Ä‚Â¢Ă¢â‚¬ÂĂ‚Â¬Ä‚â€Ă‚Â¹nh';
                appendMessage('LA�»—i: ' + errMsg);
                if (window.console && console.error) {
                    console.error('chat_error', err);
                }
            } finally {
                setLoading(false);
                input.focus();
            }
        };

        toggleBtn.addEventListener('click', () => togglePanel(panel.hidden));
        if (closeBtn) closeBtn.addEventListener('click', () => togglePanel(false));

        const submitChat = (e) => {
            if (e && typeof e.preventDefault === 'function') e.preventDefault();
            sendMessage(input.value);
        };

        form.addEventListener('submit', submitChat);
        sendBtn.addEventListener('click', submitChat);
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !isSending) submitChat(e);
        });

        root.querySelectorAll('[data-chat-quick]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const text = btn.getAttribute('data-chat-quick') || '';
                if (isSending) return;
                if (panel.hidden) togglePanel(true);
                sendMessage(text);
            });
        });
    };
    const setupCommentVotes = () => {
        document.querySelectorAll('[data-comment-vote]').forEach((btn) => {
            if (btn.dataset.voteBound === '1') return;
            btn.dataset.voteBound = '1';

            btn.addEventListener('click', async () => {
                const commentId = (btn.getAttribute('data-comment-id') || '').trim();
                const voteUrl = (btn.getAttribute('data-vote-url') || '').trim();
                const csrfToken = (btn.getAttribute('data-csrf-token') || '').trim();
                if (!commentId || !voteUrl || !csrfToken) return;

                btn.disabled = true;
                try {
                    const body = new FormData();
                    body.append('_csrf', csrfToken);

                    const response = await fetch(voteUrl, {
                        method: 'POST',
                        body,
                        credentials: 'same-origin',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-Token': csrfToken,
                        },
                    });
                const data = await parseResponseData(response);

                    if (!response.ok || !data.success) {
                        throw new Error((data && data.message) ? data.message : 'Vote failed');
                    }

                    const payload = data.data || {};
                    const liked = !!payload.liked;
                    const likeCount = Number(payload.like_count || 0);
                    btn.classList.toggle('border-primary', liked);
                    btn.classList.toggle('text-primary', liked);

                    const countEl = document.querySelector('[data-comment-like-count="' + commentId + '"]');
                    if (countEl) countEl.textContent = String(likeCount);
                } catch (err) {
                    showToast((err && err.message) ? String(err.message) : 'Khong the vote luc nay');
                } finally {
                    btn.disabled = false;
                }
            });
        });
    };

    const setupVoiceSearchInputs = () => {
        const path = String(window.location.pathname || '');
        if (path.includes('/admin')) return;
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition || null;

        const candidates = Array.from(document.querySelectorAll('input[type="search"], input[name="q"], input[name="keyword"]'))
            .filter((input) => {
                if (!(input instanceof HTMLInputElement)) return false;
                if (input.disabled || input.readOnly) return false;
                if (input.dataset.voiceSearchBound === '1') return false;
                const type = String(input.type || '').toLowerCase();
                return type === 'search' || type === 'text';
            });

        candidates.forEach((input) => {
            input.dataset.voiceSearchBound = '1';

            const wrapper = document.createElement('span');
            wrapper.className = 'inline-flex w-full items-center gap-1';

            const parent = input.parentNode;
            if (!parent) return;
            parent.insertBefore(wrapper, input);
            wrapper.appendChild(input);

            input.classList.add('flex-1');

            const triggerSearch = () => {
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
                const form = input.form || input.closest('form');
                if (form) {
                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                    } else {
                        form.submit();
                    }
                }
            };

            const searchBtn = document.createElement('button');
            searchBtn.type = 'button';
            searchBtn.setAttribute('aria-label', 'TĂ¬m kiA�º¿m');
            searchBtn.title = 'TĂ¬m kiA�º¿m';
            searchBtn.className = 'inline-flex h-9 min-w-9 items-center justify-center rounded-lg border border-slate-300 bg-white px-2 text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-primary';
            searchBtn.innerHTML = '<span aria-hidden="true">đŸ”</span>';
            searchBtn.addEventListener('click', triggerSearch);
            wrapper.appendChild(searchBtn);

            const micBtn = document.createElement('button');
            micBtn.type = 'button';
            micBtn.setAttribute('aria-label', 'NhA�º­p tĂ¬m kiA�º¿m bA�º±ng giA�»ng nĂ³i');
            micBtn.title = 'TĂ¬m kiA�º¿m bA�º±ng giA�»ng nĂ³i';
            micBtn.className = 'inline-flex h-9 min-w-9 items-center justify-center rounded-lg border border-slate-300 bg-white px-2 text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-primary';
            micBtn.innerHTML = '<span aria-hidden="true">đŸ�?¤</span>';
            wrapper.appendChild(micBtn);

            let recognition = null;
            let listening = false;
            let autoSearchTimer = null;

            const stopListeningUi = () => {
                listening = false;
                micBtn.classList.remove('border-primary', 'text-primary', 'bg-amber-50');
            };

            if (!SpeechRecognition) {
                micBtn.disabled = true;
                micBtn.classList.add('opacity-50', 'cursor-not-allowed');
                micBtn.title = 'TrĂ¬nh duyA�»‡t chA�°a hA�»— trA�»£ giA�»ng nĂ³i';
                return;
            }

            micBtn.addEventListener('click', () => {
                try {
                    if (!recognition) {
                        recognition = new SpeechRecognition();
                        recognition.lang = 'vi-VN';
                        recognition.interimResults = false;
                        recognition.maxAlternatives = 1;

                        recognition.onresult = (event) => {
                            const transcript = String(event.results?.[0]?.[0]?.transcript || '').trim();
                            if (!transcript) return;
                            input.value = transcript;
                            input.dispatchEvent(new Event('input', { bubbles: true }));
                            input.dispatchEvent(new Event('change', { bubbles: true }));
                            if (autoSearchTimer) {
                                clearTimeout(autoSearchTimer);
                            }
                            autoSearchTimer = setTimeout(() => {
                                triggerSearch();
                            }, 800);
                        };
                        recognition.onend = stopListeningUi;
                        recognition.onerror = stopListeningUi;
                    }

                    if (listening) {
                        recognition.stop();
                        stopListeningUi();
                        return;
                    }

                    listening = true;
                    micBtn.classList.add('border-primary', 'text-primary', 'bg-amber-50');
                    recognition.start();
                } catch (_err) {
                    stopListeningUi();
                }
            });
        });
    };
    window.AppUI = {
        showToast,
        setupShareButtons,
        setupCopyLinkButtons,
        setupReportReasonFields,
        setupModalToggles,
        setupConfirmActions,
        setupAjaxForms,
        setupCommentAjax,
        setupCommentVotes,
        setupChatWidget,
        setupVoiceSearchInputs,
    };

    const safeRun = (fn, name) => {
        try {
            fn();
        } catch (err) {
            if (window.console && console.error) {
                console.error('ui_init_error:' + name, err);
            }
        }
    };

    // Keep chat resilient even if another UI module throws runtime errors.
    safeRun(setupChatWidget, 'chat');
    safeRun(setupShareButtons, 'share');
    safeRun(setupCopyLinkButtons, 'copy-link');
    safeRun(setupReportReasonFields, 'report-reason');
    safeRun(setupModalToggles, 'modal');
    safeRun(setupConfirmActions, 'confirm');
    safeRun(setupAjaxForms, 'ajax-form');
    safeRun(setupCommentAjax, 'comment-ajax');
    safeRun(setupCommentVotes, 'comment-vote');
    safeRun(setupVoiceSearchInputs, 'voice-search');
});











