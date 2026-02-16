<?php
/**
 * Login Page — Username / Password Authentication
 * FileManager SMKN 62 Jakarta v3.0
 *
 * UI restored to classic Google-style clean layout.
 */
require_once __DIR__ . '/../includes/config.php';

// Already logged in → dashboard
if (isLoggedIn()) {
    redirect(BASE_URL . '/');
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        if (!checkRateLimit('login_attempt', 8, 60)) {
            $error = 'Terlalu banyak percobaan login. Coba lagi dalam 1 menit.';
        } else {
            $db = getDB();
            $stmt = $db->prepare('SELECT id, username, password_hash, email, role FROM users WHERE username = ? AND is_active = 1 LIMIT 1');
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);

                $_SESSION['user_id']       = $user['id'];
                $_SESSION['username']      = $user['username'];
                $_SESSION['user_name']     = $user['username'];
                $_SESSION['user_email']    = $user['email'] ?? '';
                $_SESSION['user_role']     = $user['role'];
                $_SESSION['last_activity'] = time();
                $_SESSION['created']       = time();

                logSecurityEvent('LOGIN_SUCCESS', ['user' => $user['username']]);
                redirect(BASE_URL . '/');
            } else {
                logSecurityEvent('LOGIN_FAILED', ['user' => $username]);
                $error = 'Username atau password salah.';
            }
        }
    }
}

