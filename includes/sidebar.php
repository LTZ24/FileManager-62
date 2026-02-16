<div class="sidebar no-transition" id="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <img src="<?php echo BASE_URL; ?>/assets/images/smk62.png" 
                 alt="SMKN 62 Jakarta" 
                 style="width: 40px; height: 40px; object-fit: contain; border-radius: 8px;">
            <span class="logo-text">SMKN 62 Jakarta</span>
        </div>
        <button class="sidebar-toggle" id="sidebarToggle" aria-label="Tutup Menu">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <nav class="sidebar-menu">
        <ul>
            <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'index.php' && strpos($_SERVER['PHP_SELF'], '/pages/') === false) ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>/" data-tooltip="Dashboard">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <li class="<?php echo (strpos($_SERVER['PHP_SELF'], '/pages/links/') !== false) ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>/pages/links/" data-tooltip="Kelola Links">
                    <i class="fas fa-link"></i>
                    <span>Kelola Links</span>
                </a>
            </li>

            <li class="<?php echo (strpos($_SERVER['PHP_SELF'], '/pages/forms/') !== false) ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>/pages/forms/" data-tooltip="Kelola Forms">
                    <i class="fas fa-file-alt"></i>
                    <span>Kelola Forms</span>
                </a>
            </li>

            <li class="<?php echo (strpos($_SERVER['PHP_SELF'], '/pages/files/') !== false && strpos($_SERVER['PHP_SELF'], 'upload.php') === false) ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>/pages/files/" data-tooltip="File Manager">
                    <i class="fas fa-folder-open"></i>
                    <span>File Manager</span>
                </a>
            </li>

            <li class="<?php echo (strpos($_SERVER['PHP_SELF'], '/pages/files/upload.php') !== false) ? 'active' : ''; ?>">
                <a href="<?php echo BASE_URL; ?>/pages/files/upload" data-tooltip="Upload File">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <span>Upload File</span>
                </a>
            </li>

            <li>
                <a href="<?php echo BASE_URL; ?>/auth/logout" data-tooltip="Keluar">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Keluar</span>
                </a>
            </li>
        </ul>
    </nav>
</div>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const menuToggle = document.getElementById('menuToggle');

        if (!sidebar || !sidebarToggle || !sidebarOverlay) {
            return;
        }

        // Buka sidebar dari header menu toggle
        if (menuToggle) {
            menuToggle.addEventListener('click', function(e) {
                e.preventDefault();
                sidebar.classList.add('active');
                sidebarOverlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            });
        }

        // Tutup sidebar dari tombol hamburger di sidebar
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            sidebar.classList.remove('active');
            sidebarOverlay.classList.remove('active');
            document.body.style.overflow = '';
        });

        // Tutup sidebar saat overlay diklik
        sidebarOverlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            sidebarOverlay.classList.remove('active');
            document.body.style.overflow = '';
        });

        // Tutup sidebar saat link di menu diklik
        sidebar.querySelectorAll('.sidebar-menu a').forEach(function(link) {
            link.addEventListener('click', function() {
                sidebar.classList.remove('active');
                sidebarOverlay.classList.remove('active');
                document.body.style.overflow = '';
            });
        });

        // Remove no-transition class after page load to enable animations
        setTimeout(function() {
            sidebar.classList.remove('no-transition');
            document.body.classList.add('page-loaded');
        }, 50);
    });
</script>
