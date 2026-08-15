/**
 * Microfinance LMS — shared front-end behaviour.
 *
 * Vanilla JS, no framework and no build step. ES5 syntax throughout so the
 * app keeps working on older engines, and every feature is progressive:
 * if this file fails to load the pages stay usable as plain HTML forms.
 */
(function () {
    'use strict';

    var DESKTOP = 992;
    var THEME_KEY = 'mfs-theme';
    var COLLAPSE_KEY = 'mfs-sidebar-collapsed';

    /* The submit button the user actually pressed, so multi-button forms
       (approve / return / reject) spin the right one. */
    var lastSubmitter = null;

    ready(function () {
        initTheme();
        initSidebar();
        initTopbarShadow();
        initPasswordToggles();
        initCapsLockHints();
        initSegmentIndicator();
        initAuthShake();
        initFormValidation();
        initSubmitFeedback();
        initConfirmations();
        initToasts();
        initTableFilter();
        initTableSort();
        initAutoSubmit();
        initCopyButtons();
        initBackToTop();
        initShortcuts();
    });

    /* ---------------------------------------------------------------
       Helpers
       --------------------------------------------------------------- */
    function ready(fn) {
        if (document.readyState !== 'loading') {
            fn();
        } else {
            document.addEventListener('DOMContentLoaded', fn);
        }
    }

    function store(key, value) {
        try { localStorage.setItem(key, value); } catch (e) { /* private mode */ }
    }

    function read(key) {
        try { return localStorage.getItem(key); } catch (e) { return null; }
    }

    function closest(node, selector) {
        while (node && node.nodeType === 1) {
            if (node.matches ? node.matches(selector) : node.msMatchesSelector(selector)) return node;
            node = node.parentNode;
        }
        return null;
    }

    function each(list, fn) {
        for (var i = 0; i < list.length; i++) fn(list[i], i);
    }

    function debounce(fn, wait) {
        var timer = null;
        return function () {
            var args = arguments, self = this;
            clearTimeout(timer);
            timer = setTimeout(function () { fn.apply(self, args); }, wait);
        };
    }

    function emit(name, detail) {
        var evt;
        if (typeof CustomEvent === 'function') {
            evt = new CustomEvent(name, { detail: detail });
        } else {
            evt = document.createEvent('CustomEvent');
            evt.initCustomEvent(name, false, false, detail);
        }
        document.dispatchEvent(evt);
    }

    function isTypingTarget(el) {
        if (!el) return false;
        var tag = (el.tagName || '').toLowerCase();
        return tag === 'input' || tag === 'textarea' || tag === 'select' || el.isContentEditable;
    }

    /* ---------------------------------------------------------------
       Theme — light/dark, remembered per browser and synced across tabs.
       --------------------------------------------------------------- */
    function initTheme() {
        var root = document.documentElement;
        paintThemeIcons(root.getAttribute('data-mfs-theme') || 'light');

        document.addEventListener('click', function (evt) {
            var btn = closest(evt.target, '[data-theme-toggle]');
            if (!btn) return;

            applyTheme(root.getAttribute('data-mfs-theme') === 'dark' ? 'light' : 'dark', true);
        });

        // Another tab changed the theme: follow it.
        window.addEventListener('storage', function (evt) {
            if (evt.key === THEME_KEY && evt.newValue) applyTheme(evt.newValue, false);
        });

        function applyTheme(theme, persist) {
            root.setAttribute('data-mfs-theme', theme);
            root.setAttribute('data-bs-theme', theme);
            if (persist) store(THEME_KEY, theme);
            paintThemeIcons(theme);
            emit('mfs:themechange', { theme: theme });
        }
    }

    function paintThemeIcons(theme) {
        each(document.querySelectorAll('[data-theme-icon]'), function (icon) {
            icon.className = 'bi ' + (theme === 'dark' ? 'bi-sun' : 'bi-moon-stars');
        });
        each(document.querySelectorAll('[data-theme-toggle]'), function (btn) {
            btn.setAttribute('title', theme === 'dark' ? 'Switch to light theme' : 'Switch to dark theme');
        });
    }

    /* ---------------------------------------------------------------
       Sidebar — drawer on small screens, icon rail on large ones.
       --------------------------------------------------------------- */
    function initSidebar() {
        var toggleBtn = document.getElementById('sidebarToggle');
        var sidebar = document.getElementById('mfsSidebar');
        var shell = document.getElementById('mfsShell');
        var backdrop = document.getElementById('mfsBackdrop');
        if (!toggleBtn || !sidebar || !shell) return;

        if (read(COLLAPSE_KEY) === '1') shell.classList.add('is-collapsed');

        toggleBtn.addEventListener('click', function () {
            if (window.innerWidth < DESKTOP) {
                setDrawer(!sidebar.classList.contains('open'));
            } else {
                var collapsed = shell.classList.toggle('is-collapsed');
                store(COLLAPSE_KEY, collapsed ? '1' : '0');
                toggleBtn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            }
        });

        if (backdrop) backdrop.addEventListener('click', function () { setDrawer(false); });

        document.addEventListener('keydown', function (evt) {
            if ((evt.key === 'Escape' || evt.keyCode === 27) && sidebar.classList.contains('open')) {
                setDrawer(false);
                toggleBtn.focus();
            }
        });

        // Closing the drawer after navigation keeps small screens uncluttered.
        sidebar.addEventListener('click', function (evt) {
            if (window.innerWidth < DESKTOP && closest(evt.target, '.nav-link')) setDrawer(false);
        });

        window.addEventListener('resize', debounce(function () {
            if (window.innerWidth >= DESKTOP) setDrawer(false);
        }, 150));

        function setDrawer(open) {
            sidebar.classList.toggle('open', open);
            if (backdrop) backdrop.classList.toggle('is-visible', open);
            toggleBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
            document.body.style.overflow = open && window.innerWidth < DESKTOP ? 'hidden' : '';
            if (open) {
                var first = sidebar.querySelector('.nav-link');
                if (first && first.focus) first.focus();
            }
        }
    }

    /** Drop a shadow under the topbar once the page scrolls beneath it. */
    function initTopbarShadow() {
        var bar = document.querySelector('.mfs-topbar');
        if (!bar) return;

        var update = function () { bar.classList.toggle('is-stuck', window.pageYOffset > 4); };
        update();
        window.addEventListener('scroll', update, { passive: true });
    }

    /* ---------------------------------------------------------------
       Password reveal buttons: [data-toggle-password="<input id>"].
       --------------------------------------------------------------- */
    function initPasswordToggles() {
        document.addEventListener('click', function (evt) {
            var btn = closest(evt.target, '[data-toggle-password]');
            if (!btn) return;

            var input = document.getElementById(btn.getAttribute('data-toggle-password'));
            if (!input) return;

            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.setAttribute('aria-pressed', show ? 'true' : 'false');
            btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');

            var icon = btn.querySelector('i');
            if (icon) icon.className = 'bi ' + (show ? 'bi-eye-slash' : 'bi-eye');

            // Keep the caret where the user left it.
            var pos = input.value.length;
            input.focus();
            if (input.setSelectionRange) {
                try { input.setSelectionRange(pos, pos); } catch (e) { /* type=number etc. */ }
            }
        });
    }

    /* ---------------------------------------------------------------
       Caps Lock warning — applies to every password field on the page.
       --------------------------------------------------------------- */
    function initCapsLockHints() {
        var hint = document.querySelector('[data-caps-hint]');
        if (!hint) return;

        var fields = document.querySelectorAll('input[type="password"]');
        if (!fields.length) return;

        function update(evt) {
            if (!evt.getModifierState) return;
            hint.classList.toggle('is-visible', evt.getModifierState('CapsLock'));
        }

        each(fields, function (input) {
            input.addEventListener('keydown', update);
            input.addEventListener('keyup', update);
            input.addEventListener('blur', function () { hint.classList.remove('is-visible'); });
        });
    }

    /** Slide the segmented-control pill to whichever portal is active. */
    function initSegmentIndicator() {
        var segment = document.querySelector('.mfs-segment');
        if (!segment || segment.getAttribute('data-active')) return;

        var active = segment.querySelector('a.active');
        var links = segment.querySelectorAll('a');
        if (!active || links.length < 2) return;

        segment.setAttribute('data-active', active === links[0] ? 'employee' : 'customer');
    }

    /** Shake the sign-in card when the server rejected the credentials. */
    function initAuthShake() {
        var card = document.querySelector('.mfs-auth-card');
        if (!card || !card.querySelector('.alert-danger')) return;

        card.classList.add('is-rejected');
        var username = document.getElementById('username');
        if (username && username.focus) username.focus();
    }

    /* ---------------------------------------------------------------
       Bootstrap's standard client-side validation pattern.
       --------------------------------------------------------------- */
    function initFormValidation() {
        each(document.querySelectorAll('.needs-validation'), function (form) {
            form.addEventListener('submit', function (event) {
                // A button with formnovalidate (e.g. "Cancel") opts out.
                if (lastSubmitter && lastSubmitter.hasAttribute('formnovalidate')) return;

                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();

                    var invalid = form.querySelector(':invalid');
                    if (invalid) {
                        if (invalid.scrollIntoView) {
                            invalid.scrollIntoView({ block: 'center', behavior: 'smooth' });
                        }
                        if (invalid.focus) invalid.focus({ preventScroll: true });
                    }
                }
                form.classList.add('was-validated');
            }, false);
        });
    }

    /* ---------------------------------------------------------------
       Spinner on the pressed submit button so double posts are blocked.
       --------------------------------------------------------------- */
    function initSubmitFeedback() {
        // Remember which control triggered the submit.
        document.addEventListener('click', function (evt) {
            var btn = closest(evt.target, 'button[type="submit"], input[type="submit"], button:not([type])');
            lastSubmitter = btn || null;
        }, true);

        document.addEventListener('submit', function (evt) {
            var form = evt.target;
            if (evt.defaultPrevented) return;

            // GET filter forms navigate instantly; a spinner just flickers.
            var method = (form.getAttribute('method') || 'get').toLowerCase();
            if (method !== 'post') return;

            if (form.checkValidity && !form.checkValidity()) return;

            var btn = lastSubmitter && form.contains(lastSubmitter)
                ? lastSubmitter
                : form.querySelector('button[type="submit"], input[type="submit"]');
            if (!btn || btn.classList.contains('is-loading')) return;

            // Let the browser serialise the button's value before it goes inert.
            setTimeout(function () {
                btn.classList.add('is-loading');
                btn.setAttribute('aria-busy', 'true');
            }, 0);
        });
    }

    /* ---------------------------------------------------------------
       Any element with [data-confirm] asks before proceeding.
       --------------------------------------------------------------- */
    function initConfirmations() {
        document.addEventListener('click', function (evt) {
            var trigger = closest(evt.target, '[data-confirm]');
            if (!trigger) return;

            var message = trigger.getAttribute('data-confirm') || 'Are you sure?';
            if (!window.confirm(message)) {
                evt.preventDefault();
                evt.stopPropagation();
                lastSubmitter = null;
            }
        });
    }

    /* ---------------------------------------------------------------
       Flash alerts float up as dismissible toasts, then fade out.
       Falls back to the inline alert if anything here is unsupported.
       --------------------------------------------------------------- */
    function initToasts() {
        var alerts = document.querySelectorAll('.mfs-content > .alert-dismissible, .mfs-auth-card > .alert-dismissible');
        if (!alerts.length) return;

        var tray = document.createElement('div');
        tray.className = 'mfs-toasts';
        tray.setAttribute('role', 'status');
        tray.setAttribute('aria-live', 'polite');
        document.body.appendChild(tray);

        each(alerts, function (alertEl) {
            var type = 'info';
            each(['success', 'danger', 'warning', 'info'], function (name) {
                if (alertEl.classList.contains('alert-' + name)) type = name;
            });

            // Read the text without the close button's markup.
            var clone = alertEl.cloneNode(true);
            each(clone.querySelectorAll('.btn-close'), function (b) { b.parentNode.removeChild(b); });
            var text = (clone.textContent || '').replace(/\s+/g, ' ').replace(/^\s+|\s+$/g, '');
            if (!text) return;

            alertEl.parentNode.removeChild(alertEl);
            tray.appendChild(buildToast(text, type));
        });
    }

    function buildToast(text, type) {
        var toast = document.createElement('div');
        toast.className = 'mfs-toast type-' + type;

        var msg = document.createElement('div');
        msg.className = 'msg';
        msg.textContent = text;

        var close = document.createElement('button');
        close.className = 'close';
        close.type = 'button';
        close.setAttribute('aria-label', 'Dismiss');
        close.innerHTML = '&times;';

        toast.appendChild(msg);
        toast.appendChild(close);

        var timer = setTimeout(dismiss, type === 'danger' ? 9000 : 6000);
        close.addEventListener('click', function () { clearTimeout(timer); dismiss(); });
        toast.addEventListener('mouseenter', function () { clearTimeout(timer); });
        toast.addEventListener('mouseleave', function () { timer = setTimeout(dismiss, 3000); });

        function dismiss() {
            toast.classList.add('is-leaving');
            setTimeout(function () {
                if (toast.parentNode) toast.parentNode.removeChild(toast);
            }, 220);
        }

        return toast;
    }

    /* ---------------------------------------------------------------
       Instant client-side row filter:
         <input data-table-filter="#someTable">
       Filters only what is already on the page — server-side search
       still owns the full data set.
       --------------------------------------------------------------- */
    function initTableFilter() {
        each(document.querySelectorAll('[data-table-filter]'), function (input) {
            var table = document.querySelector(input.getAttribute('data-table-filter'));
            if (!table) return;

            var body = table.tBodies[0];
            if (!body) return;

            var status = document.createElement('div');
            status.className = 'form-text';
            status.setAttribute('aria-live', 'polite');
            if (input.parentNode) input.parentNode.appendChild(status);

            var run = debounce(function () {
                var needle = input.value.toLowerCase().replace(/^\s+|\s+$/g, '');
                var shown = 0;

                each(body.rows, function (row) {
                    if (row.getAttribute('data-no-filter') !== null) return;
                    var hit = !needle || (row.textContent || '').toLowerCase().indexOf(needle) !== -1;
                    row.classList.toggle('mfs-row-hidden', !hit);
                    if (hit) shown++;
                });

                status.textContent = needle ? shown + ' of ' + body.rows.length + ' rows match' : '';
            }, 120);

            input.addEventListener('input', run);
            input.addEventListener('search', run);
        });
    }

    /* ---------------------------------------------------------------
       Click-to-sort headers: <th data-sort> or <th data-sort="number">.
       --------------------------------------------------------------- */
    function initTableSort() {
        each(document.querySelectorAll('table.mfs-table'), function (table) {
            var headers = table.querySelectorAll('th[data-sort]');
            if (!headers.length || !table.tBodies[0]) return;

            each(headers, function (th, index) {
                th.setAttribute('tabindex', '0');
                th.setAttribute('role', 'button');

                var activate = function () { sortBy(table, th, index); };
                th.addEventListener('click', activate);
                th.addEventListener('keydown', function (evt) {
                    if (evt.key === 'Enter' || evt.key === ' ' || evt.keyCode === 13 || evt.keyCode === 32) {
                        evt.preventDefault();
                        activate();
                    }
                });
            });
        });
    }

    function sortBy(table, th, columnIndex) {
        var body = table.tBodies[0];
        var ascending = th.getAttribute('aria-sort') !== 'ascending';
        var type = th.getAttribute('data-sort') || 'auto';

        each(table.querySelectorAll('th[data-sort]'), function (other) { other.removeAttribute('aria-sort'); });
        th.setAttribute('aria-sort', ascending ? 'ascending' : 'descending');

        var rows = [];
        each(body.rows, function (row) { rows.push(row); });

        rows.sort(function (a, b) {
            var x = cellValue(a, columnIndex, type);
            var y = cellValue(b, columnIndex, type);
            if (x < y) return ascending ? -1 : 1;
            if (x > y) return ascending ? 1 : -1;
            return 0;
        });

        // Re-appending moves rows without destroying their event handlers.
        each(rows, function (row) { body.appendChild(row); });
    }

    function cellValue(row, index, type) {
        var cell = row.cells[index];
        if (!cell) return '';

        var raw = (cell.getAttribute('data-value') !== null)
            ? cell.getAttribute('data-value')
            : (cell.textContent || '');
        raw = raw.replace(/^\s+|\s+$/g, '');

        if (type === 'text') return raw.toLowerCase();

        // Strip currency symbols and thousands separators before comparing.
        var numeric = raw.replace(/[^0-9.\-]/g, '');
        if (type === 'number' || (numeric !== '' && /[0-9]/.test(raw) && !/[a-z]{3,}/i.test(raw))) {
            var parsed = parseFloat(numeric);
            if (!isNaN(parsed)) return parsed;
        }

        if (type === 'date') {
            var time = Date.parse(raw);
            if (!isNaN(time)) return time;
        }

        return raw.toLowerCase();
    }

    /* ---------------------------------------------------------------
       Filter forms submit themselves: selects immediately, text inputs
       after a pause. [data-auto-submit] on the form opts in.
       --------------------------------------------------------------- */
    function initAutoSubmit() {
        each(document.querySelectorAll('form[data-auto-submit]'), function (form) {
            each(form.querySelectorAll('select'), function (select) {
                select.addEventListener('change', function () { form.submit(); });
            });

            var delayed = debounce(function () { form.submit(); }, 550);
            each(form.querySelectorAll('input[type="search"], input[type="text"]'), function (input) {
                input.addEventListener('input', delayed);
            });
        });
    }

    /* ---------------------------------------------------------------
       [data-copy="text"] copies to the clipboard and confirms inline.
       --------------------------------------------------------------- */
    function initCopyButtons() {
        document.addEventListener('click', function (evt) {
            var btn = closest(evt.target, '[data-copy]');
            if (!btn) return;

            evt.preventDefault();
            var text = btn.getAttribute('data-copy') || btn.textContent || '';

            copyText(text, function (ok) {
                var original = btn.getAttribute('title') || '';
                btn.setAttribute('title', ok ? 'Copied' : 'Press Ctrl+C to copy');

                var icon = btn.querySelector('i');
                if (icon && ok) {
                    var was = icon.className;
                    icon.className = 'bi bi-check2';
                    setTimeout(function () { icon.className = was; }, 1400);
                }
                setTimeout(function () { btn.setAttribute('title', original); }, 1400);
            });
        });
    }

    function copyText(text, done) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function () { done(true); }, function () { done(false); });
            return;
        }

        // Fallback for non-secure contexts and older browsers.
        var helper = document.createElement('textarea');
        helper.value = text;
        helper.setAttribute('readonly', '');
        helper.style.position = 'fixed';
        helper.style.opacity = '0';
        document.body.appendChild(helper);
        helper.select();

        var ok = false;
        try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
        document.body.removeChild(helper);
        done(ok);
    }

    /* ---------------------------------------------------------------
       Back-to-top button, injected only on pages long enough to need it.
       --------------------------------------------------------------- */
    function initBackToTop() {
        var content = document.querySelector('.mfs-content');
        if (!content) return;

        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'mfs-icon-btn mfs-to-top';
        btn.setAttribute('aria-label', 'Back to top');
        btn.innerHTML = '<i class="bi bi-arrow-up" aria-hidden="true"></i>';
        document.body.appendChild(btn);

        btn.addEventListener('click', function () {
            try {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } catch (e) {
                window.scrollTo(0, 0);
            }
        });

        var update = function () { btn.classList.toggle('is-visible', window.pageYOffset > 400); };
        update();
        window.addEventListener('scroll', update, { passive: true });
    }

    /* ---------------------------------------------------------------
       Keyboard shortcuts: "/" focuses search, "\" toggles the sidebar.
       --------------------------------------------------------------- */
    function initShortcuts() {
        document.addEventListener('keydown', function (evt) {
            if (evt.ctrlKey || evt.metaKey || evt.altKey) return;
            if (isTypingTarget(evt.target)) return;

            if (evt.key === '/') {
                var search = document.querySelector('input[type="search"], input[name="search"], input[name="q"], [data-table-filter]');
                if (search) {
                    evt.preventDefault();
                    search.focus();
                    if (search.select) search.select();
                }
                return;
            }

            if (evt.key === '\\') {
                var toggle = document.getElementById('sidebarToggle');
                if (toggle) {
                    evt.preventDefault();
                    toggle.click();
                }
            }
        });
    }

    /* ---------------------------------------------------------------
       Live client-side EMI calculator used on the loan application form.
       Mirrors calculate_emi() in includes/loan_helpers.php.
       --------------------------------------------------------------- */
    window.mfsCalculateEmi = function (principal, annualRatePct, months) {
        principal = parseFloat(principal);
        annualRatePct = parseFloat(annualRatePct);
        months = parseInt(months, 10);

        if (!isFinite(principal) || !isFinite(annualRatePct) || !months || months <= 0) return 0;

        var monthlyRate = (annualRatePct / 100) / 12;
        if (monthlyRate === 0) return principal / months;

        var factor = Math.pow(1 + monthlyRate, months);
        return (principal * monthlyRate * factor) / (factor - 1);
    };
})();
