<?php
$isEmbed = isset($_GET['embed']) && (string)$_GET['embed'] === '1';
$effectiveDate = '28 Januari 2026';

$content = <<<HTML
<div class="legal-doc">
    <p class="legal-meta">File Manager SMK Negeri 62 Jakarta • Berlaku sejak: {$effectiveDate}</p>

    <div class="legal-note">
        <strong>Penting:</strong> File Manager adalah aplikasi internal. Hanya guru dan staff SMK Negeri 62 Jakarta yang berwenang menggunakan aplikasi ini.
        Dengan menggunakan aplikasi, Anda menyetujui Terms of Service ini dan <a href="privacy" target="_blank" rel="noopener">Privacy Policy</a>.
    </div>

    <ol class="legal-ol">
        <li>
            <h2>Penerimaan Ketentuan</h2>
            <p>Dengan mengakses dan menggunakan File Manager ("Aplikasi"), Anda menyatakan telah membaca, memahami, dan menyetujui seluruh ketentuan di dokumen ini.</p>
        </li>

        <li>
            <h2>Tujuan dan Ruang Lingkup Layanan</h2>
            <p>Aplikasi menyediakan fitur untuk mengelola file dan folder (unggah, lihat, unduh, pindah, hapus) pada penyimpanan Google Drive yang terintegrasi dengan sistem sekolah. Penggunaan aplikasi dibatasi untuk kebutuhan pekerjaan/kegiatan internal sekolah.</p>
        </li>

        <li>
            <h2>Kelayakan Pengguna</h2>
            <p>Anda hanya dapat menggunakan aplikasi apabila Anda adalah guru atau staff yang memiliki akun Google yang diizinkan (misalnya akun domain sekolah/akun yang didaftarkan admin) dan memenuhi ketentuan akses yang berlaku di SMK Negeri 62 Jakarta.</p>
        </li>

        <li>
            <h2>Autentikasi dan Keamanan</h2>
            <p>Aplikasi menggunakan Google OAuth untuk autentikasi. Anda bertanggung jawab menjaga keamanan perangkat dan akun Google Anda, termasuk tidak membagikan akses kepada pihak yang tidak berwenang.</p>
            <ul>
                <li>Gunakan perangkat yang tepercaya dan lakukan logout setelah selesai.</li>
                <li>Laporkan segera jika Anda menduga ada akses tidak sah atau kebocoran akun.</li>
            </ul>
        </li>

        <li>
            <h2>Aturan Pengunggahan dan Konten</h2>
            <p>Anda hanya boleh mengunggah konten yang relevan untuk keperluan sekolah dan/atau pekerjaan, serta memiliki hak/izin untuk mengunggah dan membagikannya.</p>
            <ul>
                <li>Dilarang mengunggah konten yang melanggar hak cipta, data sensitif tanpa izin, atau materi yang bertentangan dengan aturan sekolah.</li>
                <li>Pastikan penamaan file/folder rapi dan sesuai kategori agar mudah dikelola.</li>
            </ul>
        </li>

        <li>
            <h2>Larangan Penggunaan</h2>
            <p>Anda dilarang menggunakan aplikasi untuk hal-hal berikut:</p>
            <ul>
                <li>Aktivitas ilegal, penipuan, phishing, spam, atau distribusi malware.</li>
                <li>Mencoba mengakses, mengubah, atau menghapus file/folder di luar kewenangan Anda.</li>
                <li>Mengganggu layanan, melakukan eksploitasi, scraping otomatis berlebihan, atau tindakan yang membebani sistem.</li>
            </ul>
        </li>

        <li>
            <h2>Ketersediaan Layanan</h2>
            <p>Kami berupaya menjaga ketersediaan aplikasi, namun layanan dapat terganggu karena pemeliharaan, perubahan kebijakan pihak ketiga (misalnya Google), atau gangguan jaringan. Kami dapat membatasi akses sementara untuk keamanan dan perbaikan.</p>
        </li>

        <li>
            <h2>Penghapusan, Retensi, dan Audit</h2>
            <p>Admin berwenang mengelola struktur folder, memindahkan, membatasi akses, atau menghapus konten sesuai kebijakan internal sekolah. Aktivitas tertentu dapat dicatat untuk keperluan audit keamanan dan operasional.</p>
        </li>

        <li>
            <h2>Pembatasan Tanggung Jawab</h2>
            <p>Aplikasi disediakan "sebagaimana adanya". Kami tidak bertanggung jawab atas kerugian yang timbul akibat penggunaan yang tidak sesuai ketentuan, kesalahan pengguna, atau gangguan layanan pihak ketiga. Anda tetap bertanggung jawab atas konten yang Anda unggah dan tindakan yang Anda lakukan.</p>
        </li>

        <li>
            <h2>Perubahan Ketentuan</h2>
            <p>Kami dapat memperbarui Terms of Service ini dari waktu ke waktu. Perubahan berlaku saat dipublikasikan. Dengan terus menggunakan aplikasi setelah perubahan, Anda dianggap menyetujui versi terbaru.</p>
        </li>

        <li>
            <h2>Kontak</h2>
            <p>Untuk pertanyaan, pelaporan insiden, atau permintaan terkait akses, silakan hubungi admin File Manager/Tim TIK SMK Negeri 62 Jakarta.</p>
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
    <title>Terms of Service - File Manager</title>
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
                <h1>Terms of Service</h1>
                <a class="back" href="../auth/login">&larr; Kembali ke Login</a>
            </div>
            <div class="body"><?php echo $content; ?></div>
        </div>
    </div>
</body>
</html>

