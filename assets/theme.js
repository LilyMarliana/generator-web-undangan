/* theme.js — light/dark toggle, shared by every page */
(function () {
    var SUN = '<circle cx="12" cy="12" r="4"/><line x1="12" y1="2" x2="12" y2="6"></line><line x1="12" y1="18" x2="12" y2="22"></line><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line><line x1="2" y1="12" x2="6" y2="12"></line><line x1="18" y1="12" x2="22" y2="12"></line><line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line>';
    var MOON = '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>';

    function current() {
        return document.documentElement.getAttribute('data-theme') || 'dark';
    }

    function init() {
        var btns = document.querySelectorAll('[data-theme-toggle]');
        var icons = document.querySelectorAll('[data-theme-icon]');

        function paint() {
            var isLight = current() === 'light';
            icons.forEach(function (icon) {
                icon.innerHTML = isLight ? MOON : SUN;
            });
            btns.forEach(function (btn) {
                btn.setAttribute('aria-label', isLight ? 'Ganti ke tema gelap' : 'Ganti ke tema terang');
            });
        }

        paint();

        btns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var next = current() === 'light' ? 'dark' : 'light';
                document.documentElement.setAttribute('data-theme', next);
                try { localStorage.setItem('theme', next); } catch (e) {}
                paint();
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
