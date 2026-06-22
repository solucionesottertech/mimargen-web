// Landing pública: comportamiento de UI (no inline porque el CSP es
// script-src 'self' sin 'unsafe-inline'). Cubre menú móvil, scroll-reveal,
// header sólido al hacer scroll y el modal "Ya soy cliente". La config
// dinámica ($baseDomain/$scheme y si hubo POST) llega por atributos data-*
// en #client-modal, no por JS inyectado.
(function () {
    'use strict';

    // ── Menú móvil ──────────────────────────────────────────────
    (function () {
        var btn = document.getElementById('mobile-menu-btn');
        var menu = document.getElementById('mobile-menu');
        if (!btn || !menu) return;
        btn.addEventListener('click', function () {
            menu.classList.toggle('hidden');
        });
        menu.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                menu.classList.add('hidden');
            });
        });
    })();

    // ── Scroll-reveal (.reveal → .visible) ──────────────────────
    (function () {
        var reveals = document.querySelectorAll('.reveal');
        if (!reveals.length) return;
        var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (reduce || !('IntersectionObserver' in window)) {
            reveals.forEach(function (el) { el.classList.add('visible'); });
            return;
        }
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });
        reveals.forEach(function (el) { observer.observe(el); });
    })();

    // ── Header sólido al hacer scroll ───────────────────────────
    (function () {
        var header = document.getElementById('site-header');
        if (!header) return;
        window.addEventListener('scroll', function () {
            if (window.pageYOffset > 50) {
                header.classList.add('header-solid');
            } else {
                header.classList.remove('header-solid');
            }
        }, { passive: true });
    })();

    // ── Modal "Ya soy cliente" (slug → subdominio del tenant) ───
    (function () {
        var modal = document.getElementById('client-modal');
        if (!modal) return;

        var baseDomain = modal.dataset.baseDomain || '';
        var scheme     = modal.dataset.scheme || 'https';
        var input      = document.getElementById('client-slug');
        var errBox     = document.getElementById('client-error');

        function openModal() {
            modal.setAttribute('aria-hidden', 'false');
            if (errBox) errBox.textContent = '';
            setTimeout(function () { if (input) input.focus(); }, 30);
            document.addEventListener('keydown', escClose);
        }
        function closeModal() {
            modal.setAttribute('aria-hidden', 'true');
            document.removeEventListener('keydown', escClose);
        }
        function escClose(e) { if (e.key === 'Escape') closeModal(); }

        function go() {
            var raw  = (input.value || '').trim().toLowerCase();
            var slug = raw.replace(/[^a-z0-9-]/g, '');
            if (!slug || !/^[a-z0-9](?:[a-z0-9-]{0,29}[a-z0-9])?$/.test(slug)) {
                if (errBox) errBox.textContent = 'Subdominio no válido. Usa solo letras, números y guiones.';
                return;
            }
            window.location.href = scheme + '://' + slug + '.' + baseDomain + '/';
        }

        document.querySelectorAll('[data-client-open]').forEach(function (el) {
            el.addEventListener('click', function (e) { e.preventDefault(); openModal(); });
        });
        var cancel = document.getElementById('client-cancel');
        if (cancel) cancel.addEventListener('click', closeModal);
        var goBtn = document.getElementById('client-go');
        if (goBtn) goBtn.addEventListener('click', go);
        if (input) {
            input.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') { e.preventDefault(); go(); }
            });
        }
        modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });

        // Tras un POST (éxito o error) hacemos scroll al formulario para ver el
        // resultado. El script va con defer, así que el DOM ya está parseado.
        if (modal.dataset.scrollContact === '1') {
            var c = document.getElementById('contacto');
            if (c) c.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    })();

    // ── Modal "Solicita tu prueba" (captura de lead vía AJAX) ───
    (function () {
        var modal = document.getElementById('trial-modal');
        if (!modal) return;

        var form    = document.getElementById('trial-form');
        var errBox  = document.getElementById('trial-error');
        var success = document.getElementById('trial-success');
        var submit  = document.getElementById('trial-submit');
        var first   = document.getElementById('trial-nombre');

        function openModal(e) {
            if (e) e.preventDefault();
            if (errBox) errBox.textContent = '';
            // Estado fresco: si venimos de un envío exitoso, restauramos el form.
            if (form)    { form.style.display = ''; form.reset(); }
            if (success) { success.style.display = 'none'; }
            modal.setAttribute('aria-hidden', 'false');
            setTimeout(function () { if (first) first.focus(); }, 30);
            document.addEventListener('keydown', escClose);
        }
        function closeModal() {
            modal.setAttribute('aria-hidden', 'true');
            document.removeEventListener('keydown', escClose);
        }
        function escClose(e) { if (e.key === 'Escape') closeModal(); }

        document.querySelectorAll('[data-trial-open]').forEach(function (el) {
            el.addEventListener('click', openModal);
        });
        var cancel = document.getElementById('trial-cancel');
        if (cancel) cancel.addEventListener('click', closeModal);
        var closeBtn = document.getElementById('trial-close');
        if (closeBtn) closeBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });

        if (!form) return;
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            if (errBox) errBox.textContent = '';

            // Validación mínima en cliente; el server revalida igual.
            var nombre  = (form.nombre.value  || '').trim();
            var empresa = (form.empresa.value || '').trim();
            var email   = (form.email.value   || '').trim();
            if (nombre.length < 2)  { if (errBox) errBox.textContent = 'Cuéntanos tu nombre.'; return; }
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { if (errBox) errBox.textContent = 'Necesitamos un correo válido.'; return; }
            if (empresa.length < 2) { if (errBox) errBox.textContent = 'Indica el nombre de tu empresa.'; return; }

            if (submit) { submit.disabled = true; submit.textContent = 'Enviando…'; }

            fetch(window.location.pathname || '/', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: new FormData(form)
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data && data.ok) {
                        form.style.display = 'none';
                        if (success) success.style.display = 'block';
                    } else if (errBox) {
                        errBox.textContent = (data && data.error) || 'No pudimos procesar tu solicitud.';
                    }
                })
                .catch(function () {
                    if (errBox) errBox.textContent = 'Error de conexión. Intenta de nuevo.';
                })
                .finally(function () {
                    if (submit) { submit.disabled = false; submit.textContent = 'Enviar solicitud'; }
                });
        });
    })();

    // ── Consentimiento de cookies (ley Chile / RGPD) ────────────
    // Opt-in explícito: ninguna cookie no esencial se instala hasta que el
    // usuario acepta. La decisión se guarda en una cookie propia (necesaria)
    // y se publica vía evento `cookieconsent` para que scripts de analítica /
    // marketing futuros esperen el permiso. Reabrir preferencias = retracto.
    (function () {
        var NAME = 'mm_consent';
        var VERSION = 1;
        var banner = document.getElementById('cookie-banner');
        var prefs  = document.getElementById('cookie-prefs');

        function readConsent() {
            var m = document.cookie.match(/(?:^|;\s*)mm_consent=([^;]+)/);
            if (!m) return null;
            try {
                var c = JSON.parse(decodeURIComponent(m[1]));
                return (c && c.v === VERSION) ? c : null;
            } catch (e) { return null; }
        }
        function writeConsent(analytics, marketing) {
            var c = { v: VERSION, a: analytics ? 1 : 0, m: marketing ? 1 : 0, t: Date.now() };
            var maxAge = 60 * 60 * 24 * 180; // 180 días
            document.cookie = NAME + '=' + encodeURIComponent(JSON.stringify(c)) +
                ';path=/;max-age=' + maxAge + ';SameSite=Lax' +
                (location.protocol === 'https:' ? ';Secure' : '');
            apply(c);
        }
        function apply(c) {
            window.__cookieConsent = c;
            // Hook para scripts de analítica/marketing que deben esperar permiso.
            try { document.dispatchEvent(new CustomEvent('cookieconsent', { detail: c })); } catch (e) {}
        }

        function showBanner() { if (banner) banner.setAttribute('data-show', '1'); }
        function hideBanner() { if (banner) banner.removeAttribute('data-show'); }

        function openPrefs(e) {
            if (e) e.preventDefault();
            if (!prefs) return;
            var c = readConsent();
            var a = document.getElementById('cookie-cat-analytics');
            var m = document.getElementById('cookie-cat-marketing');
            if (a) a.checked = !!(c && c.a);
            if (m) m.checked = !!(c && c.m);
            prefs.setAttribute('aria-hidden', 'false');
            document.addEventListener('keydown', escClose);
        }
        function closePrefs() {
            if (!prefs) return;
            prefs.setAttribute('aria-hidden', 'true');
            document.removeEventListener('keydown', escClose);
        }
        function escClose(e) { if (e.key === 'Escape') closePrefs(); }

        function acceptAll() { writeConsent(true, true);  hideBanner(); closePrefs(); }
        function rejectAll() { writeConsent(false, false); hideBanner(); closePrefs(); }
        function savePrefs() {
            var a = document.getElementById('cookie-cat-analytics');
            var m = document.getElementById('cookie-cat-marketing');
            writeConsent(a && a.checked, m && m.checked);
            hideBanner(); closePrefs();
        }

        // Estado inicial: si ya hay decisión, la aplicamos; si no, mostramos banner.
        var existing = readConsent();
        if (existing) { apply(existing); } else { showBanner(); }

        var ids = {
            'cookie-accept': acceptAll, 'cookie-reject': rejectAll, 'cookie-customize': openPrefs,
            'cookie-prefs-accept': acceptAll, 'cookie-prefs-reject': rejectAll, 'cookie-prefs-save': savePrefs
        };
        Object.keys(ids).forEach(function (id) {
            var el = document.getElementById(id);
            if (el) el.addEventListener('click', ids[id]);
        });
        if (prefs) prefs.addEventListener('click', function (e) { if (e.target === prefs) closePrefs(); });

        // Retracto: reabrir preferencias desde el footer u otros enlaces.
        document.querySelectorAll('[data-cookie-open]').forEach(function (el) {
            el.addEventListener('click', openPrefs);
        });
        window.openCookiePreferences = openPrefs;
    })();
})();
