<?php
/** Footer situs (dipakai index.php dan pages/bantuan.php). Butuh config.php sudah dimuat. */
$sfBase = rtrim(BASE_URL, '/');
$sfHelp = $sfBase . '/pages/bantuan.php?p=';
$sfRepo = 'https://github.com/LilyMarliana/generator-web-undangan';
$sfCols = [
    'Perusahaan' => [['Tentang kami', 'tentang'], ['Cara penggunaan', 'cara-penggunaan'], ['Hubungi kami', 'kontak']],
    'Bantuan'    => [['Paket Free & Pro', 'paket'], ['Pertanyaan umum', 'faq'], ['Panduan integrasi API', 'api']],
    'Kebijakan'  => [['Syarat & ketentuan', 'syarat'], ['Kebijakan privasi', 'privasi'], ['Kebijakan pembayaran', 'pembayaran']],
];
?>
<style>
.sf{position:relative;background:var(--cream,#fbf3e5);color:#5b5146;font:15px/1.65 'Work Sans',system-ui,sans-serif;overflow:hidden}
.sf a{color:inherit;text-decoration:none}
.sf-line{height:3px;background:linear-gradient(90deg,#a8503a,#e8b9a7,#d8c28b,#a8503a);background-size:300% 100%;animation:sfl 6s linear infinite}
@keyframes sfl{to{background-position:-300% 0}}
.sf-tick{overflow:hidden;border-bottom:1px solid var(--border,#e5dcca);padding:12px 0}
.sf-tk{display:flex;width:max-content;gap:40px;white-space:nowrap;font:italic 500 17px 'Fraunces',Georgia,serif;color:#8a8072;animation:sft 38s linear infinite}
.sf-tk b{color:#a8503a;font-style:normal}.sf-tick:hover .sf-tk{animation-play-state:paused}
@keyframes sft{to{transform:translateX(-50%)}}
.sf-in{max-width:1100px;margin:auto;padding:0 clamp(20px,5vw,44px)}
.sf-grid{display:grid;grid-template-columns:1.6fr repeat(3,1fr);gap:36px;padding:52px 0 40px}
.sf-brand img{height:30px;width:auto;transition:transform .4s}.sf-brand:hover img{transform:rotate(-3deg) scale(1.05)}
.sf-about{font-size:14px;margin:14px 0 18px;max-width:290px}
.sf-soc{display:flex;gap:10px}
.sf-soc a{width:40px;height:40px;border-radius:12px;border:1px solid var(--border,#e5dcca);display:grid;place-items:center;transition:.3s;background:#fff}
.sf-soc a:hover{background:#a8503a;color:#fff;transform:translateY(-4px) rotate(-6deg)}
.sf-soc svg{width:19px;height:19px;fill:currentColor}
.sf h4{font:600 13px 'Work Sans',sans-serif;letter-spacing:.1em;text-transform:uppercase;color:#2b241d;margin-bottom:14px}
.sf ul{list-style:none}
.sf-l a{position:relative;display:inline-block;padding:4px 0;transition:.25s}
.sf-l a:after{content:"";position:absolute;left:0;bottom:3px;height:1px;width:0;background:#a8503a;transition:.3s}
.sf-l a:hover{color:#a8503a;transform:translateX(5px)}.sf-l a:hover:after{width:100%}
.sf-bot{border-top:1px solid var(--border,#e5dcca);padding:20px 0 28px;display:flex;justify-content:space-between;gap:10px 24px;flex-wrap:wrap;font-size:13px;color:#8a8072}
.sf-heart{display:inline-block;color:#c8504a;animation:sfh 1.4s ease-in-out infinite}
@keyframes sfh{0%,40%,100%{transform:scale(1)}20%{transform:scale(1.3)}}
.sf-top{position:fixed;right:22px;bottom:22px;width:52px;height:52px;border:0;border-radius:50%;background:#2b241d;color:#fff;cursor:pointer;opacity:0;transform:translateY(20px);pointer-events:none;transition:.35s;z-index:60;box-shadow:0 10px 26px rgba(43,36,29,.3)}
.sf-top.on{opacity:1;transform:none;pointer-events:auto}.sf-top:hover{background:#a8503a}
.sf-top svg{position:absolute;inset:0;width:52px;height:52px;transform:rotate(-90deg)}
.sf-top circle{fill:none;stroke-width:3}.sf-top .b{stroke:#4a3e33}.sf-top .p{stroke:#e8b9a7;stroke-linecap:round;stroke-dasharray:151;stroke-dashoffset:151}
.sf-top span{position:relative;font-size:18px}
.sf-rv{opacity:0;transform:translateY(22px);transition:opacity .7s,transform .7s;transition-delay:var(--d,0s)}.sf-rv.in{opacity:1;transform:none}
@media(max-width:860px){.sf-grid{grid-template-columns:1fr 1fr}.sf-grid>div:first-child{grid-column:1/-1}}
@media(prefers-reduced-motion:reduce){.sf *{animation:none!important;transition:none!important}.sf-rv{opacity:1;transform:none}}
</style>

<footer class="sf" id="sf" role="contentinfo">
    <div class="sf-line"></div>
    <div class="sf-tick" aria-hidden="true"><div class="sf-tk" id="sfTk">
        <span>Every moment, beautifully invited</span><b>✦</b><span>Prompt undangan siap pakai</span><b>✦</b><span>RSVP &amp; ucapan tamu real-time</span><b>✦</b><span>17 palet warna, 13 gaya visual</span><b>✦</b><span>Pro hanya Rp18.000 / 30 hari</span><b>✦</b>
    </div></div>
    <div class="sf-in">
        <div class="sf-grid">
            <div class="sf-rv">
                <a class="sf-brand" href="<?= e($sfBase) ?>/index.php"><img src="<?= e($sfBase) ?>/assets/logo-wordmark.png" alt="Invitia" height="30"></a>
                <p class="sf-about">Susun prompt AI untuk website undangan pernikahan, lengkap dengan RSVP dan ucapan tamu yang tersambung otomatis.</p>
                <div class="sf-soc"><a href="<?= e($sfRepo) ?>" target="_blank" rel="noopener" aria-label="GitHub Invitia"><svg viewBox="0 0 24 24"><path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/></svg></a></div>
            </div>
            <?php $d = 0.08; foreach ($sfCols as $title => $links): ?>
            <div class="sf-rv" style="--d:<?= $d ?>s"><h4><?= e($title) ?></h4>
                <ul class="sf-l"><?php foreach ($links as [$label, $key]): ?><li><a href="<?= e($sfHelp . $key) ?>"><?= e($label) ?></a></li><?php endforeach; ?></ul>
            </div>
            <?php $d += 0.08; endforeach; ?>
        </div>
        <div class="sf-bot">
            <span>&copy; <?= date('Y') ?> Invitia. Seluruh hak dilindungi.</span>
            <span>Dibuat dengan <i class="sf-heart">♥</i> oleh tim Invitia</span>
        </div>
    </div>
    <button type="button" class="sf-top" id="sfTop" aria-label="Kembali ke atas"><svg viewBox="0 0 52 52"><circle class="b" cx="26" cy="26" r="24"/><circle class="p" cx="26" cy="26" r="24"/></svg><span>↑</span></button>
</footer>
<script>
(function(){
    var tk=document.getElementById('sfTk');if(tk)tk.innerHTML+=tk.innerHTML;
    var io=new IntersectionObserver(function(es){es.forEach(function(e){if(e.isIntersecting){e.target.classList.add('in');io.unobserve(e.target)}})},{threshold:.15});
    document.querySelectorAll('.sf-rv').forEach(function(el){io.observe(el)});
    var top=document.getElementById('sfTop'),ring=top.querySelector('.p');
    function sc(){var h=document.documentElement,max=h.scrollHeight-h.clientHeight,y=window.scrollY;
        top.classList.toggle('on',y>400);ring.style.strokeDashoffset=151-(max>0?151*Math.min(1,y/max):0)}
    addEventListener('scroll',sc,{passive:true});sc();
    top.addEventListener('click',function(){window.scrollTo({top:0,behavior:'smooth'})});
})();
</script>