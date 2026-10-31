/* pw-toggle.js — password visibility toggle for any input with a matching [data-pw-toggle] button. */
(function () {
    var EYE = '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"></path><circle cx="12" cy="12" r="3"></circle>';
    var EYE_OFF = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';

    function init() {
        document.querySelectorAll('[data-pw-toggle]').forEach(function (btn) {
            var input = document.getElementById(btn.dataset.pwToggle);
            if (!input) return;
            var icon = btn.querySelector('svg');
            if (icon) icon.innerHTML = EYE;

            btn.addEventListener('click', function () {
                var showing = input.type === 'password';
                input.type = showing ? 'text' : 'password';
                btn.setAttribute('aria-label', showing ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
                if (icon) icon.innerHTML = showing ? EYE_OFF : EYE;
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
