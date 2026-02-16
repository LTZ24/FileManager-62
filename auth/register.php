<?php
/**
 * Register Page — Self-registration for Staff/Guru only
 * FileManager SMKN 62 Jakarta v3.0
 */
require_once __DIR__ . '/../includes/config.php';

// Already logged in → dashboard
if (isLoggedIn()) {
    redirect(BASE_URL . '/');
}

$error = '';
$success = '';
$username = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    $token = $_POST['csrf_token'] ?? '';
    if (!verifySecureToken($token)) {
        $error = 'Token keamanan tidak valid. Silakan muat ulang halaman.';
    } else {
        // Rate limit
        if (!checkRateLimit('register_attempt', 5, 300)) {
            $error = 'Terlalu banyak percobaan registrasi. Coba lagi dalam 5 menit.';
        } else {
            $username = strtolower(trim($_POST['username'] ?? ''));
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $passwordConfirm = $_POST['password_confirm'] ?? '';

            // Validations
            if (empty($username)) {
                $error = 'Username wajib diisi.';
            } elseif (strlen($username) < 3) {
                $error = 'Username minimal 3 karakter.';
            } elseif (!preg_match('/^[a-z0-9_]+$/', $username)) {
                $error = 'Username hanya boleh berisi huruf kecil, angka, dan underscore.';
            } elseif (empty($password)) {
                $error = 'Password wajib diisi.';
            } elseif (strlen($password) < 6) {
                $error = 'Password minimal 6 karakter.';
            } elseif ($password !== $passwordConfirm) {
                $error = 'Password tidak cocok.';
            } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Format email tidak valid.';
            }

            if (empty($error)) {
                try {
                    $db = getDB();

                    // Check duplicate username
                    $stmt = $db->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
                    $stmt->execute([$username]);
                    if ($stmt->fetch()) {
                        $error = 'Username "' . htmlspecialchars($username) . '" sudah digunakan.';
                    }

                    // Check duplicate email
                    if (empty($error) && !empty($email)) {
                        $stmt = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
                        $stmt->execute([$email]);
                        if ($stmt->fetch()) {
                            $error = 'Email sudah digunakan.';
                        }
                    }

                    if (empty($error)) {
                        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $db->prepare('INSERT INTO users (username, password_hash, email, role, is_active, created_at) VALUES (?, ?, ?, ?, 1, NOW())');
                        $stmt->execute([
                            $username,
                            $passwordHash,
                            $email ?: null,
                            'staff/guru'
                        ]);

                        logSecurityEvent('SELF_REGISTER', ['new_user' => $username, 'role' => 'staff/guru']);

                        $success = 'Akun berhasil dibuat! Silakan login.';
                        $username = '';
                        $email = '';
                    }
                } catch (Throwable $e) {
                    error_log('Register error: ' . $e->getMessage());
                    $error = 'Terjadi kesalahan sistem. Silakan coba lagi.';
                }
            }
        }
    }
}

