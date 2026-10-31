<?php
require_once __DIR__ . '/../config.php';

/**
 * Helper client untuk Supabase REST & Auth API via cURL native
 */
function supabase_request($endpoint, $method = 'GET', $data = null) {
    if (!defined('SUPABASE_URL') || !defined('SUPABASE_KEY') || empty(SUPABASE_URL) || empty(SUPABASE_KEY)) {
        return ['error' => 'SUPABASE_URL atau SUPABASE_KEY belum diisi di config.php'];
    }

    // Pastikan URL tidak ada trailing slash dan endpoint diawali /
    $base = rtrim(SUPABASE_URL, '/');
    $ep   = (strpos($endpoint, '/') === 0) ? $endpoint : '/' . $endpoint;
    $url  = $base . $ep;
    $ch  = curl_init($url);

    $headers = [
        'apikey: ' . SUPABASE_KEY,
        'Authorization: Bearer ' . SUPABASE_KEY,
        'Content-Type: application/json',
    ];

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Kompatibel dengan SSL cert lokal Laragon/Windows

    if ($data !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        return ['error' => 'Gagal terhubung ke Supabase: ' . $curlError];
    }

    $json = json_decode($response, true);
    if ($httpCode >= 400) {
        $msg = $json['error_description']
            ?? $json['msg']
            ?? $json['message']
            ?? $json['error']
            ?? 'Terjadi kendala autentikasi Supabase';
        return ['error' => $msg, 'http_code' => $httpCode];
    }

    return $json ?: [];
}

/**
 * Login via Supabase Auth (email & password)
 */
function supabase_sign_in($email, $password) {
    return supabase_request('/auth/v1/token?grant_type=password', 'POST', [
        'email'    => $email,
        'password' => $password,
    ]);
}

/**
 * Register via Supabase Auth
 */
function supabase_sign_up($email, $password, $metadata = []) {
    return supabase_request('/auth/v1/signup', 'POST', [
        'email'    => $email,
        'password' => $password,
        'data'     => $metadata,
    ]);
}

/**
 * Get OAuth provider config (URL redirect ke halaman ini untuk callback)
 */
function supabase_get_oauth_provider($provider) {
    if (!defined('SUPABASE_URL') || !defined('SUPABASE_KEY') || empty(SUPABASE_URL) || empty(SUPABASE_KEY)) {
        return ['error' => 'SUPABASE_URL atau SUPABASE_KEY belum diisi di config.php'];
    }

    // Generate PKCE code verifier (43-128 chars base64url)
    $verifier = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    $_SESSION['oauth_code_verifier'] = $verifier;
    $_SESSION['oauth_provider']      = $provider;

    // Generate code challenge = base64url(sha256(verifier))
    $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

    // Callback URL di app kita
    $redirectUrl = rtrim(BASE_URL, '/') . '/pages/auth.php?provider=' . urlencode($provider);

    $base = rtrim(SUPABASE_URL, '/');
    $authUrl = $base . '/auth/v1/authorize?' . http_build_query([
        'provider'              => $provider,
        'redirect_to'           => $redirectUrl,
        'code_challenge'        => $challenge,
        'code_challenge_method' => 's256',
    ]);

    header('Location: ' . $authUrl);
    exit;
}

/**
 * Handle OAuth callback dari Supabase (tukar code dengan session via PKCE)
 */
function supabase_handle_oauth_callback() {
    if (!defined('SUPABASE_URL') || !defined('SUPABASE_KEY')) {
        return ['error' => 'Konfigurasi Supabase belum lengkap.'];
    }

    $code = $_GET['code'] ?? '';
    if (!$code) {
        return ['error' => 'Kode autentikasi tidak ditemukan dari respon login.'];
    }

    $verifier = $_SESSION['oauth_code_verifier'] ?? '';
    if (!$verifier) {
        return ['error' => 'Session verifier tidak ditemukan. Silakan ulangi proses login.'];
    }

    $url = rtrim(SUPABASE_URL, '/') . '/auth/v1/token?grant_type=pkce';
    $ch  = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: ' . SUPABASE_KEY,
        'Authorization: Bearer ' . SUPABASE_KEY,
        'Content-Type: application/json',
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'auth_code'     => $code,
        'code_verifier' => $verifier,
    ]));
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        return ['error' => 'Gagal koneksi ke Supabase: ' . $curlErr];
    }

    $data = json_decode($response, true) ?: [];
    if ($httpCode >= 400 || empty($data['access_token'])) {
        $msg = $data['error_description']
            ?? $data['msg']
            ?? $data['message']
            ?? $data['error']
            ?? 'Gagal menukarkan kode otentikasi.';
        return ['error' => $msg, 'http_code' => $httpCode];
    }

    // Bersihkan verifier setelah sukses digunakan
    unset($_SESSION['oauth_code_verifier']);
    return $data;
}
