# FileManager SMKN62 v3.0

Aplikasi manajemen file internal untuk SMK Negeri 62 Jakarta. Terintegrasi dengan Google Drive & Google Sheets untuk penyimpanan data berbasis cloud.

> **Repository**: [github.com/LTZ24/FileManager-62](https://github.com/LTZ24/FileManager-62)

---

## Fitur Utama

### Dashboard
- Statistik real-time (jumlah file, link, form)
- Data terbaru (recent uploads) dengan lazy loading AJAX
- Akses cepat ke 4 kategori bidang sekolah
- Skeleton UI saat loading

### Manajemen Links
- CRUD link penting per kategori bidang
- Integrasi Google Sheets sebagai database
- Filter berdasarkan kategori
- Pagination (10 / 25 / 50 / 100 / All)

### Manajemen Forms
- CRUD link Google Forms per kategori bidang
- Integrasi Google Sheets sebagai database
- Filter berdasarkan kategori
- Pagination

### File Manager
- Upload file ke Google Drive (drag & drop / klik)
- Upload manager dengan progress bar & antrian
- Download & hapus file dari Google Drive
- Preview file langsung di browser
- Pagination daftar file

### Kategori Bidang
Setiap bidang memiliki halaman khusus dengan 3 tab (Links, Forms, Files):
- **Kesiswaan**
- **Kurikulum**
- **Sapras & Humas**
- **Tata Usaha**

### Autentikasi & Keamanan
- Login dengan username & password (MySQL + bcrypt)
- Role-based access: `admin` dan `staff/guru`
- Session management dengan auto-logout (30 menit inaktif)
- Warning popup 2 menit sebelum logout
- CSRF protection pada semua form
- Rate limiting login (8 attempt / 60 detik)
- HMAC token + admin password gate untuk setup page
- Clean URL (tanpa ekstensi `.php`) via `.htaccess` rewrite

### User Management (Admin)
- Buat akun baru (staff/guru)
- Edit email & password user
- Aktifkan / nonaktifkan akun
- Hapus akun (dengan konfirmasi password admin)

### Google Drive Storage Setup
- Halaman setup Google OAuth (`/pages/setup-google`)
- Koneksi Google Drive untuk upload file
- Service Account untuk baca data Sheets
- Konfigurasi folder tujuan upload

### Progressive Web App (PWA)
- Installable di desktop & mobile
- Offline fallback page
- Service worker dengan cache strategy (network-first untuk halaman, cache-first untuk asset)
- App manifest dengan ikon lengkap

---

## Tech Stack

| Layer | Teknologi |
|---|---|
| **Backend** | PHP 8.2 |
| **Database** | MySQL / MariaDB (autentikasi & konfigurasi) |
| **Data Storage** | Google Sheets API v4 (links, forms) |
| **File Storage** | Google Drive API v3 (upload, download, delete) |
| **Auth** | Bcrypt + PHP Session (login), Google OAuth 2.0 (Drive setup) |
| **Frontend** | HTML5, CSS3, Vanilla JavaScript |
| **Icons** | Font Awesome 6.0 |
| **Server** | Apache 2.4 (XAMPP) dengan mod_rewrite |

---

## Struktur Folder

```
filemanager.smkn62.sch.id/
├── api/                        # REST API endpoints (AJAX)
│   ├── categories.php          # Data kategori
│   ├── category-data.php       # Data per kategori
│   ├── files-data.php          # Data file dari Drive
│   ├── forms-data.php          # Data form dari Sheets
│   ├── links-data.php          # Data link dari Sheets
│   ├── recent.php              # Upload terbaru
│   ├── stats.php               # Statistik dashboard
│   ├── storage-settings.php    # Pengaturan storage
│   ├── upload.php              # Upload handler
│   └── users.php               # CRUD user (admin)
├── assets/
│   ├── css/
│   │   ├── style.css           # Stylesheet utama
│   │   └── ajax.css            # Loading & skeleton styles
│   ├── images/
│   │   └── icons/              # PWA icons (72–512px)
│   └── js/
│       ├── main.js             # JavaScript utama
│       ├── ajax.js             # AJAX helper functions
│       ├── upload-manager.js   # Upload queue & progress
│       ├── table-pagination.js # Pagination universal
│       ├── session-keepalive.js# Auto-logout & warning
│       └── pwa.js              # PWA registration
├── auth/
│   ├── login.php               # Halaman login
│   └── logout.php              # Proses logout
├── data/
│   ├── .htaccess               # Block akses langsung
│   ├── credentials/            # Service account key (gitignored)
│   ├── storage_config.json     # Konfigurasi storage (gitignored)
│   └── storage_oauth.json      # OAuth token (gitignored)
├── includes/
│   ├── config.php              # Konfigurasi utama (gitignored)
│   ├── db.php                  # Koneksi MySQL
│   ├── google_client.php       # Google API client helper
│   ├── ajax_helpers.php        # Helper fungsi AJAX
│   ├── category_modals.php     # Modal CRUD kategori
│   ├── header.php              # Header template
│   ├── footer.php              # Footer template
│   ├── sidebar.php             # Sidebar navigasi
│   └── page-navigation.php     # Breadcrumb navigasi
├── pages/
│   ├── category/               # Halaman per bidang
│   │   ├── kesiswaan.php
│   │   ├── kurikulum.php
│   │   ├── sapras-humas.php
│   │   └── tata-usaha.php
│   ├── files/                  # File manager
│   │   ├── index.php           # Daftar file
│   │   ├── upload.php          # Upload file
│   │   └── delete.php          # Hapus file
│   ├── forms/                  # Manajemen forms
│   │   ├── index.php
│   │   ├── add.php
│   │   ├── edit.php
│   │   └── delete.php
│   ├── links/                  # Manajemen links
│   │   ├── index.php
│   │   ├── add.php
│   │   ├── edit.php
│   │   └── delete.php
│   ├── settings.php            # Pengaturan & user management
│   ├── profile.php             # Profil user
│   ├── setup-google.php        # Setup Google OAuth
│   ├── privacy.php             # Privacy policy
│   └── terms.php               # Terms of service
├── .htaccess                   # URL rewrite & security rules
├── index.php                   # Dashboard
├── error.php                   # Custom error page (400–500)
├── sw.js                       # Service worker
├── manifest.json               # PWA manifest
├── offline.html                # Offline fallback
├── composer.json               # PHP dependencies
└── README.md
```

---

## Instalasi

### 1. Clone Repository

```bash
git clone https://github.com/LTZ24/FileManager-62.git
```

### 2. Install Dependencies

```bash
cd FileManager-62
composer install
```

### 3. Buat Database MySQL

Buat database dan tabel `users`:

```sql
CREATE DATABASE filemanager_smkn62;
USE filemanager_smkn62;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100) DEFAULT NULL,
    role ENUM('admin', 'staff/guru') DEFAULT 'staff/guru',
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Buat akun admin default (password: admin123)
INSERT INTO users (username, password_hash, role)
VALUES ('admin', '$2y$10$YOUR_BCRYPT_HASH_HERE', 'admin');
```

> Ganti `$2y$10$YOUR_BCRYPT_HASH_HERE` dengan hasil `password_hash('admin123', PASSWORD_DEFAULT)`.

### 4. Buat tabel `system_config`

```sql
CREATE TABLE system_config (
    config_key VARCHAR(100) PRIMARY KEY,
    config_value TEXT,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### 5. Konfigurasi Aplikasi

Buat file `includes/config.php` berdasarkan template — isi konfigurasi database MySQL:

```php
// Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'filemanager_smkn62');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### 6. Konfigurasi Google API

1. Buat project di [Google Cloud Console](https://console.cloud.google.com/)
2. Enable **Google Sheets API** dan **Google Drive API**
3. Buat **Service Account** → download JSON key → simpan di `data/credentials/`
4. Buat **OAuth 2.0 Client ID** (untuk fitur upload ke Drive)
5. Buka halaman Settings di aplikasi → Setup Google OAuth

### 7. Konfigurasi Storage

Buat file `data/storage_config.json`:

```json
{
    "service_account_path": "credentials/service-account.json",
    "spreadsheet_id": "YOUR_GOOGLE_SHEETS_ID",
    "drive_folder_id": "YOUR_GOOGLE_DRIVE_FOLDER_ID"
}
```

---

## Menjalankan Aplikasi

### XAMPP (Windows)

1. Simpan folder project di `C:\xampp\htdocs\`
2. Jalankan Apache dan MySQL dari XAMPP Control Panel
3. Akses: `http://localhost/FileManager-62`

### Login

- Username: `admin`
- Password: (sesuai yang dibuat saat instalasi)

---

## Keamanan

| Fitur | Detail |
|---|---|
| Password hashing | bcrypt (`PASSWORD_DEFAULT`) |
| Session | Auto-logout 30 menit, session regeneration on login |
| CSRF | Token pada semua form POST |
| Rate limiting | 8 login attempts per 60 detik |
| Clean URL | `.htaccess` rewrite, `.php` dihapus dari URL |
| Data protection | `data/` di-block via `.htaccess` (no PHP execution) |
| Credentials | Service account key & OAuth token di-gitignore |
| Setup page | HMAC token + admin password gate (TTL 10 menit) |

---

## Screenshot

> _Tambahkan screenshot dashboard, file manager, dan settings di sini._

---

## Kontributor

- **Developer**: LTZ24
- **GitHub**: [@LTZ24](https://github.com/LTZ24)

## Lisensi

MIT License — Copyright © 2025-2026 LTZ24
