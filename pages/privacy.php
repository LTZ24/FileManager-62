<?php
$isEmbed = isset($_GET['embed']) && (string)$_GET['embed'] === '1';
$effectiveDate = '28 Januari 2026';

$content = <<<HTML
<div class="legal-doc">
    <p class="legal-meta">File Manager SMK Negeri 62 Jakarta • Berlaku sejak: {$effectiveDate}</p>

    <div class="legal-note">
        <strong>Ringkasan:</strong> Kebijakan ini menjelaskan data apa yang diproses saat Anda menggunakan File Manager dan bagaimana data tersebut digunakan untuk kebutuhan operasional internal sekolah.
    </div>

    <ol class="legal-ol">
        <li>
            <h2>Ruang Lingkup</h2>
            <p>Privacy Policy ini berlaku untuk penggunaan File Manager oleh guru dan staff SMK Negeri 62 Jakarta. Aplikasi ini bukan layanan publik.</p>
        </li>

        <li>
            <h2>Informasi yang Diproses</h2>
            <p>Saat Anda menggunakan aplikasi, kami dapat memproses data berikut sesuai kebutuhan fitur:</p>
            <ul>
                <li><strong>Identitas akun Google</strong> (mis. nama dan email) untuk verifikasi akses dan sesi login.</li>
                <li><strong>Metadata aktivitas</strong> (mis. waktu akses, aksi upload/unduh) untuk audit keamanan dan troubleshooting.</li>
                <li><strong>Metadata file</strong> (mis. nama file, ukuran, waktu unggah, folder tujuan) untuk menampilkan daftar file dan riwayat.</li>
            </ul>
        </li>

        <li>
            <h2>Tujuan Pemrosesan</h2>
            <p>Data digunakan untuk:</p>
            <ul>
                <li>Mengautentikasi pengguna dan menerapkan pembatasan akses sesuai kebijakan sekolah.</li>
                <li>Menyediakan fitur manajemen file/folder (unggah, lihat, unduh, hapus, dsb.).</li>
                <li>Menjaga keamanan aplikasi, mencegah penyalahgunaan, dan melakukan audit bila diperlukan.</li>
                <li>Memberikan dukungan teknis dan perbaikan layanan.</li>
            </ul>
        </li>

        <li>
            <h2>Integrasi Google APIs</h2>
            <p>File Manager menggunakan layanan Google (misalnya Google Drive API dan autentikasi OAuth). Dengan login, Anda memberikan izin sesuai scope yang diminta aplikasi agar fitur dapat berjalan.</p>
            <p>Penggunaan Google APIs tunduk pada ketentuan Google, termasuk <a href="https://developers.google.com/terms" target="_blank" rel="noopener">Google APIs Terms of Service</a>.</p>
        </li>

        <li>
            <h2>Penyimpanan Data</h2>
            <p>File dan data operasional disimpan pada Google Drive yang terintegrasi dengan aplikasi (misalnya akun storage/Drive sekolah dan/atau Drive yang diberi izin). Lokasi penyimpanan ditentukan admin sekolah.</p>
        </li>

        <li>
            <h2>Berbagi Data</h2>
            <p>Kami tidak menjual data Anda. Data dapat diakses oleh admin sekolah/Tim TIK untuk kebutuhan operasional, keamanan, dan kepatuhan internal. Data juga diproses oleh Google sesuai layanan yang digunakan.</p>
        </li>

        <li>
            <h2>Keamanan</h2>
            <p>Kami menerapkan langkah-langkah keamanan yang wajar, termasuk penggunaan OAuth, kontrol sesi, dan pembatasan akses. Namun, keamanan juga bergantung pada keamanan akun Google dan perangkat Anda.</p>
        </li>

        <li>
            <h2>Retensi dan Penghapusan</h2>
            <p>Retensi file mengikuti kebijakan internal sekolah. Admin dapat menghapus atau memindahkan file sesuai kebutuhan. Jika Anda perlu penghapusan/perbaikan data tertentu, hubungi admin.</p>
        </li>

        <li>
            <h2>Hak Anda dan Kontrol Akses</h2>
            <p>Anda dapat mencabut akses aplikasi melalui pengaturan akun Google Anda di <a href="https://myaccount.google.com/permissions" target="_blank" rel="noopener">Google Account Permissions</a>. Setelah akses dicabut, Anda mungkin tidak dapat menggunakan aplikasi sampai izin diberikan kembali.</p>
        </li>

        <li>
            <h2>Cookie dan Penyimpanan Lokal</h2>
            <p>Aplikasi dapat menggunakan cookie sesi atau penyimpanan lokal (localStorage) untuk kebutuhan login, preferensi tampilan, dan stabilitas sesi. Kami tidak menggunakan cookie untuk iklan.</p>
        </li>

        <li>
            <h2>Perubahan Kebijakan</h2>
            <p>Kami dapat memperbarui Privacy Policy ini. Versi terbaru berlaku saat dipublikasikan. Penggunaan berkelanjutan setelah pembaruan berarti Anda menerima kebijakan yang diperbarui.</p>
        </li>

        <li>
            <h2>Kontak</h2>
            <p>Untuk pertanyaan terkait privasi atau permintaan akses/penghapusan, silakan hubungi admin File Manager/Tim TIK SMK Negeri 62 Jakarta.</p>
        </li>
    </ol>
