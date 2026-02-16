<?php
// Make CSRF token available to all JS running on authenticated pages.
$__csrfToken = function_exists('generateSecureToken') ? generateSecureToken() : '';
?>
<script>
    window.APP_CSRF_TOKEN = <?php echo json_encode($__csrfToken, JSON_UNESCAPED_UNICODE); ?>;
</script>

<header class="header">
    <div class="header-left">
        <button class="menu-toggle" id="menuToggle">
            <i class="fas fa-bars"></i>
        </button>
        <div class="page-title">
            <?php
            $current_page = basename($_SERVER['PHP_SELF']);
            $page_titles = [
                'index.php' => 'Dashboard',
                'links' => 'Kelola Links',
                'forms' => 'Kelola Forms',
                'files' => 'File Manager',
                'upload.php' => 'Upload File'
            ];
            
            // Detect page title
            $title = 'Dashboard';
            if (strpos($_SERVER['PHP_SELF'], '/pages/links/') !== false) {
                if (strpos($_SERVER['PHP_SELF'], 'add.php') !== false) {
                    $title = 'Tambah Link';
                } elseif (strpos($_SERVER['PHP_SELF'], 'edit.php') !== false) {
                    $title = 'Edit Link';
                } else {
                    $title = 'Kelola Links';
                }
            } elseif (strpos($_SERVER['PHP_SELF'], '/pages/forms/') !== false) {
                if (strpos($_SERVER['PHP_SELF'], 'add.php') !== false) {
                    $title = 'Tambah Form';
                } elseif (strpos($_SERVER['PHP_SELF'], 'edit.php') !== false) {
                    $title = 'Edit Form';
                } else {
                    $title = 'Kelola Forms';
                }
            } elseif (strpos($_SERVER['PHP_SELF'], '/pages/files/upload.php') !== false) {
                $title = 'Upload File';
            } elseif (strpos($_SERVER['PHP_SELF'], '/pages/files/') !== false) {
                $title = 'File Manager';
            } elseif (strpos($_SERVER['PHP_SELF'], '/pages/settings.php') !== false) {
                $title = 'Pengaturan';
            } elseif (strpos($_SERVER['PHP_SELF'], '/pages/profile.php') !== false) {
                $title = 'Profil Saya';
            }
            ?>
            <h2><?php echo $title; ?></h2>
        </div>
    </div>

    <div class="header-right">
        <div class="datetime-display" id="datetimeDisplay">
            <i class="fas fa-calendar-alt"></i>
            <span id="currentDateTime"></span>
        </div>

        <div class="header-actions">
            <div class="user-menu" id="userMenuToggle">
                <div class="user-avatar-small">
                    <i class="fas fa-user-circle" style="font-size: 32px; color: #9ca3af;"></i>
                </div>
                <span><?php echo htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['username'] ?? 'User'); ?></span>
                <i class="fas fa-chevron-down dropdown-icon"></i>
            </div>

            <!-- User Dropdown Menu -->
            <div class="user-dropdown" id="userDropdown">
                <div class="dropdown-header">
                    <div class="dropdown-avatar">
                        <i class="fas fa-user-circle" style="font-size: 48px; color: #9ca3af;"></i>
                    </div>
                    <div class="dropdown-user-info">
                        <strong><?php echo htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['username'] ?? 'User'); ?></strong>
                        <span class="user-email"><?php echo htmlspecialchars($_SESSION['user_role'] ?? 'user'); ?></span>
                    </div>
                </div>

                <!-- DateTime for Mobile -->
                <div class="dropdown-datetime-mobile">
                    <i class="fas fa-calendar-alt"></i>
                    <span id="currentDateTimeMobile"></span>
                </div>

                <div class="dropdown-divider"></div>

                <div class="dropdown-menu-items">
                    <a href="<?php echo BASE_URL; ?>/pages/profile" class="dropdown-item">
                        <i class="fas fa-user-circle"></i>
                        <span>Profil</span>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/pages/settings" class="dropdown-item">
                        <i class="fas fa-cog"></i>
                        <span>Pengaturan</span>
                    </a>
                    
                    <div class="dropdown-divider"></div>
                    
                    <a href="<?php echo BASE_URL; ?>/auth/logout" class="dropdown-item logout-item">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Keluar</span>
                    </a>
                </div>

                <!-- Static Upload Progress Section -->
                <div id="uploadNotificationSection" class="upload-notification-section">
                    <div class="dropdown-divider"></div>
                    <div class="upload-dropdown-header-inline">
                        <span><i class="fas fa-cloud-upload-alt"></i> Upload Progress</span>
                        <div class="upload-dropdown-actions-inline">
                            <button type="button" onclick="if(window.uploadManager) window.uploadManager.clearCompleted()" title="Hapus selesai">
                                <i class="fas fa-broom"></i>
                            </button>
                        </div>
                    </div>
                    <div class="upload-dropdown-body-inline" id="uploadDropdownList">
                        <div class="upload-dropdown-empty">
                            <i class="fas fa-inbox"></i>
                            <p>Tidak ada upload aktif</p>
                        </div>
                    </div>
                    <div class="upload-dropdown-footer-inline" id="uploadDropdownFooter">
                        <span id="uploadDropdownSummary">-</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
    // Date Time Update (Indonesian only)
    function updateDateTime() {
        const now = new Date();
        
        const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                         'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        const monthsShort = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
                             'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        const dayName = days[now.getDay()];
        const day = now.getDate();
        const month = months[now.getMonth()];
        const year = now.getFullYear();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');

        const fullDateTimeString = `${dayName}, ${day} ${month} ${year} - ${hours}:${minutes}:${seconds} WIB`;
        const shortDateTimeString = `${dayName}, ${day} ${monthsShort[now.getMonth()]} ${year} ${hours}:${minutes} WIB`;
        const useShort = (window.matchMedia && window.matchMedia('(max-width: 420px)').matches);
        const headerDateTimeString = useShort ? shortDateTimeString : fullDateTimeString;
        const dateTimeElement = document.getElementById('currentDateTime');
        const dateTimeMobileElement = document.getElementById('currentDateTimeMobile');
        
        if (dateTimeElement) {
            dateTimeElement.textContent = headerDateTimeString;
        }
        if (dateTimeMobileElement) {
            // Keep full datetime inside dropdown on mobile.
            dateTimeMobileElement.textContent = fullDateTimeString;
        }
    }

    updateDateTime();
    setInterval(updateDateTime, 1000);

    // User Dropdown Toggle
    document.addEventListener('DOMContentLoaded', function() {
        const userMenuToggle = document.getElementById('userMenuToggle');
        const userDropdown = document.getElementById('userDropdown');

        if (userMenuToggle && userDropdown) {
            userMenuToggle.addEventListener('click', function(e) {
                e.stopPropagation();
                userDropdown.classList.toggle('active');
                userMenuToggle.classList.toggle('active');
            });

            userDropdown.addEventListener('click', function(e) {
                if (e.target.closest('a')) {
                    userDropdown.classList.remove('active');
                    userMenuToggle.classList.remove('active');
                }
            });

            document.addEventListener('click', function(e) {
                if (!userMenuToggle.contains(e.target) && !userDropdown.contains(e.target)) {
                    userDropdown.classList.remove('active');
                    userMenuToggle.classList.remove('active');
                }
            });
        }
    });
</script>
