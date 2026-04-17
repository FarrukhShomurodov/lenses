<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>

    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">

    <title>{{ $title ?? 'lenses' }}</title>

    <link rel="stylesheet" href="{{ asset('style.css') }}?v=modern-9">
    <script src="https://telegram.org/js/telegram-web-app.js?1"></script>

    <meta name="theme-color" content="#f1f3f7">

    @yield('head')
</head>

<body>

<div class="wrapper">
    @yield('content')
    @yield('nav')
</div>

{{-- UNIVERSAL CHAT_ID SCRIPT --}}
<script>
    const tg = window.Telegram.WebApp;
    const userId = tg?.initDataUnsafe?.user?.id;
    // const userId = '69621116';

    // if (!tg || !tg.initDataUnsafe || !tg.initData) {
    //     const botUsername = "lenses_bot";
    //     const webAppUrl = encodeURIComponent(window.location.href);
    //     window.location.href = `https://t.me/${botUsername}?start=webapp&startapp=${webAppUrl}`;
    // }

    if (userId) {
        const selectors = [
            'a[href*="webapp"]',
            '#menu-home',
            '#menu-favs',
            '#menu-profile',
            '#menu-profile_header',
            '#bottom-cart-btn',
            '#cart-btn'
        ];

        selectors.forEach(sel => {
            document.querySelectorAll(sel).forEach(link => {
                if (!link.href) return;
                const base = link.href.split("?")[0];
                const params = new URLSearchParams(link.href.split("?")[1]);
                params.set("chat_id", userId);
                link.href = base + "?" + params.toString();
            });
        });
    }
</script>

<script>
    if (userId) {
        sessionStorage.setItem('chat_id', userId);
    }

    const nativeFetch = window.fetch;
    window.fetch = function (url, options = {}) {
        options.headers = options.headers || {};
        const cid = sessionStorage.getItem('chat_id');
        if (cid) {
            options.headers['X-CHAT-ID'] = cid;
        }
        return nativeFetch(url, options);
    };
</script>

<script>
    (function () {
        const L = {
            formTitle: @json(__('webapp.access_form_title')),
            formSubtitle: @json(__('webapp.access_form_subtitle')),
            formCompany: @json(__('webapp.access_form_company')),
            formSubmit: @json(__('webapp.access_form_submit')),
            formError: @json(__('webapp.access_form_error')),
            pendingTitle: @json(__('webapp.access_pending_title')),
            pendingText: @json(__('webapp.access_pending_text')),
            close: @json(__('webapp.close')),
        };

        const escapeHtml = (s) => String(s ?? '').replace(/[&<>"']/g,
            c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

        function renderPending() {
            document.body.innerHTML = `
                <div class="access-overlay">
                    <div class="access-card">
                        <div class="access-card__icon">⏳</div>
                        <h2 class="access-card__title">${escapeHtml(L.pendingTitle)}</h2>
                        <p class="access-card__subtitle">${escapeHtml(L.pendingText)}</p>
                        <button type="button" class="access-form__submit" id="access-close-btn">
                            ${escapeHtml(L.close)}
                        </button>
                    </div>
                </div>`;
            document.getElementById('access-close-btn')
                ?.addEventListener('click', () => window.Telegram?.WebApp?.close?.());
        }

        function renderForm(prefill) {
            const company = escapeHtml(prefill?.company_name ?? '');
            document.body.innerHTML = `
                <div class="access-overlay">
                    <div class="access-card">
                        <div class="access-card__icon">🔒</div>
                        <h2 class="access-card__title">${escapeHtml(L.formTitle)}</h2>
                        <p class="access-card__subtitle">${escapeHtml(L.formSubtitle)}</p>
                        <form class="access-form" id="access-form">
                            <label class="access-form__field">
                                <span>${escapeHtml(L.formCompany)}</span>
                                <input type="text" name="company_name" value="${company}" required autocomplete="organization" maxlength="255">
                            </label>
                            <div class="access-form__error" id="access-form-error"></div>
                            <button type="submit" class="access-form__submit" id="access-form-submit">
                                ${escapeHtml(L.formSubmit)}
                            </button>
                        </form>
                    </div>
                </div>`;

            const form = document.getElementById('access-form');
            const submitBtn = document.getElementById('access-form-submit');
            const errorBox = document.getElementById('access-form-error');

            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const companyName = form.company_name.value.trim();
                if (!companyName) return;

                errorBox.textContent = '';
                submitBtn.disabled = true;

                const tgUser = window.Telegram?.WebApp?.initDataUnsafe?.user || {};

                try {
                    const resp = await fetch('/api/webapp/access-request', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            chat_id: userId,
                            company_name: companyName,
                            first_name: tgUser.first_name || prefill?.first_name || null,
                            uname: tgUser.username || null,
                            phone: prefill?.phone || null,
                        }),
                    });
                    const data = await resp.json();
                    if (data.success) {
                        window.Telegram?.WebApp?.HapticFeedback?.notificationOccurred?.('success');
                        renderPending();
                    } else {
                        errorBox.textContent = L.formError;
                        submitBtn.disabled = false;
                    }
                } catch (err) {
                    errorBox.textContent = L.formError;
                    submitBtn.disabled = false;
                }
            });
        }

        fetch('/api/webapp/check-user')
            .then(r => r.json())
            .then(res => {
                if (res.status === 'active' || res.active === true) return;
                if (res.status === 'pending') {
                    renderPending();
                    return;
                }
                renderForm(res.prefill || {});
            })
            .catch(() => {});
    })();
</script>

@yield('scripts')

</body>
</html>
