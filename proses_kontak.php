<?php
/**
 * proses_kontak.php — VERSI DIPERBAIKI
 * Fix: CSRF divalidasi, rate limiting, pesan.txt diamankan
 */

session_start();

/* ============================================================
   1. CEK HTTP METHOD
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: kontak.php');
    exit;
}

/* ============================================================
   2. VALIDASI CSRF TOKEN
   (Fix bug: sebelumnya token dibuat tapi tidak pernah dicek)
   ============================================================ */
$token_input   = $_POST['csrf_token'] ?? '';
$token_session = $_SESSION['csrf_token'] ?? '';

if (empty($token_input) || empty($token_session) || !hash_equals($token_session, $token_input)) {
    $_SESSION['error'] = 'Permintaan tidak valid. Silakan coba lagi.';
    header('Location: kontak.php');
    exit;
}
// Hapus token setelah dipakai (one-time use)
unset($_SESSION['csrf_token']);

/* ============================================================
   3. RATE LIMITING SEDERHANA
   Maksimal 3 kiriman per 10 menit per IP
   (Fix: sebelumnya tidak ada proteksi spam)
   ============================================================ */
$ip         = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$ip_key     = 'ratelimit_' . md5($ip);
$limit      = 3;
$window     = 600; // 10 menit dalam detik
$now        = time();

$attempts = $_SESSION[$ip_key] ?? [];
// Hapus percobaan yang sudah kadaluarsa
$attempts = array_filter($attempts, fn($t) => ($now - $t) < $window);

if (count($attempts) >= $limit) {
    $_SESSION['error'] = 'Terlalu banyak percobaan. Silakan tunggu beberapa menit.';
    header('Location: kontak.php');
    exit;
}

$attempts[] = $now;
$_SESSION[$ip_key] = $attempts;

/* ============================================================
   4. AMBIL & SANITASI INPUT
   ============================================================ */