$logoutReason = isset($_GET['reason']) ? $_GET['reason'] : '';
$csrfToken = generateSecureToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>

    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#50e3c2">
    <meta name="description" content="File Manager internal SMK Negeri 62 Jakarta">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="FileManager SMKN62">

    <!-- Icons -->
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo BASE_URL; ?>/assets/images/icons/icon-128x128.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo BASE_URL; ?>/assets/images/icons/icon-72x72.png">
    <link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>/assets/images/icons/icon-152x152.png">

    <!-- PWA Manifest -->
    <link rel="manifest" href="<?php echo BASE_URL; ?>/manifest.json?v=<?php echo urlencode(APP_VERSION); ?>">

    <!-- Stylesheets -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css?v=<?php echo urlencode(APP_VERSION); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body.login-page {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #ffffff;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            margin: 0;
            padding: 0;
        }

        .login-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            max-width: 100%;
        }

        .login-content {
            text-align: center;
            width: 100%;
            max-width: 450px;
        }

        .login-logo {
            margin-bottom: 2rem;
        }

        .login-logo img {
            width: 150px;
            height: 150px;
            object-fit: contain;
        }

        .login-title {
            margin-bottom: 2rem;
        }

        .login-title h1 {
            font-size: 1.5rem;
            color: #202124;
            margin: 0 0 0.5rem 0;
            font-weight: 400;
        }

        .login-title p {
            font-size: 1rem;
            color: #5f6368;
            margin: 0;
            font-weight: 400;
        }

        /* Toast notification */
        .login-toast {
            position: fixed;
            top: 18px;
            right: 18px;
            z-index: 99999;
            background: rgba(15, 23, 42, 0.95);
            color: #ffffff;
            padding: 0.85rem 1rem;
            border-radius: 10px;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.25);
            display: none;
            max-width: min(420px, calc(100vw - 36px));
            font-size: 0.9rem;
            line-height: 1.35;
        }

        .login-toast.show {
            display: flex;
            gap: 0.6rem;
            align-items: flex-start;
            animation: slideDown 0.25s ease;
        }

        .login-toast i {
            margin-top: 2px;
            color: #fbbf24;
        }

        /* Alert styles */
        .alert-error {
            background: #f8d7da;
            border: 1px solid #f5c2c7;
            color: #842029;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: start;
            gap: 0.75rem;
            font-size: 0.875rem;
            animation: slideDown 0.3s ease;
            text-align: left;
        }

        .alert-error i {
            font-size: 1.25rem;
            margin-top: 2px;
        }

        .alert-success {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #065f46;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.875rem;
            animation: slideDown 0.3s ease;
        }

        /* Login form */
        .login-form {
            margin-bottom: 2rem;
            text-align: left;
        }

        .form-group {
            margin-bottom: 1.125rem;
        }

        .form-group label {
            display: block;
            font-size: 0.8125rem;
            font-weight: 500;
            color: #5f6368;
            margin-bottom: 0.4rem;
            letter-spacing: 0.25px;
        }

        .input-wrap {
            position: relative;
        }

        .input-wrap input {
            width: 100%;
            padding: 0.75rem 0.9rem 0.75rem 2.5rem;
            border: 1px solid #dadce0;
            border-radius: 4px;
            font-size: 0.9375rem;
            color: #202124;
            background: #fff;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
        }

        .input-wrap input:focus {
            border-color: #1a73e8;
            box-shadow: 0 0 0 2px rgba(26, 115, 232, 0.15);
        }

        .input-wrap .input-icon {
            position: absolute;
            left: 0.85rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9aa0a6;
            font-size: 0.875rem;
            pointer-events: none;
        }

        .input-wrap .toggle-password {
            position: absolute;
            right: 0.6rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #9aa0a6;
            cursor: pointer;
            font-size: 0.95rem;
            padding: 4px;
        }

        .input-wrap .toggle-password:hover {
            color: #5f6368;
        }

        /* Submit button — Google-style */
        .btn-login {
            width: 100%;
            background: #1a73e8;
            color: #fff;
            border: none;
            padding: 12px 24px;
            font-size: 0.9375rem;
            font-weight: 500;
            letter-spacing: 0.25px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.2s, box-shadow 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 1px 2px 0 rgba(60,64,67,0.3), 0 1px 3px 1px rgba(60,64,67,0.15);
            margin-top: 0.5rem;
        }

        .btn-login:hover {
            background: #1765cc;
            box-shadow: 0 1px 3px 0 rgba(60,64,67,0.3), 0 4px 8px 3px rgba(60,64,67,0.15);
        }

        .btn-login:active {
            background: #185abc;
        }

        .btn-login:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Data notice */
        .data-notice {
            margin-top: 2rem;
            padding: 16px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #dadce0;
            font-size: 0.75rem;
            color: #5f6368;
            line-height: 1.6;
            text-align: justify;
        }

        .data-notice strong {
            display: block;
            text-align: center;
            color: #202124;
            margin-bottom: 8px;
        }

        /* Footer */
        .page-footer {
            background: #f8f9fa;
            border-top: 1px solid #dadce0;
            padding: 12px 20px;
            width: 100%;
            text-align: center;
            white-space: nowrap;
        }

        .page-footer span {
            color: #5f6368;
            font-size: 0.75rem;
        }

        .page-footer a {
            color: #5f6368;
            text-decoration: none;
            font-size: 0.75rem;
            transition: color 0.2s;
        }

        .page-footer a:hover {
            color: #202124;
        }

        .page-footer .sep {
            color: #dadce0;
            margin: 0 8px;
            font-size: 0.75rem;
        }

        /* Back home link */
        .back-home {
            position: absolute;
            top: 16px;
            left: 20px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            color: #5f6368;
            font-size: 0.8125rem;
            font-weight: 500;
            padding: 8px 16px;
            border: 1px solid #dadce0;
            border-radius: 4px;
            transition: background 0.2s, color 0.2s;
        }

        .back-home:hover {
            background: #f8f9fa;
            color: #202124;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── Mobile Responsive ── */
        @media (max-width: 768px) {
            .login-container {
                padding: 20px;
            }

            .login-logo img {
                width: 100px;
                height: 100px;
            }

            .login-title h1 {
                font-size: 1.25rem;
            }

            .login-title p {
                font-size: 0.875rem;
            }

            .input-wrap input {
                padding: 0.65rem 0.8rem 0.65rem 2.25rem;
                font-size: 0.875rem;
            }

            .btn-login {
                padding: 10px 20px;
                font-size: 0.875rem;
            }

            .data-notice {
                font-size: 0.6875rem;
            }

            .page-footer {
                padding: 10px 12px;
            }

            .page-footer span, .page-footer a {
                font-size: 0.6875rem;
            }

            .page-footer .sep {
                margin: 0 4px;
                font-size: 0.6875rem;
            }
        }

        @media (max-width: 600px) {
            .back-home {
                top: 12px;
                left: 12px;
                font-size: 0.75rem;
                padding: 6px 12px;
            }

            .login-logo img {
                width: 80px;
                height: 80px;
            }
        }
    </style>
</head>
<body class="login-page">
    <a class="back-home" href="<?php echo BASE_URL; ?>/">&larr; Home</a>

    <div id="loginToast" class="login-toast" role="status" aria-live="polite">
        <i class="fas fa-clock"></i>
        <div>
            <strong>Sesi berakhir</strong><br>
            <small>Silakan login kembali untuk melanjutkan.</small>
        </div>
    </div>

    <div class="login-container">
        <div class="login-content">
            <div class="login-logo">
                <img src="<?php echo BASE_URL; ?>/assets/images/smk62.png" alt="Logo SMKN 62">
            </div>

            <div class="login-title">
                <h1>File Manager SMKN62</h1>
                <p>Masuk untuk mengakses sistem</p>
            </div>

            <?php if ($error): ?>
                <div class="alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <div><?php echo htmlspecialchars($error); ?></div>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['logout'])): ?>
                <div class="alert-success">
                    <i class="fas fa-check-circle"></i>
                    Anda telah berhasil keluar.
                </div>
            <?php endif; ?>

            <div class="login-form">
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
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-wrap">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" id="password" name="password"
                                   placeholder="Masukkan password"
                                   required>
                            <button type="button" class="toggle-password" onclick="togglePwd()" aria-label="Toggle password visibility">
                                <i class="fas fa-eye" id="pwdIcon"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-login">
                        <i class="fas fa-sign-in-alt"></i> Masuk
                    </button>
                </form>

                <div style="margin-top: 1rem; text-align: center; font-size: 0.875rem; color: #5f6368;">
                    Belum punya akun? <a href="<?php echo BASE_URL; ?>/auth/register" style="color: #1a73e8; text-decoration: none; font-weight: 500;">Daftar di sini</a>
                </div>
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

    <script src="<?php echo BASE_URL; ?>/assets/js/main.js?v=<?php echo urlencode(APP_VERSION); ?>"></script>
    <script>
        function togglePwd() {
            var inp = document.getElementById('password');
            var ico = document.getElementById('pwdIcon');
            if (inp.type === 'password') {
                inp.type = 'text';
                ico.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                inp.type = 'password';
                ico.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        // Session timeout toast (from old UI)
        (function () {
            try {
                var url = new URL(window.location.href);
                var params = url.searchParams;
                var changed = false;

                var hasVisitedKey = 'fm_login_visited_v1';
                var hasVisited = false;
                try {
                    hasVisited = window.localStorage && window.localStorage.getItem(hasVisitedKey) === '1';
                    if (window.localStorage) window.localStorage.setItem(hasVisitedKey, '1');
                } catch (e) {}

                var isTimeout = params.has('session_timeout') || (params.get('reason') === 'timeout');

                if (hasVisited && isTimeout) {
                    var toast = document.getElementById('loginToast');
                    if (toast) {
                        toast.classList.add('show');
                        setTimeout(function () { toast.classList.remove('show'); }, 3500);
                    }
                }

                if (params.has('session_timeout')) { params.delete('session_timeout'); changed = true; }
                if (params.get('reason') === 'timeout') { params.delete('reason'); changed = true; }
                if (changed) {
                    var newUrl = url.pathname + (params.toString() ? ('?' + params.toString()) : '') + url.hash;
                    window.history.replaceState({}, document.title, newUrl);
                }
            } catch (e) {}
        })();

        // PWA Mobile: close app on back button at login page
        (function () {
            var isStandalone = window.matchMedia('(display-mode: standalone)').matches
                            || window.navigator.standalone === true
                            || document.referrer.includes('android-app://');
            var isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)
                        || window.innerWidth <= 768;
            if (!isStandalone || !isMobile) return;

            if (window.history.state === null || !window.history.state.pwaLoginGuard) {
                window.history.pushState({ pwaLoginGuard: true }, document.title, window.location.href);
            }
            window.addEventListener('popstate', function () {
                try { window.close(); } catch (err) {}
                setTimeout(function () {
                    if (!window.closed) window.location.href = 'about:blank';
                }, 50);
            });
        })();
    </script>
</body>
</html>