$csrfToken = generateSecureToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - <?php echo APP_NAME; ?></title>

    <meta name="theme-color" content="#50e3c2">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo BASE_URL; ?>/assets/images/icons/icon-128x128.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo BASE_URL; ?>/assets/images/icons/icon-72x72.png">
    <link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>/assets/images/icons/icon-152x152.png">
    <link rel="manifest" href="<?php echo BASE_URL; ?>/manifest.json?v=<?php echo urlencode(APP_VERSION); ?>">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css?v=<?php echo urlencode(APP_VERSION); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body.register-page {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #ffffff;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            margin: 0;
        }

        .register-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .register-content {
            text-align: center;
            width: 100%;
            max-width: 450px;
        }

        .register-logo { margin-bottom: 1.5rem; }
        .register-logo img { width: 100px; height: 100px; object-fit: contain; }

        .register-title { margin-bottom: 1.5rem; }
        .register-title h1 { font-size: 1.5rem; color: #202124; font-weight: 400; margin-bottom: 0.35rem; }
        .register-title p { font-size: 0.95rem; color: #5f6368; }

        .alert-error {
            background: #f8d7da; border: 1px solid #f5c2c7; color: #842029;
            padding: 1rem; border-radius: 8px; margin-bottom: 1.25rem;
            display: flex; align-items: start; gap: 0.75rem; font-size: 0.875rem;
            text-align: left; animation: slideDown 0.3s ease;
        }
        .alert-error i { font-size: 1.25rem; margin-top: 2px; }

        .alert-success {
            background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46;
            padding: 1rem; border-radius: 8px; margin-bottom: 1.25rem;
            display: flex; align-items: center; gap: 0.75rem; font-size: 0.875rem;
            animation: slideDown 0.3s ease;
        }

        .register-form { margin-bottom: 1.5rem; text-align: left; }

        .form-group { margin-bottom: 1rem; }
        .form-group label {
            display: block; font-size: 0.8125rem; font-weight: 500;
            color: #5f6368; margin-bottom: 0.4rem; letter-spacing: 0.25px;
        }

        .input-wrap { position: relative; }
        .input-wrap input {
            width: 100%; padding: 0.75rem 0.9rem 0.75rem 2.5rem;
            border: 1px solid #dadce0; border-radius: 4px;
            font-size: 0.9375rem; color: #202124; background: #fff;
            transition: border-color 0.2s, box-shadow 0.2s; outline: none;
        }
        .input-wrap input:focus {
            border-color: #1a73e8;
            box-shadow: 0 0 0 2px rgba(26, 115, 232, 0.15);
        }
        .input-wrap .input-icon {
            position: absolute; left: 0.85rem; top: 50%; transform: translateY(-50%);
            color: #9aa0a6; font-size: 0.875rem; pointer-events: none;
        }
        .input-wrap .toggle-password {
            position: absolute; right: 0.6rem; top: 50%; transform: translateY(-50%);
            background: none; border: none; color: #9aa0a6; cursor: pointer;
            font-size: 0.95rem; padding: 4px;
        }
        .input-wrap .toggle-password:hover { color: #5f6368; }

        .form-hint {
            font-size: 0.75rem; color: #9aa0a6; margin-top: 0.3rem;
        }

        .btn-register {
            width: 100%; background: #10b981; color: #fff; border: none;
            padding: 12px 24px; font-size: 0.9375rem; font-weight: 500;
            letter-spacing: 0.25px; border-radius: 4px; cursor: pointer;
            transition: background-color 0.2s, box-shadow 0.2s;
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            box-shadow: 0 1px 2px 0 rgba(60,64,67,0.3), 0 1px 3px 1px rgba(60,64,67,0.15);
            margin-top: 0.5rem;
        }
        .btn-register:hover { background: #059669; }
        .btn-register:active { background: #047857; }

        .login-link {
            margin-top: 1rem; font-size: 0.875rem; color: #5f6368;
        }
        .login-link a {
            color: #1a73e8; text-decoration: none; font-weight: 500;
        }
        .login-link a:hover { text-decoration: underline; }

        .role-info {
            margin-top: 1.5rem; padding: 12px 16px;
            background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px;
            font-size: 0.8rem; color: #166534; text-align: center;
        }
        .role-info i { margin-right: 6px; }

        .page-footer {
            background: #f8f9fa; border-top: 1px solid #dadce0;
            padding: 12px 20px; text-align: center; white-space: nowrap;
        }
        .page-footer span { color: #5f6368; font-size: 0.75rem; }
        .page-footer a { color: #5f6368; text-decoration: none; font-size: 0.75rem; transition: color 0.2s; }
        .page-footer a:hover { color: #202124; }
        .page-footer .sep { color: #dadce0; margin: 0 8px; font-size: 0.75rem; }

        .back-login {
            position: absolute; top: 16px; left: 20px;
            display: inline-flex; align-items: center; gap: 6px;
            text-decoration: none; color: #5f6368; font-size: 0.8125rem; font-weight: 500;
            padding: 8px 16px; border: 1px solid #dadce0; border-radius: 4px;
            transition: background 0.2s, color 0.2s;
        }
        .back-login:hover { background: #f8f9fa; color: #202124; }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 768px) {
            .register-container { padding: 20px; }
            .register-logo img { width: 80px; height: 80px; }
            .register-title h1 { font-size: 1.25rem; }
            .input-wrap input { padding: 0.65rem 0.8rem 0.65rem 2.25rem; font-size: 0.875rem; }
            .btn-register { padding: 10px 20px; font-size: 0.875rem; }
        }

        @media (max-width: 600px) {
            .back-login { top: 12px; left: 12px; font-size: 0.75rem; padding: 6px 12px; }
        }
    </style>
</head>
<body class="register-page">
    <a class="back-login" href="<?php echo BASE_URL; ?>/auth/login">&larr; Login</a>

    <div class="register-container">
        <div class="register-content">
            <div class="register-logo">
                <img src="<?php echo BASE_URL; ?>/assets/images/smk62.png" alt="Logo SMKN 62">
            </div>

            <div class="register-title">
                <h1>Daftar Akun Baru</h1>
                <p>Buat akun staff untuk mengakses sistem</p>
            </div>

            <?php if ($error): ?>
                <div class="alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <div><?php echo htmlspecialchars($error); ?></div>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert-success">
                    <i class="fas fa-check-circle"></i>
                    <div>
                        <?php echo htmlspecialchars($success); ?>
                        <a href="<?php echo BASE_URL; ?>/auth/login" style="color: #059669; font-weight: 600; margin-left: 4px;">Login &rarr;</a>
                    </div>
                </div>
            <?php endif; ?>

            <div class="register-form">
                <form method="POST" action="" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                    <div class="form-group">
                        <label for="username">Username</label>
                        <div class="input-wrap">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" id="username" name="username"
                                   value="<?php echo htmlspecialchars($username); ?>"
                                   placeholder="Masukkan username"
                                   required autofocus>
                        </div>
                        <div class="form-hint">Huruf kecil, angka, underscore. Min. 3 karakter.</div>
                    </div>

                    <div class="form-group">
                        <label for="email">Email <span style="color: #9aa0a6; font-weight: 400;">(opsional)</span></label>
                        <div class="input-wrap">
                            <i class="fas fa-envelope input-icon"></i>
                            <input type="email" id="email" name="email"
                                   value="<?php echo htmlspecialchars($email); ?>"
                                   placeholder="contoh@email.com">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-wrap">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" id="password" name="password"
                                   placeholder="Minimal 6 karakter"
                                   required>
                            <button type="button" class="toggle-password" onclick="togglePwd('password','pwdIcon1')" aria-label="Toggle password">
                                <i class="fas fa-eye" id="pwdIcon1"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password_confirm">Konfirmasi Password</label>
                        <div class="input-wrap">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" id="password_confirm" name="password_confirm"
                                   placeholder="Ulangi password"
                                   required>
                            <button type="button" class="toggle-password" onclick="togglePwd('password_confirm','pwdIcon2')" aria-label="Toggle password">
                                <i class="fas fa-eye" id="pwdIcon2"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-register">
                        <i class="fas fa-user-plus"></i> Daftar
                    </button>
                </form>

                <div class="login-link">
                    Sudah punya akun? <a href="<?php echo BASE_URL; ?>/auth/login">Login di sini</a>
                </div>
            </div>

            <div class="role-info">
                <i class="fas fa-info-circle"></i>
                Akun yang didaftarkan akan memiliki role <strong>Staff/Guru</strong>.
            </div>

        </div>
    </div>

    <footer class="page-footer">
        <span>&copy; <?php echo date('Y'); ?> SMKN 62 Jakarta.</span>
        <span class="sep">|</span>
        <a href="<?php echo BASE_URL; ?>/pages/privacy">Privacy &amp; Policy</a>
        <span class="sep">&middot;</span>
        <a href="<?php echo BASE_URL; ?>/pages/terms">Terms of Service</a>
    </footer>

    <script>
        function togglePwd(inputId, iconId) {
            var inp = document.getElementById(inputId);
            var ico = document.getElementById(iconId);
            if (inp.type === 'password') {
                inp.type = 'text';
                ico.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                inp.type = 'password';
                ico.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
    </script>
</body>
</html>
