/**
 * Microfinance LMS — shared front-end behaviour.
 * Vanilla JS only, no frameworks/build step.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        initSidebarToggle();
        initBootstrapValidation();
        initDeleteConfirmations();
        initAutoDismissAlerts();
    });

    /** Mobile sidebar open/close toggle. */
    function initSidebarToggle() {
        var toggleBtn = document.getElementById('sidebarToggle');
        var sidebar = document.querySelector('.mfs-sidebar');
        if (!toggleBtn || !sidebar) return;

        toggleBtn.addEventListener('click', function () {
            sidebar.classList.toggle('open');
        });

        document.addEventListener('click', function (evt) {
            if (window.innerWidth >= 992) return;
            if (!sidebar.contains(evt.target) && !toggleBtn.contains(evt.target)) {
                sidebar.classList.remove('open');
            }
        });
    }

    /** Bootstrap's standard client-side validation pattern. */
    function initBootstrapValidation() {
        var forms = document.querySelectorAll('.needs-validation');
        Array.prototype.slice.call(forms).forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }

    /** Any element with [data-confirm] shows a confirm() dialog before proceeding. */
    function initDeleteConfirmations() {
        document.addEventListener('click', function (evt) {
            var trigger = evt.target.closest('[data-confirm]');
            if (!trigger) return;

            var message = trigger.getAttribute('data-confirm') || 'Are you sure?';
            if (!window.confirm(message)) {
                evt.preventDefault();
                evt.stopPropagation();
            }
        });
    }

    /** Auto-dismiss flash alerts after 6 seconds. */
    function initAutoDismissAlerts() {
        var alerts = document.querySelectorAll('.alert-dismissible');
        alerts.forEach(function (alertEl) {
            setTimeout(function () {
                var bsAlert = bootstrap.Alert.getOrCreateInstance(alertEl);
                bsAlert.close();
            }, 6000);
        });
    }

    /** Live client-side EMI calculator used on the loan application form. */
    window.mfsCalculateEmi = function (principal, annualRatePct, months) {
        var monthlyRate = (annualRatePct / 100) / 12;
        if (monthlyRate === 0) {
            return principal / months;
        }
        var factor = Math.pow(1 + monthlyRate, months);
        return (principal * monthlyRate * factor) / (factor - 1);
    };
})();
