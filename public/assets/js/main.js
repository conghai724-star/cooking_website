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
                target.classList.toggle('hidden', v !== 'khac' && v !== 'khác');
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
                const message = form.getAttribute('data-confirm') || 'Bạn có chắc muốn tiếp tục?';
                if (!window.confirm(message)) e.preventDefault();
            });
        });

        document.querySelectorAll('[data-confirm-click]').forEach((el) => {
            if (el.dataset.confirmBound === '1') return;
            el.dataset.confirmBound = '1';
            el.addEventListener('click', (e) => {
                const message = el.getAttribute('data-confirm-click') || 'Bạn có chắc muốn tiếp tục?';
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

                    const contentType = response.headers.get('content-type') || '';
                    const data = contentType.includes('application/json')
                        ? await response.json()
                        : { success: response.ok, message: await response.text() };

                    if (!response.ok || data.success === false) {
                        throw new Error(data.message || 'Request failed');
                    }

                    showToast(data.message || 'Da xu ly thanh cong');

                    const closeTarget = form.getAttribute('data-close-target');
                    if (closeTarget) closeModalBySelector(closeTarget);
                } catch (err) {
                    showToast((err && err.message) ? String(err.message) : 'Không thể xử lý lúc này');
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
                'Toi chua hieu ro cau hoi. Ban thu hoi theo mau ben duoi.': 'Tôi chưa hiểu rõ câu hỏi. Bạn thử hỏi theo mẫu bên dưới.',
                'Toi muon vao tai khoan': 'Tôi muốn vào tài khoản',
                'Co mon an it calo khong?': 'Có món ăn ít calo không?',
                'Xem ke hoach bua an o dau?': 'Xem kế hoạch bữa ăn ở đâu?',
                'Ban vao trang Dang nhap de truy cap tai khoan.': 'Bạn vào trang Đăng nhập để truy cập tài khoản.',
                'Ban vao trang Dang ky de tao tai khoan moi.': 'Bạn vào trang Đăng ký để tạo tài khoản mới.',
                'Ban vao trang Quen mat khau de dat lai mat khau.': 'Bạn vào trang Quên mật khẩu để đặt lại mật khẩu.',
                'Ban co the xem danh sach cong thuc tai trang Cong thuc.': 'Bạn có thể xem danh sách công thức tại trang Công thức.',
                'Toi co the goi y mon an phu hop.': 'Tôi có thể gợi ý món ăn phù hợp.',
                'Ban vao trang Lap ke hoach de xem va quan ly thuc don.': 'Bạn vào trang Lập kế hoạch để xem và quản lý thực đơn.',
                'Toi co the goi y mon giam can theo bua sang trua toi.': 'Tôi có thể gợi ý món giảm cân theo bữa sáng, trưa, tối.',
                'Toi co the goi y mon it calo theo muc kcal ban muon.': 'Tôi có thể gợi ý món ít calo theo mức kcal bạn muốn.',
                'Toi co the uoc tinh calo cho mon gan nhat ma ban dang xem.': 'Tôi có thể ước tính calo cho món gần nhất mà bạn đang xem.',
                'Ban co the xem ho so tai trang Ho so.': 'Bạn có thể xem hồ sơ tại trang Hồ sơ.',
                'Ban muon bua nao?': 'Bạn muốn bữa nào?',
                'Ban co nguyen lieu gi?': 'Bạn có nguyên liệu gì?',
                'Ban muon tranh nguyen lieu nao?': 'Bạn muốn tránh nguyên liệu nào?',
                'Ban muon gioi han bao nhieu kcal?': 'Bạn muốn giới hạn bao nhiêu kcal?',
                'Ban uu tien mon thit hay mon chay?': 'Bạn ưu tiên món thịt hay món chay?',
                'Neu ban muon chinh xac hon, hay gui ten mon cu the.': 'Nếu bạn muốn chính xác hơn, hãy gửi tên món cụ thể.',
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
                appendMessage('Xin chào, tôi có thể hỗ trợ gì cho bạn?');
            }
        };

        const setLoading = (loading) => {
            isSending = loading;
            sendBtn.disabled = loading;
            input.disabled = loading;
            sendBtn.textContent = loading ? 'Đang gửi...' : defaultSendLabel;
            input.placeholder = loading ? 'Đang xử lý...' : defaultInputPlaceholder;
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

                const contentType = response.headers.get('content-type') || '';
                const data = contentType.includes('application/json')
                    ? await response.json()
                    : { success: false, message: await response.text() };

                if (!response.ok || !data.success) {
                    throw new Error((data && data.message) || 'Không thể xử lý');
                }

                hideTyping();
                appendMessage(data.message || 'Đã nhận câu hỏi của bạn.');
                const suggestions = Array.isArray(data.suggestions) ? data.suggestions : [];
                if (suggestions.length > 0) {
                    appendMessage('Gợi ý tiếp: ' + suggestions.slice(0, 2).map(localizeChatText).join(' | '));
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
                const errMsg = (err && err.message) ? String(err.message) : 'Không xác định';
                appendMessage('Lỗi: ' + errMsg);
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

    window.AppUI = {
        showToast,
        setupShareButtons,
        setupCopyLinkButtons,
        setupReportReasonFields,
        setupModalToggles,
        setupConfirmActions,
        setupAjaxForms,
        setupChatWidget,
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
});