</div>
HTML;

if ($isEmbed) {
        echo $content;
        exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Privacy Policy - File Manager</title>
    <meta name="theme-color" content="#50e3c2" />
    <link rel="icon" type="image/png" href="../assets/images/icons/icon-72x72.png" />
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,system-ui,sans-serif;background:#f5f7fa;color:#0f172a;line-height:1.65;padding:18px}
        .page{max-width:980px;margin:0 auto}
        .card{background:#fff;border-radius:14px;box-shadow:0 18px 50px rgba(0,0,0,.12);overflow:hidden}
        .topbar{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 16px;border-bottom:1px solid rgba(226,232,240,.95);background:linear-gradient(135deg, rgba(80,227,194,.14), rgba(255,255,255,0))}
        .topbar h1{font-size:1rem;font-weight:900}
        .back{display:inline-flex;align-items:center;gap:8px;border:1px solid rgba(148,163,184,.55);border-radius:10px;padding:10px 12px;text-decoration:none;color:#0f172a;font-weight:800;background:#fff}
        .back:hover{background:rgba(80,227,194,.10)}
        .body{padding:16px}
        .legal-doc{color:#0f172a;line-height:1.65}
        .legal-meta{margin:0;color:#64748b;font-size:.95rem}
        .legal-note{margin:14px 0 18px 0;padding:12px 14px;border-radius:12px;background:rgba(80,227,194,.12);border:1px solid rgba(80,227,194,.25);color:#0f172a;font-size:.95rem}
        .legal-ol{list-style:none;padding-left:0;margin:0;counter-reset:legal}
        .legal-ol>li{counter-increment:legal;padding:10px 0;border-bottom:1px dashed rgba(226,232,240,.9)}
        .legal-ol>li:last-child{border-bottom:none}
        .legal-ol>li>h2{margin:0 0 8px 0;font-size:1.05rem;font-weight:900}
        .legal-ol>li>h2::before{content:counter(legal) ". ";color:#0fbfa2}
        .legal-doc p{margin:0 0 10px 0;color:#334155}
        .legal-doc ul{margin:8px 0 12px 20px;color:#334155}
        .legal-doc li{margin:6px 0}
        .legal-doc a{color:#0fbfa2;text-decoration:none;font-weight:900}
        @media (max-width: 520px){body{padding:12px}.body{padding:14px}}
    </style>
</head>
<body>
    <div class="page">
        <div class="card">
            <div class="topbar">
                <h1>Privacy Policy</h1>
                <a class="back" href="../auth/login">&larr; Kembali ke Login</a>
            </div>
            <div class="body">
                <?php echo $content; ?>
            </div>
        </div>
    </div>
</body>
</html>
