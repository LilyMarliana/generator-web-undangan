<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../lib/auth.php';
require_login();

if (current_user()['role'] !== 'user') {
    redirect(BASE_URL . '/pages/user/dashboard.php');
}

$flash = flash('save_success');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generator - Wedding Website Generator</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Work+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <span class="header-eyebrow">Wedding Website Generator</span>
            <h1>Buat Prompt Undangan</h1>
            <p>Isi 5 langkah singkat, prompt siap pakai untuk AI pembuat website akan otomatis tersusun.</p>
        </div>
        <div class="content">
            <?php if ($flash): ?>
                <div class="success-msg"><?= e($flash) ?></div>
            <?php endif; ?>
            <div class="dash-nav">
                <a href="generator.php" class="active">Generator Baru</a>
                <a href="dashboard.php">Dashboard</a>
            </div>

            <div class="wizard-progress">
                <div class="wizard-progress-bar"><div class="wizard-progress-fill" id="wizardProgressFill"></div></div>
                <div class="wizard-progress-label" id="wizardProgressLabel">Langkah 1 dari 5 — Data Pernikahan</div>
            </div>

            <form id="weddingForm">
                <div class="wizard-step" data-step="1">
                    <h2 class="step-title">Data Pernikahan</h2>

                    <div class="form-group">
                        <label for="groomName">Nama Lengkap Pengantin Pria:</label>
                        <input type="text" id="groomName" name="groomName" required>
                    </div>

                    <div class="form-group">
                        <label for="brideName">Nama Lengkap Pengantin Wanita:</label>
                        <input type="text" id="brideName" name="brideName" required>
                    </div>

                    <div class="form-group">
                        <label for="akadDate">Tanggal Akad Nikah:</label>
                        <input type="date" id="akadDate" name="akadDate" required>
                    </div>

                    <div class="form-group">
                        <label for="akadTime">Waktu Akad Nikah:</label>
                        <input type="time" id="akadTime" name="akadTime" required>
                    </div>

                    <div class="form-group">
                        <label for="akadLocation">Lokasi Akad Nikah:</label>
                        <input type="text" id="akadLocation" name="akadLocation" required>
                    </div>

                    <div class="form-group">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <input type="checkbox" id="hasResepsi" name="hasResepsi">
                            <label for="hasResepsi" style="margin:0;">Resepsi berbeda dari Akad</label>
                        </div>
                    </div>

                    <div id="resepsiInput" class="conditional-input">
                        <div class="form-group">
                            <label for="resepsiDate">Tanggal Resepsi:</label>
                            <input type="date" id="resepsiDate" name="resepsiDate">
                        </div>
                        <div class="form-group">
                            <label for="resepsiTime">Waktu Resepsi:</label>
                            <input type="time" id="resepsiTime" name="resepsiTime">
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label for="receptionLocation">Lokasi Resepsi:</label>
                            <input type="text" id="receptionLocation" name="receptionLocation" placeholder="Masukkan lokasi resepsi">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="groomParents">Nama Orang Tua Pengantin Pria (Bpk. ... & Ibu ...):</label>
                        <input type="text" id="groomParents" name="groomParents" placeholder="Contoh: Bpk. H. Ahmad & Ibu Hj. Siti" required>
                    </div>

                    <div class="form-group" style="margin-bottom:0;">
                        <label for="brideParents">Nama Orang Tua Pengantin Wanita (Bpk. ... & Ibu ...):</label>
                        <input type="text" id="brideParents" name="brideParents" placeholder="Contoh: Bpk. H. Budi & Ibu Hj. Dewi" required>
                    </div>

                    <div class="wizard-nav">
                        <span></span>
                        <button type="button" class="btn-primary" onclick="goToStep(2)">Lanjut</button>
                    </div>
                </div>

                <div class="wizard-step" data-step="2" style="display:none;">
                    <h2 class="step-title">Tema &amp; Gaya Visual</h2>

                    <div class="form-group">
                        <label>Pilih Tema Warna Website Pernikahan:</label>
                        <div class="radio-group">
                            <div class="radio-item"><input type="radio" id="dustyRose" name="colorTheme" value="Dusty Rose"><label for="dustyRose">Dusty Rose</label></div>
                            <div class="radio-item"><input type="radio" id="sageGreen" name="colorTheme" value="Sage Green"><label for="sageGreen">Sage Green</label></div>
                            <div class="radio-item"><input type="radio" id="champagne" name="colorTheme" value="Champagne / Gold"><label for="champagne">Champagne / Gold</label></div>
                            <div class="radio-item"><input type="radio" id="ivory" name="colorTheme" value="Ivory / Off White"><label for="ivory">Ivory / Off White</label></div>
                            <div class="radio-item"><input type="radio" id="blushPink" name="colorTheme" value="Blush Pink"><label for="blushPink">Blush Pink</label></div>
                            <div class="radio-item"><input type="radio" id="lavender" name="colorTheme" value="Lavender / Lilac"><label for="lavender">Lavender / Lilac</label></div>
                            <div class="radio-item"><input type="radio" id="emerald" name="colorTheme" value="Emerald Green"><label for="emerald">Emerald Green</label></div>
                            <div class="radio-item"><input type="radio" id="navyBlue" name="colorTheme" value="Navy Blue"><label for="navyBlue">Navy Blue</label></div>
                            <div class="radio-item"><input type="radio" id="terracotta" name="colorTheme" value="Terracotta / Burnt Orange"><label for="terracotta">Terracotta / Burnt Orange</label></div>
                            <div class="radio-item"><input type="radio" id="maroon" name="colorTheme" value="Maroon / Wine Red"><label for="maroon">Maroon / Wine Red</label></div>
                            <div class="radio-item"><input type="radio" id="peach" name="colorTheme" value="Peach / Coral"><label for="peach">Peach / Coral</label></div>
                            <div class="radio-item"><input type="radio" id="blackWhite" name="colorTheme" value="Black & White"><label for="blackWhite">Black & White</label></div>
                            <div class="radio-item"><input type="radio" id="dustyBlue" name="colorTheme" value="Dusty Blue"><label for="dustyBlue">Dusty Blue</label></div>
                            <div class="radio-item"><input type="radio" id="mintGreen" name="colorTheme" value="Mint Green"><label for="mintGreen">Mint Green</label></div>
                            <div class="radio-item"><input type="radio" id="roseGold" name="colorTheme" value="Rose Gold"><label for="roseGold">Rose Gold</label></div>
                            <div class="radio-item"><input type="radio" id="charcoalGrey" name="colorTheme" value="Charcoal Grey"><label for="charcoalGrey">Charcoal Grey</label></div>
                            <div class="radio-item"><input type="radio" id="mustardYellow" name="colorTheme" value="Mustard Yellow"><label for="mustardYellow">Mustard Yellow</label></div>
                            <div class="radio-item"><input type="radio" id="teal" name="colorTheme" value="Teal"><label for="teal">Teal</label></div>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom:0;">
                        <label>Gaya Visual Website yang Diinginkan:</label>
                        <div class="radio-group">
                            <div class="radio-item"><input type="radio" id="minimalist" name="visualStyle" value="Minimalis elegan"><label for="minimalist">Minimalis elegan</label></div>
                            <div class="radio-item"><input type="radio" id="rustic" name="visualStyle" value="Rustic"><label for="rustic">Rustic</label></div>
                            <div class="radio-item"><input type="radio" id="floral" name="visualStyle" value="Floral / Botanical"><label for="floral">Floral / Botanical</label></div>
                            <div class="radio-item"><input type="radio" id="modernGlam" name="visualStyle" value="Modern glam"><label for="modernGlam">Modern glam</label></div>
                            <div class="radio-item"><input type="radio" id="classic" name="visualStyle" value="Classic / Traditional"><label for="classic">Classic / Traditional</label></div>
                            <div class="radio-item"><input type="radio" id="bohemian" name="visualStyle" value="Bohemian"><label for="bohemian">Bohemian</label></div>
                            <div class="radio-item"><input type="radio" id="cuteFun" name="visualStyle" value="Cute & Fun"><label for="cuteFun">Cute & Fun</label></div>
                            <div class="radio-item"><input type="radio" id="cultural" name="visualStyle" value="Kultural / Adat"><label for="cultural">Kultural / Adat</label></div>
                            <div class="radio-item"><input type="radio" id="darkMode" name="visualStyle" value="Dark mode / Moody"><label for="darkMode">Dark mode / Moody</label></div>
                            <div class="radio-item"><input type="radio" id="monochrome" name="visualStyle" value="Monokrom hitam putih"><label for="monochrome">Monokrom hitam putih</label></div>
                            <div class="radio-item"><input type="radio" id="vintage" name="visualStyle" value="Vintage / Retro"><label for="vintage">Vintage / Retro</label></div>
                            <div class="radio-item"><input type="radio" id="tropical" name="visualStyle" value="Tropical / Beach"><label for="tropical">Tropical / Beach</label></div>
                            <div class="radio-item"><input type="radio" id="fairytale" name="visualStyle" value="Fairytale / Whimsical"><label for="fairytale">Fairytale / Whimsical</label></div>
                            <div class="radio-item"><input type="radio" id="artDeco" name="visualStyle" value="Art Deco"><label for="artDeco">Art Deco</label></div>
                            <div class="radio-item"><input type="radio" id="watercolor" name="visualStyle" value="Watercolor / Pastel Dreamy"><label for="watercolor">Watercolor / Pastel Dreamy</label></div>
                        </div>
                    </div>

                    <div class="wizard-nav">
                        <button type="button" class="btn-outline" onclick="goToStep(1)">Kembali</button>
                        <button type="button" class="btn-primary" onclick="goToStep(3)">Lanjut</button>
                    </div>
                </div>

                <div class="wizard-step" data-step="3" style="display:none;">
                    <h2 class="step-title">Elemen Website &amp; Upload</h2>

                    <div class="form-group" style="margin-bottom:0;">
                        <label>Elemen yang Ingin Ditampilkan di Website (boleh pilih lebih dari satu):</label>
                        <div class="checkbox-group">
                            <div class="checkbox-item"><input type="checkbox" id="coverPembuka" name="elements" value="Cover/Sampul Pembuka"><label for="coverPembuka">Cover/Sampul Pembuka (nama tamu + tombol buka undangan)</label></div>
                            <div class="checkbox-item"><input type="checkbox" id="heroImage" name="elements" value="Hero image pasangan"><label for="heroImage">Hero image pasangan</label></div>
                            <div class="checkbox-item"><input type="checkbox" id="couplePhotos" name="elements" value="Foto mempelai pria & wanita"><label for="couplePhotos">Foto mempelai pria & wanita</label></div>
                            <div class="checkbox-item"><input type="checkbox" id="countdown" name="elements" value="Countdown timer"><label for="countdown">Countdown timer</label></div>
                            <div class="checkbox-item"><input type="checkbox" id="rsvp" name="elements" value="RSVP form"><label for="rsvp">RSVP form</label></div>
                            <div class="checkbox-item"><input type="checkbox" id="gallery" name="elements" value="Galeri foto pasangan"><label for="gallery">Galeri foto pasangan</label></div>
                            <div class="checkbox-item"><input type="checkbox" id="music" name="elements" value="Background music"><label for="music">Background music</label></div>
                            <div class="checkbox-item"><input type="checkbox" id="maps" name="elements" value="Google Maps lokasi"><label for="maps">Google Maps lokasi</label></div>
                            <div class="checkbox-item"><input type="checkbox" id="social" name="elements" value="Sosial media"><label for="social">Sosial media</label></div>
                            <div class="checkbox-item"><input type="checkbox" id="gift" name="elements" value="Wedding gift"><label for="gift">Wedding gift</label></div>
                            <div class="checkbox-item"><input type="checkbox" id="wishes" name="elements" value="Ucapan dari tamu"><label for="wishes">Ucapan dari tamu</label></div>
                            <div class="checkbox-item"><input type="checkbox" id="susunanAcara" name="elements" value="Susunan Acara"><label for="susunanAcara">Susunan Acara</label></div>
                            <div class="checkbox-item"><input type="checkbox" id="dressCode" name="elements" value="Dress Code"><label for="dressCode">Dress Code</label></div>
                            <div class="checkbox-item"><input type="checkbox" id="turutMengundang" name="elements" value="Turut Mengundang"><label for="turutMengundang">Turut Mengundang</label></div>
                        </div>

                        <div id="heroImageInput" class="conditional-input">
                            <label for="heroImageUpload">Foto Hero Image:</label>
                            <input type="file" id="heroImageUpload" name="heroImageUpload" accept="image/jpeg,image/png">
                        </div>

                        <div id="couplePhotosInput" class="conditional-input">
                            <label for="groomPhotoUpload" style="margin-bottom:15px;">Foto Pengantin Pria:</label>
                            <input type="file" id="groomPhotoUpload" name="groomPhotoUpload" accept="image/jpeg,image/png" style="margin-bottom:15px;">
                            <label for="bridePhotoUpload">Foto Pengantin Wanita:</label>
                            <input type="file" id="bridePhotoUpload" name="bridePhotoUpload" accept="image/jpeg,image/png">
                        </div>

                        <div id="galleryInput" class="conditional-input">
                            <label>Foto untuk Galeri (maks 6 foto):</label>
                            <input type="file" id="galleryUpload1" name="galleryUpload1" accept="image/jpeg,image/png" style="margin-bottom: 10px;">
                            <input type="file" id="galleryUpload2" name="galleryUpload2" accept="image/jpeg,image/png" style="margin-bottom: 10px;">
                            <input type="file" id="galleryUpload3" name="galleryUpload3" accept="image/jpeg,image/png" style="margin-bottom: 10px;">
                            <input type="file" id="galleryUpload4" name="galleryUpload4" accept="image/jpeg,image/png" style="margin-bottom: 10px;">
                            <input type="file" id="galleryUpload5" name="galleryUpload5" accept="image/jpeg,image/png" style="margin-bottom: 10px;">
                            <input type="file" id="galleryUpload6" name="galleryUpload6" accept="image/jpeg,image/png">
                        </div>

                        <div id="musicInput" class="conditional-input">
                            <label for="musicUpload">File Background Music:</label>
                            <input type="file" id="musicUpload" name="musicUpload" accept="audio/mpeg">
                        </div>

                        <div id="giftInput" class="conditional-input">
                            <label for="accountNumber">Nomor Rekening untuk Wedding Gift (satu per baris):</label>
                            <textarea id="accountNumber" name="accountNumber" rows="3" placeholder="Contoh:&#10;BCA 1234567890 a.n. John Doe&#10;Mandiri 1310018923401 a.n. Jane Doe"></textarea>
                        </div>

                        <div id="socialInput" class="conditional-input">
                            <label for="igUsername">Username Instagram:</label>
                            <input type="text" id="igUsername" name="igUsername" placeholder="Contoh: @fauzan" style="margin-bottom:15px;">
                            <label for="tiktokUsername">Username TikTok:</label>
                            <input type="text" id="tiktokUsername" name="tiktokUsername" placeholder="Contoh: @fauzan_">
                        </div>

                        <div id="mapsInput" class="conditional-input">
                            <label for="mapsLink">Link Google Maps Lokasi:</label>
                            <input type="text" id="mapsLink" name="mapsLink" placeholder="Tempel link Google Maps di sini">
                        </div>

                        <div id="susunanAcaraInput" class="conditional-input">
                            <label for="susunanAcaraList">Susunan Acara (satu per baris):</label>
                            <textarea id="susunanAcaraList" name="susunanAcaraList" rows="4" placeholder="Contoh:&#10;09.00 - 10.00 : Akad Nikah&#10;10.00 - 12.00 : Resepsi Sesi 1&#10;13.00 - 15.00 : Resepsi Sesi 2"></textarea>
                        </div>

                        <div id="dressCodeInput" class="conditional-input">
                            <label for="dressCodeText">Keterangan Dress Code:</label>
                            <textarea id="dressCodeText" name="dressCodeText" rows="2" placeholder="Contoh: Warna pastel (dusty blue, cream, sage green). Mohon hindari warna putih."></textarea>
                        </div>

                        <div id="turutMengundangInput" class="conditional-input">
                            <label for="turutMengundangList">Nama Keluarga yang Turut Mengundang (satu per baris):</label>
                            <textarea id="turutMengundangList" name="turutMengundangList" rows="3" placeholder="Contoh:&#10;Keluarga Besar Bapak H. Ahmad&#10;Keluarga Besar Bapak H. Budi"></textarea>
                        </div>

                        <div id="coverPembukaInput" class="conditional-input">
                            <p style="margin:0; font-size:0.9em; line-height:1.5;">ℹ️ Nama tamu di cover ini <strong>tidak diisi manual di sini</strong> — otomatis terbaca dari link yang kamu bagikan ke tiap tamu, contoh: <code>undangan.html?to=Budi</code>. Kalau linknya dibuka tanpa <code>?to=</code>, otomatis tampil "Tamu Undangan".</p>
                        </div>
                    </div>

                    <div class="wizard-nav">
                        <button type="button" class="btn-outline" onclick="goToStep(2)">Kembali</button>
                        <button type="button" class="btn-primary" onclick="goToStep(4)">Lanjut</button>
                    </div>
                </div>

                <div class="wizard-step" data-step="4" style="display:none;">
                    <h2 class="step-title">Cerita &amp; Ucapan</h2>

                    <div class="form-group">
                        <label for="openingQuote">Quote / Ayat Pembuka (opsional):</label>
                        <textarea id="openingQuote" name="openingQuote" placeholder="Contoh: Segala sesuatu yang pantas dimiliki akan datang pada waktu yang tepat. - ..."></textarea>
                    </div>

                    <div class="form-group">
                        <label for="loveStory">Cerita Cinta Kalian:</label>
                        <textarea id="loveStory" name="loveStory" placeholder="Ceritakan secara singkat bagaimana kalian bertemu atau perjalanan cinta kalian..." required></textarea>
                    </div>

                    <div class="form-group" style="margin-bottom:0;">
                        <label for="wishesIntro">Kata-kata Pembuka untuk Kolom Ucapan dari Tamu:</label>
                        <textarea id="wishesIntro" name="wishesIntro" placeholder="Contoh: Berikan ucapan terbaik untuk pasangan bahagia ini..." required></textarea>
                    </div>

                    <div class="wizard-nav">
                        <button type="button" class="btn-outline" onclick="goToStep(3)">Kembali</button>
                        <button type="button" class="btn-primary" onclick="goToStep(5)">Lanjut</button>
                    </div>
                </div>

                <div class="wizard-step" data-step="5" style="display:none;">
                    <h2 class="step-title">Review &amp; Generate</h2>
                    <p class="step-desc">Semua data sudah lengkap. Klik tombol di bawah untuk membuat prompt-nya — otomatis tersimpan ke Dashboard begitu selesai.</p>

                    <div class="wizard-nav">
                        <button type="button" class="btn-outline" onclick="goToStep(4)">Kembali</button>
                        <button type="button" class="btn-primary" onclick="generatePrompt()">Generate &amp; Simpan Prompt</button>
                    </div>
                </div>
            </form>

            <div id="resultContainer" class="result-container">
                <h3>Prompt Siap Pakai:</h3>
                <div id="resultText" class="result-text"></div>
                <button class="btn-success" onclick="copyToClipboard()">Copy Prompt</button>
            </div>
        </div>
    </div>

    <script>
    const stepLabels = {
        1: 'Data Pernikahan',
        2: 'Tema & Gaya Visual',
        3: 'Elemen Website & Upload',
        4: 'Cerita & Ucapan',
        5: 'Review & Generate'
    };
    let currentStep = 1;
    const totalSteps = 5;

    function showStep(n) {
        document.querySelectorAll('.wizard-step').forEach(step => {
            step.style.display = (parseInt(step.dataset.step, 10) === n) ? 'block' : 'none';
        });
        currentStep = n;
        const pct = Math.round((n / totalSteps) * 100);
        const fill = document.getElementById('wizardProgressFill');
        const label = document.getElementById('wizardProgressLabel');
        if (fill) fill.style.width = pct + '%';
        if (label) label.textContent = 'Langkah ' + n + ' dari ' + totalSteps + ' — ' + stepLabels[n];
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function validateStep(n) {
        const form = document.getElementById('weddingForm');
        const formData = new FormData(form);

        if (n === 1) {
            const groomName = formData.get('groomName');
            const brideName = formData.get('brideName');
            const akadDate = formData.get('akadDate');
            const akadTime = formData.get('akadTime');
            const akadLocation = formData.get('akadLocation');
            const groomParents = formData.get('groomParents');
            const brideParents = formData.get('brideParents');
            const hasResepsi = formData.get('hasResepsi') !== null;
            const resepsiDate = formData.get('resepsiDate');
            const resepsiTime = formData.get('resepsiTime');
            const receptionLocation = formData.get('receptionLocation');
            if (!groomName || !brideName || !akadDate || !akadTime || !akadLocation || !groomParents || !brideParents) {
                alert('Mohon lengkapi semua field yang wajib diisi di langkah ini!');
                return false;
            }
            if (hasResepsi && (!resepsiDate || !resepsiTime || !receptionLocation)) {
                alert('Lengkapi semua data resepsi!');
                return false;
            }
            return true;
        }

        if (n === 2) {
            const colorTheme = formData.get('colorTheme');
            const visualStyle = formData.get('visualStyle');
            if (!colorTheme || !visualStyle) {
                alert('Pilih tema warna dan gaya visual dulu ya!');
                return false;
            }
            return true;
        }

        if (n === 3) {
            const heroChecked   = document.getElementById('heroImage').checked;
            const coupleChecked = document.getElementById('couplePhotos').checked;
            const musicChecked  = document.getElementById('music').checked;
            const heroFileCheck  = document.getElementById('heroImageUpload').files[0];
            const groomFileCheck = document.getElementById('groomPhotoUpload').files[0];
            const brideFileCheck = document.getElementById('bridePhotoUpload').files[0];
            const musicFileCheck = document.getElementById('musicUpload').files[0];
            const missingFiles = [];
            if (heroChecked && !heroFileCheck) missingFiles.push('Foto Hero Image');
            if (coupleChecked && !groomFileCheck) missingFiles.push('Foto Pengantin Pria');
            if (coupleChecked && !brideFileCheck) missingFiles.push('Foto Pengantin Wanita');
            if (musicChecked && !musicFileCheck) missingFiles.push('File Background Music');
            if (missingFiles.length > 0) {
                alert(
                    'Kamu mencentang elemen yang butuh file, tapi file berikut belum dipilih:\n\n' +
                    missingFiles.map(f => '- ' + f).join('\n') +
                    '\n\nSilakan pilih file tersebut dulu sebelum lanjut.'
                );
                return false;
            }
            return true;
        }

        if (n === 4) {
            const loveStory = formData.get('loveStory');
            const wishesIntro = formData.get('wishesIntro');
            if (!loveStory || !wishesIntro) {
                alert('Mohon lengkapi cerita cinta dan kata pembuka ucapan!');
                return false;
            }
            return true;
        }

        return true;
    }

    function goToStep(n) {
        if (n > currentStep && !validateStep(currentStep)) return;
        showStep(n);
    }

    document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const item = this.closest('.checkbox-item');
            if (item) {
                if (this.checked) item.classList.add('checked');
                else item.classList.remove('checked');
            }
            handleConditionalInputs();
        });
    });

    document.querySelectorAll('input[type="radio"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.querySelectorAll('input[name="'+this.name+'"]').forEach(r => r.closest('.radio-item').classList.remove('selected'));
            this.closest('.radio-item').classList.add('selected');
        });
    });

    function handleConditionalInputs() {
        const map = {
            'heroImage': 'heroImageInput',
            'couplePhotos': 'couplePhotosInput',
            'gallery': 'galleryInput',
            'music': 'musicInput',
            'gift': 'giftInput',
            'social': 'socialInput',
            'maps': 'mapsInput',
            'hasResepsi': 'resepsiInput',
            'susunanAcara': 'susunanAcaraInput',
            'dressCode': 'dressCodeInput',
            'turutMengundang': 'turutMengundangInput',
            'coverPembuka': 'coverPembukaInput'
        };
        Object.keys(map).forEach(key => {
            const el = document.getElementById(key);
            const box = document.getElementById(map[key]);
            if (!el || !box) return; // safety: skip kalau elemen tidak ditemukan
            if (el.checked) {
                box.classList.add('show');
                box.style.display = 'block';
                box.style.opacity = '1';
            } else {
                box.classList.remove('show');
                box.style.display = 'none';
                box.style.opacity = '0';
            }
        });
    }

    function savePrompt(groomName, brideName) {
        const title = 'Prompt Undangan ' + groomName + ' & ' + brideName;
        const content = document.getElementById('resultText').textContent;
        const formData = new FormData();
        formData.append('csrf', <?= json_encode(csrf_token()) ?>);
        formData.append('title', title);
        formData.append('prompt', content);
        formData.append('groom_name', groomName);
        formData.append('bride_name', brideName);

        fetch('save.php', {method:'POST', body:formData})
            .then(r=>r.json()).then(d=> {
                if (d.success) {
                    window.location.href = 'dashboard.php';
                } else {
                    alert('Gagal menyimpan: ' + d.error);
                }
            }).catch(e=>alert('Error: ' + e));
    }

    async function generatePrompt() {
        const form = document.getElementById('weddingForm');
        const formData = new FormData(form);
        const groomName = formData.get('groomName');
        const brideName = formData.get('brideName');
        const akadDate = formData.get('akadDate');
        const akadTime = formData.get('akadTime');
        const resepsiDate = formData.get('resepsiDate');
        const resepsiTime = formData.get('resepsiTime');
        const akadLocation = formData.get('akadLocation');
        const receptionLocation = formData.get('receptionLocation');
        const groomParents = formData.get('groomParents');
        const brideParents = formData.get('brideParents');
        const colorTheme = formData.get('colorTheme');
        const visualStyle = formData.get('visualStyle');
        const loveStory = formData.get('loveStory');
        const wishesIntro = formData.get('wishesIntro');
        const accountNumber = formData.get('accountNumber');
        const igUsername = formData.get('igUsername');
        const tiktokUsername = formData.get('tiktokUsername');
        const mapsLink = formData.get('mapsLink');
        const openingQuote = formData.get('openingQuote');
        const hasResepsi = formData.get('hasResepsi') !== null;
        const susunanAcaraList = formData.get('susunanAcaraList');
        const dressCodeText = formData.get('dressCodeText');
        const turutMengundangList = formData.get('turutMengundangList');

        if (!groomName || !brideName || !akadDate || !akadTime ||
            !akadLocation || !groomParents || !brideParents ||
            !colorTheme || !visualStyle || !loveStory || !wishesIntro) {
            alert('Mohon lengkapi semua field yang wajib diisi!');
            return;
        }
        if (hasResepsi && (!resepsiDate || !resepsiTime || !receptionLocation)) {
            alert('Lengkapi semua data resepsi!');
            return;
        }

        // ====== VALIDASI FILE WAJIB SESUAI ELEMEN YANG DICENTANG ======
        // Mencegah bug "foto tidak lengkap" dengan menolak submit kalau
        // elemen terkait sudah dicentang tapi file belum dipilih.
        const heroChecked   = document.getElementById('heroImage').checked;
        const coupleChecked = document.getElementById('couplePhotos').checked;
        const musicChecked  = document.getElementById('music').checked;

        const heroFileCheck  = document.getElementById('heroImageUpload').files[0];
        const groomFileCheck = document.getElementById('groomPhotoUpload').files[0];
        const brideFileCheck = document.getElementById('bridePhotoUpload').files[0];
        const musicFileCheck = document.getElementById('musicUpload').files[0];

        const missingFiles = [];
        if (heroChecked && !heroFileCheck) missingFiles.push('Foto Hero Image');
        if (coupleChecked && !groomFileCheck) missingFiles.push('Foto Pengantin Pria');
        if (coupleChecked && !brideFileCheck) missingFiles.push('Foto Pengantin Wanita');
        if (musicChecked && !musicFileCheck) missingFiles.push('File Background Music');

        if (missingFiles.length > 0) {
            alert(
                'Kamu mencentang elemen yang butuh file, tapi file berikut belum dipilih:\n\n' +
                missingFiles.map(f => '- ' + f).join('\n') +
                '\n\nSilakan pilih file tersebut dulu sebelum generate.'
            );
            return;
        }
        // ====== AKHIR VALIDASI FILE ======

        let uploads = { heroImage: '', groomPhoto: '', bridePhoto: '', gallery: [], music: '' };
        const heroFile = document.getElementById('heroImageUpload').files[0];
        const groomPhotoFile = document.getElementById('groomPhotoUpload').files[0];
        const bridePhotoFile = document.getElementById('bridePhotoUpload').files[0];
        const musicFile = document.getElementById('musicUpload').files[0];
        const hasGallery = [1,2,3,4,5,6].some(i => document.getElementById('galleryUpload'+i).files[0]);

        if (heroFile || groomPhotoFile || bridePhotoFile || musicFile || hasGallery) {
            const uploadData = new FormData();
            if (heroFile) uploadData.append('heroImageUpload', heroFile);
            if (groomPhotoFile) uploadData.append('groomPhotoUpload', groomPhotoFile);
            if (bridePhotoFile) uploadData.append('bridePhotoUpload', bridePhotoFile);
            if (musicFile) uploadData.append('musicUpload', musicFile);
            for (let i=1;i<=6;i++) {
                const f = document.getElementById('galleryUpload'+i).files[0];
                if (f) uploadData.append('galleryUpload'+i, f);
            }
            try {
                const res = await fetch('upload.php', {method:'POST', body:uploadData});
                if (!res.ok) throw new Error();
                uploads = await res.json();

                // Tampilkan error per-field kalau ada yang gagal di server
                // (misal ukuran/format tidak sesuai), meski sudah lolos validasi di atas
                if (uploads.errors && Object.keys(uploads.errors).length > 0) {
                    const pesan = Object.entries(uploads.errors)
                        .map(([field, msg]) => '- ' + field + ': ' + msg)
                        .join('\n');
                    alert('Beberapa file gagal diupload:\n' + pesan);
                }
            } catch (e) {
                alert('Gagal mengupload file. Periksa format (gambar: JPG/PNG, musik: MP3) dan ukuran (maks 5MB gambar / 5MB musik).');
                return;
            }
        }

        const akadDateObj = new Date(akadDate);
        const resepsiDateObj = resepsiDate ? new Date(resepsiDate) : null;
        const options = { weekday:'long', year:'numeric', month:'long', day:'numeric' };
        const fmtAkad = akadDateObj.toLocaleDateString('id-ID', options);
        const fmtResepsi = resepsiDateObj ? resepsiDateObj.toLocaleDateString('id-ID', options) : '';

        const waktuResepsi = resepsiTime || akadTime;

        let detail = `- Tanggal Akad Nikah: ${fmtAkad}, Pukul ${akadTime}\n`;
        if (hasResepsi) {
            detail += `- Tanggal Resepsi: ${fmtResepsi}, Pukul ${waktuResepsi}\n`;
            detail += `- Lokasi Resepsi: ${receptionLocation}\n`;
        }
        detail += `- Lokasi Akad Nikah: ${akadLocation}\n`;

        const heroLayoutHints = [
            'Untuk hero section, gunakan layout full-screen dengan foto sebagai background dan overlay gradient gelap-transparan di atasnya, nama pengantin besar di tengah.',
            'Untuk hero section, gunakan layout split-screen: separuh layar foto, separuh layar teks nama & tanggal dengan warna solid dari tema.',
            'Untuk hero section, gunakan layout terpusat dengan foto berbentuk lingkaran/oval di tengah dikelilingi ornamen dekoratif sesuai Gaya Visual.',
            'Untuk hero section, gunakan layout asimetris: foto besar di satu sisi, teks nama pengantin muncul dengan animasi fade-in bertahap dari sisi lainnya.'
        ];
        const chosenHeroHint = heroLayoutHints[Math.floor(Math.random() * heroLayoutHints.length)];

        let prompt = `Saya ingin kamu bertindak sebagai seorang frontend developer profesional yang membuat website undangan pernikahan digital. Website ini harus memiliki desain yang modern, elegan, dan SEPENUHNYA RESPONSIF di tiga ukuran layar: desktop, tablet, dan mobile (gunakan CSS media query untuk ketiganya, bukan cuma dioptimalkan untuk mobile saja), dan TIDAK BOLEH terasa monoton atau generik.

Aturan dasar CSS yang WAJIB dipasang di awal stylesheet (sebelum style lainnya), untuk mencegah bug layout meluber ke luar layar:
- Reset global: '*, *::before, *::after { box-sizing: border-box; }' — semua elemen wajib pakai box-sizing: border-box, tanpa kecuali.
- Pasang 'html, body { overflow-x: hidden; width: 100%; max-width: 100vw; }' supaya TIDAK ADA elemen (termasuk teks, foto, ornamen dekoratif floating/parallax, atau container dengan padding/margin negatif) yang bisa menyebabkan horizontal scroll atau teks/konten terpotong di tepi kiri/kanan layar.
- Semua container/section pakai width: 100% dengan padding di dalamnya (bukan margin negatif atau posisi absolute yang lebar/offset-nya bisa mendorong konten keluar viewport).
- METODOLOGI WAJIB: tulis CSS dengan pendekatan MOBILE-FIRST. Artinya: semua style dasar/default (tanpa media query) HARUS ditulis untuk tampilan mobile (1 kolom, stacked/bertumpuk), lalu gunakan media query 'min-width' (BUKAN 'max-width') untuk menambahkan/mengubah layout ke tablet dan desktop. Pakai breakpoint yang KONSISTEN di seluruh file: mobile = default (di bawah 768px), tablet = '@media (min-width: 768px)', desktop = '@media (min-width: 1024px)'.
- WAJIB pastikan sebuah elemen yang tampil bertumpuk rapi di mobile TIDAK berubah jadi berantakan/tidak simetris/salah posisi saat lebar layar bertambah (tablet & desktop) — kalau suatu section memang didesain 1 kolom (misal Cover pembuka, Cerita Cinta, RSVP form), section itu HARUS tetap 1 kolom terpusat di SEMUA ukuran layar (mobile, tablet, desktop), jangan mendadak jadi sejajar/menyamping (side-by-side) hanya karena layar melebar, KECUALI section tersebut memang secara sengaja didesain 2 kolom di layar lebar (misal Foto mempelai pria & wanita, atau Galeri). Sebelum selesai, bayangkan/cek ulang tampilan di 3 lebar: 375px (mobile), 768px (tablet), 1440px (desktop) — pastikan tidak ada elemen yang berpindah posisi aneh atau berubah struktur secara tidak sengaja antar breakpoint tersebut.

Panduan visual & animasi (terapkan semua, ini bagian penting bukan sekadar tempelan):
- Scroll reveal: setiap section muncul dengan animasi fade + slide halus saat discroll ke dalam viewport (pakai IntersectionObserver), jangan statis langsung muncul.
- Tambahkan elemen dekoratif mengambang (floating, parallax ringan) yang relevan dengan Gaya Visual yang dipilih. Pilih motif yang paling sesuai dari daftar berikut (boleh kombinasikan 2-3 motif berbeda dalam satu halaman untuk variasi, tapi tetap konsisten dengan tema, JANGAN pakai motif yang tidak nyambung dengan Gaya Visual yang dipilih):
  - Hati kecil yang melayang pelan — cocok untuk gaya Cute & Fun, Fairytale/Whimsical, Bohemian, Watercolor/Pastel Dreamy
  - Kupu-kupu yang beterbangan lembut — cocok untuk Floral/Botanical, Fairytale/Whimsical, Tropical/Beach, Watercolor/Pastel Dreamy
  - Bunga & daun SVG tipis yang melayang — cocok untuk Floral/Botanical, Rustic, Bohemian, Tropical/Beach
  - Amplop/surat dengan ikon hati kecil — cocok untuk Classic/Traditional, Vintage/Retro, Cute & Fun
  - Pita/ribbon melengkung dan ornamen bunga kecil — cocok untuk Classic/Traditional, Vintage/Retro, Art Deco
  - Sparkle/bintang kecil berkelip halus — cocok untuk Modern glam, Dark mode/Moody, Art Deco
  - Cincin kawin minimalis atau garis geometris halus — cocok untuk Minimalis elegan, Modern glam, Monokrom hitam putih
  - Merpati/burung kecil terbang — cocok untuk Classic/Traditional, Kultural/Adat, Vintage/Retro
  Semua elemen dekoratif ini HARUS dianimasikan bergerak halus (melayang naik-turun, parallax saat scroll, atau fade in/out perlahan) menggunakan CSS animation/transform — jangan statis diam di tempat.
- Gunakan ornamen pemisah antar section (divider) yang match tema, bukan cuma garis lurus polos.
- Beri tekstur/pattern background yang samar (opacity rendah) sesuai tema warna & gaya visual supaya section tidak terasa kosong/putih polos.
- Tombol dan elemen interaktif harus punya hover/active state dengan transisi halus.
- ${chosenHeroHint}
- Konsistensi tipografi: gunakan alignment yang SAMA (pilih satu: rata kiri ATAU rata tengah) untuk semua heading dan body text di seluruh section — JANGAN campur rata kiri, kanan, dan tengah dalam section yang sama maupun antar section. Variasi visual antar section cukup didapat dari perbedaan background/layout container (misal section dengan foto besar vs section polos), BUKAN dari mengubah alignment teks.
- Untuk ukuran font heading, gunakan CSS clamp() (contoh: 'font-size: clamp(1.8rem, 5vw, 3.5rem)') supaya teks otomatis menyesuaikan secara halus dari mobile ke desktop, bukan lompatan ukuran patah-patah antar breakpoint.
- Foto WAJIB menyesuaikan ukuran layar (bukan ukuran fixed yang sama di semua device): gunakan CSS aspect-ratio tetap (misal 4:5 untuk foto potret, 1:1 untuk foto persegi) dikombinasikan dengan object-fit: cover, dan lebar foto diatur pakai clamp() (contoh: 'width: clamp(250px, 40vw, 500px)') supaya foto otomatis mengecil di layar mobile dan membesar proporsional di layar desktop/tablet, tanpa pernah melebihi container atau menyisakan foto yang terlalu kecil/kepotong aneh di ukuran layar manapun. JANGAN pakai nilai height/width fixed dalam px yang sama untuk semua breakpoint. WAJIB juga beri batas atas tinggi foto dengan 'max-height' memakai satuan vh (contoh: 'max-height: 60vh' untuk foto besar di section Mempelai/hero, 'max-height: 45vh' untuk foto galeri) supaya foto potret dengan rasio tinggi tidak pernah mendominasi/memenuhi seluruh layar — tinggi foto ditentukan oleh aspect-ratio + max-height ini, JANGAN biarkan foto meninggi mengikuti ukuran asli filenya.
- Untuk navbar: buat HANYA SATU elemen <header> atau <nav> tunggal yang diletakkan di luar/sebelum semua <section>, dengan position: sticky; top: 0. JANGAN membuat markup navbar berulang di dalam setiap section — navbar harus tetap di posisi yang sama sepanjang halaman di-scroll, bukan muncul berkali-kali di tengah konten.
- Untuk spacing/padding antar section dan di dalam container, gunakan CSS clamp() juga (contoh: 'padding: clamp(2rem, 6vw, 6rem) clamp(1rem, 5vw, 3rem)') supaya jarak antar elemen tidak terasa sempit di mobile maupun terlalu lega/kosong di desktop.
- Untuk layout yang berisi 2 foto berdampingan (misal foto mempelai pria & wanita, atau galeri foto), gunakan CSS Grid/Flexbox yang otomatis berubah dari beberapa kolom (desktop/tablet) menjadi 1 kolom bertumpuk (mobile) via media query — JANGAN biarkan foto berdampingan jadi gepeng/kecil-kecil di layar sempit.
- Batasi lebar maksimum konten (misal max-width: 1200px untuk container utama, max-width: 800px untuk blok teks seperti cerita cinta) dan tetap center secara horizontal (margin: 0 auto), supaya di layar desktop/monitor besar teks dan foto tidak melebar penuh ke tepi layar yang bikin sulit dibaca.
- Semua tombol dan elemen interaktif (termasuk tombol di form RSVP, tombol copy rekening, dsb) harus punya ukuran minimum yang nyaman disentuh di mobile (minimum tinggi/lebar sekitar 44px), jangan terlalu kecil sehingga sulit di-tap di layar sentuh.

Catatan penting: larangan "jangan tambah elemen di luar daftar" nanti HANYA berlaku untuk section/fitur fungsional (misal jangan bikin section galeri kalau tidak diminta). Elemen dekoratif/visual seperti animasi, ornamen, tekstur, efek hover tetap sangat dianjurkan dan bebas ditambahkan untuk memperkaya tampilan.

Detail Pernikahan:
- Pengantin Pria: ${groomName}
- Pengantin Wanita: ${brideName}
${detail}- Orang Tua Pengantin Pria: ${groomParents}
- Orang Tua Pengantin Wanita: ${brideParents}

Tema Desain:
- Warna: ${colorTheme}
- Gaya Visual: ${visualStyle}

Cerita Cinta:
"${loveStory}"

${openingQuote ? 'Quote / Ayat Pembuka:\n"' + openingQuote + '"\n\n' : ''}Website mencakup beberapa bagian utama seperti:`;

        const selectedElements = [];
        const checkboxes = document.querySelectorAll('input[name="elements"]:checked');
        checkboxes.forEach(cb => selectedElements.push(cb.value));

        if (selectedElements.length > 0) {
            prompt += '\n\nElemen yang harus ada (hanya centang ini, jangan tambah sendiri):';
            selectedElements.forEach(element => {
                prompt += '\n- ' + element;
                if (element === 'Hero image pasangan' && uploads.heroImage) {
                    prompt += ' (Gunakan gambar dari: ' + uploads.heroImage + ')';
                }
                if (element === 'Foto mempelai pria & wanita') {
                    const parts = [];
                    if (uploads.groomPhoto) parts.push('Foto pria: ' + uploads.groomPhoto);
                    if (uploads.bridePhoto) parts.push('Foto wanita: ' + uploads.bridePhoto);
                    if (parts.length) prompt += ' (' + parts.join(', ') + ')';
                    if (!uploads.heroImage && (uploads.groomPhoto || uploads.bridePhoto)) {
                        prompt += '\n  PENTING: tidak ada foto hero terpisah, jadi kedua foto ini (pria & wanita) WAJIB dipakai BERSAMA di hero section (contoh: side-by-side split-screen, atau dua foto bulat/oval bertumpuk dengan sedikit overlap) — JANGAN hanya menampilkan salah satu foto saja di hero.';
                    }
                }
                if (element === 'Galeri foto pasangan') {
                    const galleryLinks = uploads.gallery.filter(l => l);
                    if (galleryLinks.length > 0) {
                        prompt += ' (Gunakan foto dari: ' + galleryLinks.join(', ') + ')';
                    }
                    prompt += '\n  Tampilkan galeri ini sebagai slider/carousel yang bisa di-swipe (mobile) atau diklik panah navigasi (desktop), lengkap dengan indikator dot, bukan grid foto statis.';
                }
                if (element === 'Background music' && uploads.music) {
                    prompt += ' (Link musik: ' + uploads.music + ')';
                }
                if (element === 'Wedding gift' && accountNumber) {
                    const accounts = accountNumber.split('\n').map(l => l.trim()).filter(Boolean);
                    if (accounts.length === 1) {
                        prompt += ' (Rekening: ' + accounts[0] + ')';
                    } else if (accounts.length > 1) {
                        prompt += ' (Rekening:\n' + accounts.map(a => '  - ' + a).join('\n') + ')';
                    }
                }
                if (element === 'Sosial media') {
                    const socials = [];
                    if (igUsername) socials.push('Instagram: ' + igUsername);
                    if (tiktokUsername) socials.push('TikTok: ' + tiktokUsername);
                    if (socials.length) prompt += ' (' + socials.join(', ') + ')';
                }
                if (element === 'Ucapan dari tamu') {
                    prompt += ' (Kata pembuka: "' + wishesIntro + '")';
                }
                if (element === 'Google Maps lokasi' && mapsLink) {
                    prompt += ' (Link Google Maps: ' + mapsLink + ')';
                }
                if (element === 'Susunan Acara' && susunanAcaraList) {
                    const items = susunanAcaraList.split('\n').map(l => l.trim()).filter(Boolean);
                    if (items.length) prompt += ' (\n' + items.map(i => '  - ' + i).join('\n') + '\n)';
                }
                if (element === 'Dress Code' && dressCodeText) {
                    prompt += ' (' + dressCodeText.trim() + ')';
                }
                if (element === 'Turut Mengundang') {
                    const items = turutMengundangList ? turutMengundangList.split('\n').map(l => l.trim()).filter(Boolean) : [];
                    if (items.length) {
                        prompt += ' (\n' + items.map(i => '  - ' + i).join('\n') + '\n)';
                    }
                    prompt += '\n  Tampilkan daftar ini di bagian atas/pembuka undangan (sebelum atau berdekatan dengan nama orang tua pengantin), bukan sebagai section terpisah yang jauh dari salam pembuka.';
                }
                if (element === 'Cover/Sampul Pembuka') {
                    const hasMusicElement = selectedElements.includes('Background music');
                    prompt += '\n  Buat sebagai layer overlay TERPISAH yang menutupi SELURUH viewport (position: fixed; inset: 0; z-index tinggi, misal 999) dan tampil PALING PERTAMA saat halaman dimuat, menutupi seluruh konten section lain di baliknya. Desain cover ini berbentuk kartu vertikal/portrait di tengah layar (mirip tampilan kartu undangan fisik), berisi: nama kedua pengantin, tanggal pernikahan, ucapan singkat "Kepada Yth." diikuti nama tamu, dan tombol "Buka Undangan".'
                        + '\n  WAJIB: beri kartu ini lebar maksimum tetap (misal max-width: 420px) dan tetap di-center secara horizontal & vertikal (contoh: flexbox dengan justify-content/align-items: center pada overlay pembungkusnya) di SEMUA ukuran layar — mobile, tablet, MAUPUN desktop. Bentuknya harus SELALU berupa satu kartu vertikal tunggal yang sama persis proporsinya di layar manapun (hanya ukurannya yang boleh menyesuaikan lewat clamp(), bukan strukturnya) — JANGAN biarkan kartu ini berubah jadi melebar penuh, terbelah dua kolom, atau elemen di dalamnya (nama tamu, judul, tombol) jadi sejajar menyamping saat layar melebar ke tablet/desktop. Sisa area viewport di luar kartu (kiri-kanan pada layar lebar) cukup diisi background polos/blur/dekorasi tema, BUKAN konten tambahan.'
                        + '\n  Nama tamu WAJIB diambil otomatis dari parameter URL memakai `new URLSearchParams(window.location.search).get(\'to\')` (contoh link: ?to=Budi) — kalau parameter kosong/tidak ada, tampilkan fallback teks "Tamu Undangan".'
                        + '\n  Selama cover ini tampil, set `document.body.style.overflow = \'hidden\'` supaya user tidak bisa scroll melihat konten di baliknya. Saat tombol "Buka Undangan" diklik: sembunyikan cover dengan animasi fade-out/slide-up halus, lalu kembalikan `document.body.style.overflow = \'\'` supaya halaman utama bisa di-scroll normal.'
                        + (hasMusicElement
                            ? '\n  PENTING: panggil `audio.play()` untuk Background music PERSIS di dalam event handler klik tombol "Buka Undangan" ini (bukan di window.onload), karena browser modern memblokir autoplay audio tanpa interaksi user — klik tombol ini dipakai sebagai pemicu sah untuk memulai musik.'
                            : '');
                }
            });
        }
        prompt += '\n\nPastikan SEMUA elemen di atas dibuat, dan TIDAK membuat elemen/bagian lain yang tidak disebutkan di daftar "Elemen yang harus ada".\n\nGunakan HTML, CSS, dan JavaScript dalam satu file. Buat kode yang bersih, terorganisir, dan siap digunakan. Pastikan semua animasi dan efek visual berjalan dengan smooth dan tidak mengganggu performa website.';

        const baseUrl = <?= json_encode(rtrim(BASE_URL, '/')) ?>;
        const hasRSVP = selectedElements.includes('RSVP form');
        const hasWish = selectedElements.includes('Ucapan dari tamu');

        if (hasRSVP || hasWish) {
            prompt += '\n\nAPI Endpoints untuk RSVP dan Ucapan Tamu:\n- API Key: {{API_KEY}}\n- Base URL: ' + baseUrl + '/api.php\n\nFormat Response API:\n- Semua response berformat JSON: { "success": true/false, "data": [...], "error": "pesan error" }\n- Untuk mengecek apakah request berhasil, gunakan: if (response.ok && data.success)\n\nContoh kode JavaScript yang benar (WAJIB ikuti pola ini):\n  async function submitRSVP(payload) {\n      const response = await fetch(`${BASE_URL}?action=rsvp&api_key=${API_KEY}`, {\n          method: \'POST\',\n          headers: { \'Content-Type\': \'application/json\' },\n          body: JSON.stringify(payload)\n      });\n      const data = await response.json();\n      if (!response.ok || !data.success) {\n          showToast(\'Gagal mengirim: \' + (data.error || \'Terjadi kesalahan\'), true);\n          return;\n      }\n      showToast(\'Berhasil dikirim!\');\n      fetchWishes(); // Refresh list\n  }\n\nContoh fetch data (GET):\n  async function fetchWishes() {\n      const res = await fetch(`${BASE_URL}?action=wish&api_key=${API_KEY}`);\n      const data = await res.json();\n      if (res.ok && data.success && Array.isArray(data.data)) {\n          renderWishes(data.data);\n      }\n  }\n\n- Setelah POST RSVP/Wish berhasil, panggil ulang GET untuk refresh data\n- Gunakan escapeHtml() pada semua teks dari user (XSS protection)\n- Tampilkan toast error (bukan hanya toast sukses) jika gagal\n- Jangan gunakan localStorage fallback — semua data harus ke API\n- Pastikan API key yang digunakan adalah api_key yang benar (check console untuk debugging)';

            if (hasRSVP) {
                prompt += '\n\nPastikan website memiliki Form RSVP (input nama, pilihan kehadiran, jumlah tamu, catatan) dengan data disimpan via API.\nPENTING soal value kehadiran: value yang dikirim ke API pada field "attending" WAJIB persis salah satu dari tiga string berikut (huruf kecil semua, pakai underscore): hadir, tidak_hadir, belum_pasti — JANGAN kirim teks bebas seperti "Hadir" atau "Ya", karena API akan menolak/menganggap tidak valid selain 3 value itu. Kalau pakai <select>, isi atribut value dengan salah satu dari 3 string itu, dan tampilkan label yang mudah dibaca terpisah dari value-nya.\nTampilkan juga daftar RSVP yang sudah masuk (nama, status kehadiran, jumlah tamu, catatan) di halaman ini dalam bentuk list/card, diambil secara real-time dari GET action=rsvp. Panggil endpoint GET ini saat halaman pertama kali dimuat DAN setiap kali setelah submit RSVP berhasil, supaya daftar langsung ter-update tanpa refresh manual.';
            }
            if (hasWish) {
                prompt += '\nPastikan website memiliki Kolom Ucapan (input nama, textarea pesan) dan tampilan daftar ucapan yang diambil secara real-time dari GET endpoint action=wish. Panggil endpoint ini saat halaman pertama kali dimuat DAN setiap kali setelah submit ucapan berhasil, supaya daftar ucapan langsung ter-update tanpa refresh manual. Tampilkan dalam bentuk list/card yang rapi, urutkan dari yang terbaru.';
            }
        }

        document.getElementById('resultText').textContent = prompt;
        document.getElementById('resultContainer').style.display = 'block';
        document.getElementById('resultContainer').scrollIntoView({behavior:'smooth'});

        savePrompt(groomName, brideName);
    }

    function copyToClipboard() {
        const text = document.getElementById('resultText').textContent;
        navigator.clipboard.writeText(text).then(() => {
            const btn = document.querySelector('.btn-success');
            const original = btn.textContent;
            btn.textContent = 'Tersalin!';
            btn.style.background = '#20c997';
            setTimeout(() => {
                btn.textContent = original;
                btn.style.background = '#28a745';
            }, 2000);
        }).catch(() => alert('Gagal menyalin. Silakan salin manual.'));
    }

    document.addEventListener('DOMContentLoaded', function() {
        handleConditionalInputs();
        showStep(1);
    });
    </script>
</body>
</html>