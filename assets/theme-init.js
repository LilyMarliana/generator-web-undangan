/* Apply saved theme before first paint so the page never flashes the wrong colors. */
(function () {
    try {
        var saved = localStorage.getItem('theme');
        if (saved === 'light' || saved === 'dark') {
            document.documentElement.setAttribute('data-theme', saved);
        }
    } catch (e) {}
})();
