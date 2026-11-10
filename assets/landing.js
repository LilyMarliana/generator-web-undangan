/* Invitia — animasi landing page (vanilla JS, tanpa library) */
(function () {
    'use strict';

    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var finePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
    var $ = function (s, c) { return (c || document).querySelector(s); };
    var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };
    var clamp = function (v, a, b) { return Math.min(b, Math.max(a, v)); };
    var lerp = function (a, b, t) { return a + (b - a) * t; };

    /* ---------- 1. Preloader ---------- */
    var body = document.body;
    var loader = $('#loader');
    var fill = $('#loaderFill');
    var pct = $('#loaderPct');
    var started = false;

    function startPage() {
        if (started) return;
        started = true;
        loader.classList.add('done');
        body.classList.remove('is-loading');
        setTimeout(function () { initReveal(); }, 150);
    }

    (function runLoader() {
        if (reduce) { fill.style.width = '100%'; pct.textContent = '100'; startPage(); return; }
        var p = 0;
        var t = setInterval(function () {
            p = Math.min(100, p + 6 + Math.random() * 12);
            fill.style.width = p + '%';
            pct.textContent = Math.round(p);
            if (p >= 100) { clearInterval(t); setTimeout(startPage, 250); }
        }, 90);
        setTimeout(startPage, 3000); // pengaman
    })();

    /* ---------- 2. Reveal saat scroll + split judul hero ---------- */
    function splitWords(el) {
        var i = 0;
        (function walk(node) {
            Array.prototype.slice.call(node.childNodes).forEach(function (n) {
                if (n.nodeType === 3) {
                    var frag = document.createDocumentFragment();
                    n.textContent.split(/(\s+)/).forEach(function (part) {
                        if (!part) return;
                        if (/^\s+$/.test(part)) { frag.appendChild(document.createTextNode(' ')); return; }
                        var w = document.createElement('span');
                        w.className = 'w';
                        var s = document.createElement('span');
                        s.style.setProperty('--i', i++);
                        s.textContent = part;
                        w.appendChild(s);
                        frag.appendChild(w);
                    });
                    node.replaceChild(frag, n);
                } else if (n.nodeType === 1) {
                    walk(n);
                }
            });
        })(el);
    }
    $$('.split').forEach(splitWords);

    function initReveal() {
        var els = $$('.rv, .split');
        if (!('IntersectionObserver' in window) || reduce) {
            els.forEach(function (e) { e.classList.add('in'); });
            return;
        }
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (en) {
                if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); }
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -6% 0px' });
        els.forEach(function (e) { io.observe(e); });
    }

    /* ---------- 3. Topbar, progress scroll, counter ---------- */
    var topbar = $('#topbar');
    var progress = $('#progress span');
    var ticking = false;

    function onScroll() {
        var y = window.scrollY || window.pageYOffset;
        var max = document.documentElement.scrollHeight - window.innerHeight;
        topbar.classList.toggle('scrolled', y > 10);
        progress.style.transform = 'scaleX(' + (max > 0 ? y / max : 0) + ')';
        updateStory();
        ticking = false;
    }
    window.addEventListener('scroll', function () {
        if (!ticking) { ticking = true; requestAnimationFrame(onScroll); }
    }, { passive: true });
    window.addEventListener('resize', function () { onScroll(); sizePetals(); });

    // counter angka
    $$('[data-count]').forEach(function (el) {
        var target = parseInt(el.getAttribute('data-count'), 10) || 0;
        if (reduce || !('IntersectionObserver' in window)) { el.textContent = target; return; }
        var io = new IntersectionObserver(function (en) {
            if (!en[0].isIntersecting) return;
            io.disconnect();
            var t0 = performance.now();
            (function step(t) {
                var k = clamp((t - t0) / 1400, 0, 1);
                el.textContent = Math.round(target * (1 - Math.pow(1 - k, 3)));
                if (k < 1) requestAnimationFrame(step);
            })(t0);
        }, { threshold: 0.6 });
        io.observe(el);
    });

    /* ---------- 4. Kelopak bunga jatuh (canvas) ---------- */
    var canvas = $('#petals');
    var ctx = canvas.getContext('2d');
    var W = 0, H = 0, dpr = 1, petals = [], mouse = { x: -999, y: -999 };

    function sizePetals() {
        dpr = Math.min(window.devicePixelRatio || 1, 2);
        W = window.innerWidth; H = window.innerHeight;
        canvas.width = W * dpr; canvas.height = H * dpr;
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    }
    var COLORS = ['#e9b4aa', '#f2cdc4', '#f6dcd3', '#d99a8e', '#f0c9a8'];
    function newPetal(initial) {
        return {
            x: Math.random() * W,
            y: initial ? Math.random() * H : -20,
            s: 6 + Math.random() * 9,
            vy: 0.35 + Math.random() * 0.7,
            vx: 0,
            sway: Math.random() * Math.PI * 2,
            sw: 0.01 + Math.random() * 0.02,
            r: Math.random() * Math.PI * 2,
            vr: (Math.random() - 0.5) * 0.04,
            c: COLORS[(Math.random() * COLORS.length) | 0],
            a: 0.35 + Math.random() * 0.4
        };
    }
    function drawPetal(p) {
        ctx.save();
        ctx.translate(p.x, p.y);
        ctx.rotate(p.r);
        ctx.scale(1, 0.6 + 0.4 * Math.abs(Math.sin(p.sway)));
        ctx.globalAlpha = p.a;
        ctx.fillStyle = p.c;
        ctx.beginPath();
        ctx.moveTo(0, -p.s);
        ctx.bezierCurveTo(p.s, -p.s * 0.6, p.s * 0.8, p.s * 0.7, 0, p.s);
        ctx.bezierCurveTo(-p.s * 0.8, p.s * 0.7, -p.s, -p.s * 0.6, 0, -p.s);
        ctx.fill();
        ctx.restore();
    }
    function petalLoop() {
        ctx.clearRect(0, 0, W, H);
        for (var i = 0; i < petals.length; i++) {
            var p = petals[i];
            p.sway += p.sw;
            var dx = p.x - mouse.x, dy = p.y - mouse.y, d2 = dx * dx + dy * dy;
            if (d2 < 14000) { var f = (1 - d2 / 14000) * 1.4; p.vx += (dx / (Math.sqrt(d2) + 1)) * f; }
            p.vx *= 0.94;
            p.x += Math.sin(p.sway) * 0.7 + p.vx;
            p.y += p.vy;
            p.r += p.vr;
            if (p.y > H + 20 || p.x < -40 || p.x > W + 40) petals[i] = newPetal(false);
            drawPetal(p);
        }
        requestAnimationFrame(petalLoop);
    }
    if (!reduce) {
        sizePetals();
        var count = window.innerWidth < 700 ? 14 : 26;
        for (var n = 0; n < count; n++) petals.push(newPetal(true));
        requestAnimationFrame(petalLoop);
        window.addEventListener('pointermove', function (e) { mouse.x = e.clientX; mouse.y = e.clientY; }, { passive: true });
    } else {
        canvas.style.display = 'none';
    }

    /* ---------- 5. Kursor kustom, magnetic, spotlight, parallax chip ---------- */
    var cursor = $('#cursor');
    if (finePointer && !reduce) {
        document.documentElement.classList.add('cursor-on');
        var cx = 0, cy = 0, tx = 0, ty = 0;
        window.addEventListener('pointermove', function (e) { tx = e.clientX; ty = e.clientY; }, { passive: true });
        (function cursorLoop() {
            cx = lerp(cx, tx, 0.22); cy = lerp(cy, ty, 0.22);
            cursor.style.transform = 'translate(' + cx + 'px,' + cy + 'px)';
            requestAnimationFrame(cursorLoop);
        })();
        $$('a, button, summary').forEach(function (el) {
            el.addEventListener('pointerenter', function () { cursor.classList.add('hot'); });
            el.addEventListener('pointerleave', function () { cursor.classList.remove('hot'); });
        });

        // tombol "magnetic"
        $$('.magnetic').forEach(function (el) {
            el.addEventListener('pointermove', function (e) {
                var r = el.getBoundingClientRect();
                var x = (e.clientX - r.left - r.width / 2) * 0.25;
                var y = (e.clientY - r.top - r.height / 2) * 0.35;
                el.style.transform = 'translate(' + x + 'px,' + y + 'px)';
            });
            el.addEventListener('pointerleave', function () { el.style.transform = ''; });
        });

        // chip hero bergerak mengikuti mouse + ponsel miring
        var hero = $('.hero'), phone = $('#phone'), chips = $$('.chip');
        hero.addEventListener('pointermove', function (e) {
            var nx = (e.clientX / window.innerWidth - 0.5), ny = (e.clientY / window.innerHeight - 0.5);
            chips.forEach(function (c) {
                var k = parseFloat(c.getAttribute('data-px')) || 20;
                c.style.transform = 'translate(' + (nx * k) + 'px,' + (ny * k) + 'px)';
            });
            phone.style.transform = 'rotateY(' + (-10 + nx * 14) + 'deg) rotateX(' + (4 - ny * 10) + 'deg) rotate(2deg)';
        });
    }
    // spotlight kartu fitur (juga jalan di layar sentuh saat disentuh)
    $$('.spot').forEach(function (card) {
        card.addEventListener('pointermove', function (e) {
            var r = card.getBoundingClientRect();
            card.style.setProperty('--mx', (e.clientX - r.left) + 'px');
            card.style.setProperty('--my', (e.clientY - r.top) + 'px');
        });
    });

    /* ---------- 6. Countdown di mockup ponsel ---------- */
    var cdTarget = Date.now() + (96 * 24 * 3600 + 5 * 3600) * 1000;
    var cd = { d: $('[data-cd="d"]'), h: $('[data-cd="h"]'), m: $('[data-cd="m"]'), s: $('[data-cd="s"]') };
    function pad(n) { return (n < 10 ? '0' : '') + n; }
    function tickCd() {
        var diff = Math.max(0, cdTarget - Date.now()) / 1000;
        cd.d.textContent = pad(Math.floor(diff / 86400));
        cd.h.textContent = pad(Math.floor(diff % 86400 / 3600));
        cd.m.textContent = pad(Math.floor(diff % 3600 / 60));
        cd.s.textContent = pad(Math.floor(diff % 60));
    }
    tickCd();
    if (!reduce) setInterval(tickCd, 1000);

    /* ---------- 7. Story: amplop terbuka mengikuti scroll ---------- */
    var story = $('.story');
    var env = $('#env');
    var steps = $$('#storySteps li');
    var panels = $$('.panel');
    var bar = $('#storyBar');
    var cap = $('#storyCap');
    var capText = ['Data pasangan', 'Tema & gaya visual', 'Elemen & upload', 'Cerita & ucapan', 'Prompt siap pakai'];
    var curStep = -1;
    var mobileMode = function () { return window.innerWidth <= 960 || reduce; };

    function setStep(i) {
        if (i === curStep) return;
        curStep = i;
        steps.forEach(function (s, k) { s.classList.toggle('on', k === i); });
        panels.forEach(function (p, k) { p.classList.toggle('on', k === i); });
        if (cap && !mobileMode()) cap.textContent = capText[i];
    }
    function setEnv(open, lift) {
        env.style.setProperty('--open', open.toFixed(3));
        env.style.setProperty('--lift', lift.toFixed(3));
        env.classList.toggle('opened', open > 0.5);
    }
    function updateStory() {
        if (!story) return;
        if (mobileMode()) return; // mode mobile ditangani autoplay di bawah
        var r = story.getBoundingClientRect();
        var total = story.offsetHeight - window.innerHeight;
        var p = clamp(-r.top / total, 0, 1);
        var open = clamp(p / 0.1, 0, 1);
        var lift = clamp((p - 0.06) / 0.1, 0, 1);
        setEnv(open, lift);
        var sp = clamp((p - 0.14) / 0.84, 0, 0.9999);
        setStep(Math.floor(sp * 5));
        bar.style.transform = 'scaleX(' + p + ')';
        if (p < 0.1) cap.textContent = 'Gulir untuk membuka amplop';
    }
    // mobile / reduced motion: amplop terbuka, langkah berganti otomatis
    var autoplay = null;
    function setupMobile() {
        if (autoplay) { clearInterval(autoplay); autoplay = null; }
        if (!mobileMode()) { curStep = -1; updateStory(); return; }
        setEnv(1, 1);
        setStep(0);
        if (!reduce) {
            var i = 0;
            autoplay = setInterval(function () { i = (i + 1) % 5; setStep(i); }, 3200);
        }
    }
    setupMobile();
    window.addEventListener('resize', setupMobile);
    updateStory();

    /* ---------- 8. Playground tema ---------- */
    var tv = $('#tv');
    var ppColor = $('#ppColor'), ppStyle = $('#ppStyle');
    $$('#palPick button').forEach(function (b) {
        b.addEventListener('click', function () {
            $$('#palPick button').forEach(function (x) { x.classList.remove('on'); });
            b.classList.add('on');
            tv.style.setProperty('--tv-bg', b.dataset.bg);
            tv.style.setProperty('--tv-ac', b.dataset.ac);
            tv.style.setProperty('--tv-ink', b.dataset.ink);
            ppColor.textContent = b.dataset.name;
        });
    });
    $$('#styPick button').forEach(function (b, idx) {
        b.addEventListener('click', function () {
            $$('#styPick button').forEach(function (x) { x.classList.remove('on'); });
            b.classList.add('on');
            tv.dataset.sty = idx;
            tv.style.setProperty('--tv-font', b.dataset.font);
            ppStyle.textContent = b.dataset.name;
        });
    });
    tv.dataset.sty = 0;

    /* ---------- 9. Prompt demo: efek mengetik ---------- */
    var term = $('#termBody');
    var termCopy = $('#termCopy');
    var promptText = [
        '[h]# KONTEKS[/h]',
        'Buatkan website undangan pernikahan satu halaman',
        'yang responsif untuk pasangan [k]Marliana & Choi Seungcheol[/k].',
        '',
        '[h]# ACARA[/h]',
        'Akad: [k]Sabtu, 14 Februari 2027, 09.00[/k]',
        'Lokasi: [k]Gedung Serbaguna, Makassar[/k]',
        '',
        '[h]# DESAIN[/h]',
        'Tema warna: [k]Dusty Rose[/k]',
        'Gaya visual: [k]Minimalis elegan[/k]',
        '',
        '[h]# ELEMEN[/h]',
        'Cover pembuka, Countdown timer, Galeri foto,',
        'RSVP form, Ucapan dari tamu, Google Maps, Musik latar',
        '',
        '[h]# RSVP & UCAPAN (API)[/h]',
        'Endpoint: /api.php?action=rsvp&api_key=[k]{API_KEY}[/k]'
    ].join('\n');
    var plainPrompt = promptText.replace(/\[\/?[hk]\]/g, '');

    function renderTyped(n) {
        // n = jumlah karakter terlihat dari teks polos; sisipkan tag warna
        var out = '', shown = 0, i = 0, open = '';
        while (i < promptText.length && shown < n) {
            var m = /^\[(\/?)([hk])\]/.exec(promptText.slice(i));
            if (m) {
                open = m[1] ? '' : m[2];
                out += m[1] ? '</span>' : '<span class="' + m[2] + '">';
                i += m[0].length;
            } else {
                var ch = promptText[i++];
                out += ch === '<' ? '&lt;' : ch === '>' ? '&gt;' : ch === '&' ? '&amp;' : ch;
                shown++;
            }
        }
        if (open) out += '</span>';
        term.innerHTML = out + '<span class="caret"></span>';
    }
    (function setupTyping() {
        if (reduce || !('IntersectionObserver' in window)) { renderTyped(plainPrompt.length); return; }
        var io = new IntersectionObserver(function (en) {
            if (!en[0].isIntersecting) return;
            io.disconnect();
            var n = 0;
            (function type() {
                n += 2;
                renderTyped(n);
                if (n < plainPrompt.length) setTimeout(type, 22);
            })();
        }, { threshold: 0.4 });
        io.observe(term);
    })();
    termCopy.addEventListener('click', function () {
        var done = function () { termCopy.textContent = 'Tersalin!'; setTimeout(function () { termCopy.textContent = 'Salin'; }, 1600); };
        if (navigator.clipboard && navigator.clipboard.writeText) navigator.clipboard.writeText(plainPrompt).then(done, done);
        else done();
    });

    onScroll();
})();