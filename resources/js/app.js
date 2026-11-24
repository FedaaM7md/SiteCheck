/*
 * SiteCheck — minimal progressive-enhancement behaviours. No framework:
 * the mobile menu, confirm-guarded destructive forms, the scan busy state
 * and the print trigger are all driven from data-* attributes so the app
 * can ship a strict Content-Security-Policy without inline handlers.
 * Copyright (c) 2026 Fedaa Mohamed. All rights reserved.
 */

function ready(fn) {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', fn);
    } else {
        fn();
    }
}

ready(function () {
    // Mobile menu toggle.
    document.querySelectorAll('[data-menu-toggle]').forEach(function (toggle) {
        toggle.addEventListener('click', function () {
            var open = toggle.getAttribute('aria-expanded') === 'true';
            toggle.setAttribute('aria-expanded', String(!open));

            document.querySelectorAll('[data-menu]').forEach(function (menu) {
                menu.hidden = open;
            });

            document.querySelectorAll('[data-menu-icon]').forEach(function (icon) {
                icon.hidden = icon.getAttribute('data-menu-icon') === (open ? 'open' : 'closed');
            });
        });
    });

    // Destructive forms confirm before submitting.
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!window.confirm(form.getAttribute('data-confirm'))) {
                event.preventDefault();
            }
        });
    });

    // Scan form: disable the submit button and reveal the busy state.
    document.querySelectorAll('form[data-scan]').forEach(function (form) {
        form.addEventListener('submit', function () {
            var button = form.querySelector('button[data-submit]');
            if (!button) {
                return;
            }

            button.disabled = true;
            button.setAttribute('aria-disabled', 'true');

            button.querySelectorAll('[data-idle]').forEach(function (el) {
                el.hidden = true;
            });
            button.querySelectorAll('[data-busy]').forEach(function (el) {
                el.hidden = false;
            });
        });
    });

    // Disclosure panels (e.g. the mobile history search).
    document.querySelectorAll('[data-disclosure-toggle]').forEach(function (toggle) {
        var panel = document.getElementById(toggle.getAttribute('aria-controls'));

        if (!panel) {
            return;
        }

        toggle.addEventListener('click', function () {
            var open = toggle.getAttribute('aria-expanded') === 'true';
            toggle.setAttribute('aria-expanded', String(!open));
            panel.classList.toggle('hidden', open);
        });
    });

    // Print trigger (print layout).
    document.querySelectorAll('[data-print]').forEach(function (el) {
        el.addEventListener('click', function () {
            window.print();
        });
    });
});