$nama     = trim(filter_input(INPUT_POST, 'nama',     FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$instansi = trim(filter_input(INPUT_POST, 'instansi', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$email    = trim(filter_input(INPUT_POST, 'email',    FILTER_SANITIZE_EMAIL) ?? '');
$telepon  = trim(filter_input(INPUT_POST, 'telepon',  FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$topik    = trim(filter_input(INPUT_POST, 'topik',    FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$pesan    = trim(filter_input(INPUT_POST, 'pesan',    FILTER_SANITIZE_SPECIAL_CHARS) ?? '');

/* ============================================================
   5. VALIDASI SERVER-SIDE
   ============================================================ */
$errors = [];

if (empty($nama)) {
    $errors['err_nama'] = 'Nama tidak boleh kosong.';
} elseif (strlen($nama) < 2) {
    $errors['err_nama'] = 'Nama minimal 2 karakter.';
} elseif (strlen($nama) > 100) {
    $errors['err_nama'] = 'Nama maksimal 100 karakter.';
} elseif (!preg_match('/^[\p{L}\s\'\-\.]+$/u', $nama)) {
    $errors['err_nama'] = 'Nama hanya boleh berisi huruf dan spasi.';
}

if (empty($email)) {
    $errors['err_email'] = 'Email tidak boleh kosong.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['err_email'] = 'Format email tidak valid.';
} elseif (strlen($email) > 150) {
    $errors['err_email'] = 'Email terlalu panjang (maks. 150 karakter).';
}

if (!empty($telepon)) {
    $telepon_bersih = preg_replace('/[\s\-\(\)\+]/', '', $telepon);
    if (!ctype_digit($telepon_bersih)) {
        $errors['err_telepon'] = 'Nomor telepon hanya boleh berisi angka.';
    } elseif (strlen($telepon_bersih) < 9 || strlen($telepon_bersih) > 15) {
        $errors['err_telepon'] = 'Nomor telepon tidak valid (9–15 digit).';
    }
}

if (empty($pesan)) {
    $errors['err_pesan'] = 'Pesan tidak boleh kosong.';
} elseif (strlen($pesan) < 10) {
    $errors['err_pesan'] = 'Pesan terlalu pendek (minimal 10 karakter).';
} elseif (strlen($pesan) > 500) {
    $errors['err_pesan'] = 'Pesan melebihi batas 500 karakter.';
}

/* ============================================================
   6. JIKA ADA ERROR → Kembalikan ke form
   ============================================================ */
if (!empty($errors)) {
    $_SESSION['error'] = 'Terdapat kesalahan pada form. Periksa kembali isian Anda.';
    $_SESSION['data']  = array_merge([
        'nama'     => $nama,
        'instansi' => $instansi,
        'email'    => $email,
        'telepon'  => $telepon,
        'topik'    => $topik,
        'pesan'    => $pesan,
    ], $errors);

    header('Location: kontak.php');
    exit;
}

/* ============================================================
   7. LABEL TOPIK
   ============================================================ */
$label_topik = [
    'web'        => 'Pengembangan Web App',
    'mobile'     => 'Pengembangan Mobile App',
    'api'        => 'Backend / REST API',
    'konsultasi' => 'Konsultasi Teknis',
    'kolaborasi' => 'Kolaborasi Proyek Kampus',
    'lain'       => 'Lainnya',
    ''           => 'Tidak Dipilih',
];
$topik_label = $label_topik[$topik] ?? 'Tidak Dipilih';

/* ============================================================
   8. SIMPAN DATA KE FILE
   FIX: Simpan di luar document root jika memungkinkan.
   Jika tidak bisa, minimal beri nama acak dan blokir via .htaccess.
   
   PENTING: Ganti path di bawah ini ke folder di luar public_html
   Contoh shared hosting: dirname(__DIR__) . '/pesan_masuk/pesan.txt'
   ============================================================ */
$dir_pesan  = __DIR__ . '/data_private'; // Buat folder ini dan isi .htaccess
$file_pesan = $dir_pesan . '/pesan.txt';

// Buat folder jika belum ada
if (!is_dir($dir_pesan)) {
    mkdir($dir_pesan, 0750, true);
    // Buat .htaccess untuk blokir akses langsung
    file_put_contents($dir_pesan . '/.htaccess', "Deny from all\n");
}

$waktu = date('Y-m-d H:i:s');

if (!file_exists($file_pesan)) {
    $header = "WAKTU\t\t\t\tNAMA\t\t\tINSTANSI\t\tEMAIL\t\t\t\tTELEPON\t\t\tTOPIK\t\t\t\tPESAN\n";
    $header .= str_repeat('=', 120) . "\n";
    file_put_contents($file_pesan, $header, FILE_APPEND | LOCK_EX);
}

$pesan_oneline = str_replace(["\r\n", "\r", "\n"], ' | ', $pesan);
$baris = implode("\t|\t", [
    $waktu,
    $nama,
    $instansi ?: '-',
    $email,
    $telepon  ?: '-',
    $topik_label,
    $pesan_oneline,
]) . "\n";

$simpan_ok = file_put_contents($file_pesan, $baris, FILE_APPEND | LOCK_EX);

/* ============================================================
   9. JIKA GAGAL SIMPAN
   ============================================================ */
if ($simpan_ok === false) {
    $_SESSION['error'] = 'Terjadi kesalahan teknis saat menyimpan pesan. Silakan coba lagi.';
    $_SESSION['data']  = compact('nama','instansi','email','telepon','topik','pesan');
    header('Location: kontak.php');
    exit;
}

/* ============================================================
   10. SUKSES — PRG Pattern
   ============================================================ */
$_SESSION['sukses'] = [
    'nama'   => $nama,
    'email'  => $email,
    'topik'  => $topik_label,
    'waktu'  => date('d M Y, H:i') . ' WIB',
];

header('Location: kontak.php');
exit;