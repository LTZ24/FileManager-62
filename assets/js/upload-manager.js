/**
 * Upload Manager - Handles file uploads with progress tracking
 * 
 * Features:
 * - Batch upload (max 15 files)
 * - Progress bar for each file
 * - Notification dropdown (Android-style)
 * - Session keepalive during upload
 * - Toast notifications
 * - All file types supported
 */

class UploadManager {
    constructor(options = {}) {
        this.baseUrl = options.baseUrl || window.baseUrl || '';
        this.maxFiles = options.maxFiles || 15;
        this.maxFileSize = options.maxFileSize || 100 * 1024 * 1024; // 100MB
        this.category = options.category || '';
        this.onComplete = options.onComplete || (() => {});
        this.onError = options.onError || (() => {});
        this.onProgress = options.onProgress || (() => {});
        
        this.uploadQueue = [];
        this.isUploading = false;
        this.currentUploads = 0;
        this.maxConcurrent = 2;
        this.keepaliveInterval = null;
        this.uploadContainer = null;
        this.notificationDropdown = null;
        
        this.init();
    }
    
    init() {
        this.createUploadContainer();
        this.createNotificationDropdown();
        this.startKeepalive();
    }
    
    createUploadContainer() {
        if (document.getElementById('uploadManagerContainer')) {
            this.uploadContainer = document.getElementById('uploadManagerContainer');
            return;
        }
        
        const container = document.createElement('div');
        container.id = 'uploadManagerContainer';
        container.className = 'upload-manager-container';
        container.innerHTML = `
            <div class="upload-manager-header">
                <span class="upload-manager-title">
                    <i class="fas fa-cloud-upload-alt"></i> 
                    <span id="uploadManagerTitle">Upload Files</span>
                </span>
                <div class="upload-manager-actions">
                    <button type="button" class="upload-manager-minimize" onclick="uploadManager.minimizeToDropdown()" title="Minimize ke notifikasi">
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <button type="button" class="upload-manager-close" onclick="uploadManager.close()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="upload-manager-body" id="uploadManagerBody">
                <div class="upload-list" id="uploadList"></div>
            </div>
            <div class="upload-manager-footer" id="uploadManagerFooter">
                <div class="upload-summary">
                    <span id="uploadSummary">0 files</span>
                </div>
            </div>
        `;
        
        document.body.appendChild(container);
        this.uploadContainer = container;
        
        this.injectStyles();
    }
    
    createNotificationDropdown() {
        // Upload section is now static in header.php, just get reference to the list element
        this.notificationDropdown = document.getElementById('uploadDropdownList');
    }
    
    injectStyles() {
        if (document.getElementById('uploadManagerStyles')) return;
        
        const style = document.createElement('style');
        style.id = 'uploadManagerStyles';
        style.textContent = `
            /* Notification Bell */
            .upload-notification-bell {
                position: relative;
                display: inline-flex;
                align-items: center;
            }
            
            .upload-nav-item {
                margin-right: 8px;
            }
            
            .upload-bell-btn {
                background: transparent;
                border: none;
                color: #64748b;
                font-size: 18px;
                padding: 8px 12px;
                border-radius: 8px;
                cursor: pointer;
                position: relative;
                transition: all 0.2s;
            }
            
            /* Upload Badge Indicator on User Menu */
            .upload-badge-indicator {
                position: absolute;
                top: 2px;
                right: 2px;
                background: #3b82f6;
                color: white;
                font-size: 8px;
                width: 16px;
                height: 16px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                animation: badgePulse 2s infinite;
                box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            }
            
            @keyframes badgePulse {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.1); }
            }
            
            /* Upload Section inside User Dropdown */
            .upload-notification-section {
                border-top: 1px solid #e2e8f0;
            }
            
            .upload-dropdown-header-inline {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 10px 16px;
                background: #ffffff;
                color: #64748b;
                font-weight: 600;
                font-size: 12px;
                border-bottom: 1px solid #e2e8f0;
            }
            
            .upload-dropdown-header-inline i {
                margin-right: 6px;
                color: #3b82f6;
            }
            
            .upload-dropdown-actions-inline {
                display: flex;
                gap: 4px;
            }
            
            .upload-dropdown-actions-inline button {
                background: #f1f5f9;
                border: none;
                color: #64748b;
                width: 24px;
                height: 24px;
                border-radius: 4px;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: background 0.2s;
                font-size: 11px;
            }
            
            .upload-dropdown-actions-inline button:hover {
                background: rgba(255,255,255,0.3);
            }
            
            .upload-dropdown-body-inline {
                max-height: 200px;
                overflow-y: auto;
                background: #f8fafc;
            }
            
            .upload-dropdown-footer-inline {
                padding: 8px 16px;
                background: #f1f5f9;
                border-top: 1px solid #e2e8f0;
                font-size: 11px;
                color: #64748b;
                text-align: center;
            }
            
            .upload-dropdown-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 12px 16px;
                background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
                color: white;
                font-weight: 600;
                font-size: 13px;
            }
            
            .upload-dropdown-header i {
                margin-right: 8px;
            }
            
            .upload-dropdown-actions {
                display: flex;
                gap: 4px;
            }
            
            .upload-dropdown-actions button {
                background: rgba(255,255,255,0.2);
                border: none;
                color: white;
                width: 28px;
                height: 28px;
                border-radius: 6px;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: background 0.2s;
            }
            
            .upload-dropdown-actions button:hover {
                background: rgba(255,255,255,0.3);
            }
            
            .upload-dropdown-body {
                max-height: 320px;
                overflow-y: auto;
            }
            
            .upload-dropdown-empty {
                padding: 40px 20px;
                text-align: center;
                color: #94a3b8;
            }
            
            .upload-dropdown-empty i {
                font-size: 32px;
                margin-bottom: 12px;
                opacity: 0.5;
            }
            
            .upload-dropdown-empty p {
                margin: 0;
                font-size: 13px;
            }
            
            .upload-dropdown-footer {
                padding: 10px 16px;
                background: #f8fafc;
                border-top: 1px solid #e2e8f0;
                font-size: 12px;
                color: #64748b;
                text-align: center;
            }
            
            /* Dropdown Item */
            .upload-dropdown-item {
                display: flex;
                align-items: flex-start;
                gap: 12px;
                padding: 12px 16px;
                border-bottom: 1px solid #f1f5f9;
                position: relative;
                transition: background 0.2s;
            }
            
            .upload-dropdown-item:hover {
                background: #f8fafc;
            }
            
            .upload-dropdown-item:last-child {
                border-bottom: none;
            }
            
            .upload-dropdown-item.uploading {
                background: #eff6ff;
            }
            
            .upload-dropdown-item.success {
                background: #f0fdf4;
            }
            
            .upload-dropdown-item.error {
                background: #fef2f2;
            }
            
            .upload-dropdown-item-icon {
                width: 32px;
                height: 32px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: #e2e8f0;
                border-radius: 6px;
                color: #64748b;
                font-size: 14px;
                flex-shrink: 0;
            }
            
            .upload-dropdown-item.uploading .upload-dropdown-item-icon {
                background: #dbeafe;
                color: #3b82f6;
            }
            
            .upload-dropdown-item.success .upload-dropdown-item-icon {
                background: #dcfce7;
                color: #22c55e;
            }
            
            .upload-dropdown-item.error .upload-dropdown-item-icon {
                background: #fee2e2;
                color: #ef4444;
            }
            
            .upload-dropdown-item-content {
                flex: 1;
                min-width: 0;
            }
            
            .upload-dropdown-item-name {
                font-size: 13px;
                font-weight: 500;
                color: #1e293b;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                margin-bottom: 4px;
            }
            
            .upload-dropdown-item-meta {
                display: flex;
                align-items: center;
                gap: 8px;
                font-size: 11px;
                color: #64748b;
            }
            
            .upload-dropdown-item-progress {
                width: 100%;
                height: 3px;
                background: #e2e8f0;
                border-radius: 2px;
                margin-top: 6px;
                overflow: hidden;
            }
            
            .upload-dropdown-item-progress-bar {
                height: 100%;
                background: linear-gradient(90deg, #3b82f6 0%, #2563eb 100%);
                border-radius: 2px;
                transition: width 0.2s ease;
                width: 0%;
            }
            
            .upload-dropdown-item.success .upload-dropdown-item-progress-bar {
                background: linear-gradient(90deg, #22c55e 0%, #16a34a 100%);
                width: 100%;
            }
            
            .upload-dropdown-item-dismiss {
                position: absolute;
                top: 8px;
                right: 8px;
                background: transparent;
                border: none;
                color: #94a3b8;
                cursor: pointer;
                padding: 4px;
                border-radius: 4px;
                opacity: 0;
                transition: all 0.2s;
            }
            
            .upload-dropdown-item:hover .upload-dropdown-item-dismiss {
                opacity: 1;
            }
            
            .upload-dropdown-item-dismiss:hover {
                background: #fee2e2;
                color: #ef4444;
            }
            
            /* Main Upload Panel */
            .upload-manager-container {
                position: fixed;
                bottom: 20px;
                right: 20px;
                width: 400px;
                max-width: calc(100vw - 40px);
                background: white;
                border-radius: 12px;
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
                z-index: 10000;
                display: none;
                overflow: hidden;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            }
            
            .upload-manager-container.show {
                display: block;
                animation: slideUp 0.3s ease;
            }
            
            @keyframes slideUp {
                from { transform: translateY(100px); opacity: 0; }
                to { transform: translateY(0); opacity: 1; }
            }
            
            .upload-manager-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 12px 16px;
                background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
                color: white;
            }
            
            .upload-manager-title {
                display: flex;
                align-items: center;
                gap: 8px;
                font-weight: 600;
                font-size: 14px;
            }
            
            .upload-manager-actions {
                display: flex;
                gap: 8px;
            }
            
            .upload-manager-minimize,
            .upload-manager-close {
                background: rgba(255, 255, 255, 0.2);
                border: none;
                color: white;
                width: 28px;
                height: 28px;
                border-radius: 6px;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: background 0.2s;
            }
            
            .upload-manager-minimize:hover,
            .upload-manager-close:hover {
                background: rgba(255, 255, 255, 0.3);
            }
            
            .upload-manager-body {
                max-height: 300px;
                overflow-y: auto;
                padding: 8px;
            }
            
            .upload-list {
                display: flex;
                flex-direction: column;
                gap: 8px;
            }
            
            .upload-item {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 10px 12px;
                background: #f8fafc;
                border-radius: 8px;
                border: 1px solid #e2e8f0;
            }
            
            .upload-item.uploading {
                background: #eff6ff;
                border-color: #bfdbfe;
            }
            
            .upload-item.success {
                background: #f0fdf4;
                border-color: #bbf7d0;
            }
            
            .upload-item.error {
                background: #fef2f2;
                border-color: #fecaca;
            }
            
            .upload-item-icon {
                width: 36px;
                height: 36px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: #e2e8f0;
                border-radius: 8px;
                color: #64748b;
                font-size: 16px;
                flex-shrink: 0;
            }
            
            .upload-item.uploading .upload-item-icon {
                background: #dbeafe;
                color: #3b82f6;
            }
            
            .upload-item.success .upload-item-icon {
                background: #dcfce7;
                color: #22c55e;
            }
            
            .upload-item.error .upload-item-icon {
                background: #fee2e2;
                color: #ef4444;
            }
            
            .upload-item-info {
                flex: 1;
                min-width: 0;
            }
            
            .upload-item-name {
                font-size: 13px;
                font-weight: 500;
                color: #1e293b;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            
            .upload-item-meta {
                display: flex;
                align-items: center;
                gap: 8px;
                font-size: 11px;
                color: #64748b;
                margin-top: 4px;
            }
            
            .upload-item-progress {
                width: 100%;
                height: 4px;
                background: #e2e8f0;
                border-radius: 2px;
                margin-top: 6px;
                overflow: hidden;
            }
            
            .upload-item-progress-bar {
                height: 100%;
                background: linear-gradient(90deg, #3b82f6 0%, #2563eb 100%);
                border-radius: 2px;
                transition: width 0.2s ease;
                width: 0%;
            }
            
            .upload-item.success .upload-item-progress-bar {
                background: linear-gradient(90deg, #22c55e 0%, #16a34a 100%);
                width: 100%;
            }
            
            .upload-item-status {
                font-size: 12px;
                flex-shrink: 0;
            }
            
            .upload-item.uploading .upload-item-status {
                color: #3b82f6;
            }
            
            .upload-item.success .upload-item-status {
                color: #22c55e;
            }
            
            .upload-item.error .upload-item-status {
                color: #ef4444;
            }
            
            .upload-manager-footer {
                padding: 12px 16px;
                background: #f8fafc;
                border-top: 1px solid #e2e8f0;
            }
            
            .upload-summary {
                font-size: 12px;
                color: #64748b;
                text-align: center;
            }
            
            /* Toast notifications */
            .upload-toast {
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 12px 20px;
                background: white;
                border-radius: 8px;
                box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
                display: flex;
                align-items: center;
                gap: 10px;
                z-index: 10001;
                animation: toastSlideIn 0.3s ease;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            }
            
            .upload-toast.success {
                border-left: 4px solid #22c55e;
            }
            
            .upload-toast.error {
                border-left: 4px solid #ef4444;
            }
            
            .upload-toast.info {
                border-left: 4px solid #3b82f6;
            }
            
            @keyframes toastSlideIn {
                from { transform: translateX(100px); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            
            @keyframes toastSlideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100px); opacity: 0; }
            }
            
            .upload-toast-icon {
                font-size: 18px;
            }
            
            .upload-toast.success .upload-toast-icon {
                color: #22c55e;
            }
            
            .upload-toast.error .upload-toast-icon {
                color: #ef4444;
            }
            
            .upload-toast.info .upload-toast-icon {
                color: #3b82f6;
            }
            
            .upload-toast-message {
                font-size: 14px;
                color: #1e293b;
            }
            
            /* Mobile toast styles */
            @media (max-width: 480px) {
                .upload-toast {
                    left: 16px;
                    right: 16px;
                    top: 16px;
                    max-width: none;
                    animation: toastSlideDown 0.3s ease;
                }
                
                @keyframes toastSlideDown {
                    from { transform: translateY(-100%); opacity: 0; }
                    to { transform: translateY(0); opacity: 1; }
                }
                
                @keyframes toastSlideUp {
                    from { transform: translateY(0); opacity: 1; }
                    to { transform: translateY(-100%); opacity: 0; }
                }
            }
            
            /* Batch file input styling */
            .batch-file-input {
                display: none;
            }
            
            @media (max-width: 480px) {
                .upload-manager-container {
                    width: calc(100vw - 20px);
                    right: 10px;
                    bottom: 10px;
                }
                
                .upload-notification-dropdown {
                    width: calc(100vw - 20px);
                    right: -10px;
                }
            }
            
            /* Mobile: always show dismiss button (no hover on touch devices) */
            @media (max-width: 768px) {
                .upload-dropdown-item-dismiss {
                    opacity: 1;
                    padding: 8px;
                    min-width: 32px;
                    min-height: 32px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
            }
        `;
        
        document.head.appendChild(style);
    }
    
    show() {
        this.uploadContainer.classList.add('show');
        this.hideNotificationDropdown();
    }
    
    hide() {
        this.uploadContainer.classList.remove('show');
    }
    
    close() {
        if (this.isUploading) {
            if (!confirm('Upload masih berjalan. Yakin ingin menutup?')) {
                return;
            }
        }
        this.hide();
        this.clearQueue();
    }
    
    // Minimize to notification dropdown
    minimizeToDropdown() {
        this.hide();
        this.syncToDropdown();
        this.showNotificationDropdown();
    }
    
    // Expand from dropdown to full panel
    expandFromDropdown() {
        this.hideNotificationDropdown();
        this.show();
    }
    
    toggleNotificationDropdown() {
        // Now using user dropdown - open/close user dropdown
        const userMenuToggle = document.getElementById('userMenuToggle');
        const userDropdown = document.getElementById('userDropdown');
        if (userMenuToggle && userDropdown) {
            userDropdown.classList.toggle('active');
            userMenuToggle.classList.toggle('active');
        }
    }
    
    showNotificationDropdown() {
        this.syncToDropdown();
        // Open user dropdown to show upload section
        const userMenuToggle = document.getElementById('userMenuToggle');
        const userDropdown = document.getElementById('userDropdown');
        if (userMenuToggle && userDropdown) {
            userDropdown.classList.add('active');
            userMenuToggle.classList.add('active');
        }
    }
    
    hideNotificationDropdown() {
        // Don't close user dropdown automatically - let user control it
        // Just sync the content
        this.syncToDropdown();
    }
    
    // Sync upload items to dropdown
    syncToDropdown() {
        const list = document.getElementById('uploadDropdownList');
        if (!list) return;
        
        if (this.uploadQueue.length === 0) {
            list.innerHTML = `
                <div class="upload-dropdown-empty">
                    <i class="fas fa-inbox"></i>
                    <p>Tidak ada upload aktif</p>
                </div>
            `;
            this.updateDropdownSummary();
            return;
        }
        
        list.innerHTML = this.uploadQueue.map(item => this.renderDropdownItem(item)).join('');
        this.updateDropdownSummary();
    }
    
    renderDropdownItem(item) {
        const icon = this.getFileIcon(item.file.name);
        let statusText = 'Menunggu...';
        let statusIcon = 'fa-clock';
        
        if (item.status === 'uploading') {
            statusText = item.progress + '%';
            statusIcon = 'fa-spinner fa-spin';
        } else if (item.status === 'success') {
            statusText = 'Selesai';
            statusIcon = 'fa-check';
        } else if (item.status === 'error') {
            statusText = item.error || 'Gagal';
            statusIcon = 'fa-exclamation-circle';
        }
        
        return `
            <div class="upload-dropdown-item ${item.status}" id="dropdown_${item.id}">
                <div class="upload-dropdown-item-icon">
                    <i class="fas ${icon}"></i>
                </div>
                <div class="upload-dropdown-item-content">
                    <div class="upload-dropdown-item-name">${this.escapeHtml(item.file.name)}</div>
                    <div class="upload-dropdown-item-meta">
                        <span>${this.formatFileSize(item.file.size)}</span>
                        <span>•</span>
                        <span><i class="fas ${statusIcon}"></i> ${statusText}</span>
                    </div>
                    <div class="upload-dropdown-item-progress">
                        <div class="upload-dropdown-item-progress-bar" style="width: ${item.progress}%"></div>
                    </div>
                </div>
                <button type="button" class="upload-dropdown-item-dismiss" onclick="uploadManager.dismissItem('${item.id}')" title="Hapus">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
    }
    
    updateDropdownItem(item) {
        const element = document.getElementById('dropdown_' + item.id);
        if (!element) return;
        
        element.className = 'upload-dropdown-item ' + item.status;
        
        const progressBar = element.querySelector('.upload-dropdown-item-progress-bar');
        if (progressBar) {
            progressBar.style.width = item.progress + '%';
        }
        
        const meta = element.querySelector('.upload-dropdown-item-meta');
        if (meta) {
            let statusText = 'Menunggu...';
            let statusIcon = 'fa-clock';
            
            if (item.status === 'uploading') {
                statusText = item.progress + '%';
                statusIcon = 'fa-spinner fa-spin';
            } else if (item.status === 'success') {
                statusText = 'Selesai';
                statusIcon = 'fa-check';
            } else if (item.status === 'error') {
                statusText = item.error || 'Gagal';
                statusIcon = 'fa-exclamation-circle';
            }
            
            meta.innerHTML = `
                <span>${this.formatFileSize(item.file.size)}</span>
                <span>•</span>
                <span><i class="fas ${statusIcon}"></i> ${statusText}</span>
            `;
        }
    }
    
    updateDropdownSummary() {
        const summary = document.getElementById('uploadDropdownSummary');
        if (!summary) return;
        
        const total = this.uploadQueue.length;
        const completed = this.uploadQueue.filter(i => i.status === 'success').length;
        const failed = this.uploadQueue.filter(i => i.status === 'error').length;
        const uploading = this.uploadQueue.filter(i => i.status === 'uploading').length;
        
        if (total === 0) {
            summary.textContent = '-';
        } else if (uploading > 0) {
            summary.textContent = `Mengupload ${uploading} dari ${total} file...`;
        } else {
            summary.textContent = `${completed} berhasil${failed > 0 ? `, ${failed} gagal` : ''} dari ${total} file`;
        }
    }
    
    // Update badge on user menu (show upload indicator)
    updateBadge() {
        const userMenuToggle = document.getElementById('userMenuToggle');
        if (!userMenuToggle) return;
        
        // Create or find badge on user menu
        let badge = userMenuToggle.querySelector('.upload-badge-indicator');
        
        const active = this.uploadQueue.filter(i => i.status === 'pending' || i.status === 'uploading').length;
        const errors = this.uploadQueue.filter(i => i.status === 'error').length;
        
        if (active > 0 || errors > 0) {
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'upload-badge-indicator';
                userMenuToggle.style.position = 'relative';
                userMenuToggle.appendChild(badge);
            }
            badge.style.display = 'flex';
            if (errors > 0) {
                badge.innerHTML = '<i class="fas fa-exclamation"></i>';
                badge.style.background = '#ef4444';
            } else {
                badge.innerHTML = '<i class="fas fa-cloud-upload-alt"></i>';
                badge.style.background = '#3b82f6';
            }
        } else if (badge) {
            badge.style.display = 'none';
        }
    }
    
    // Dismiss (remove) an item from queue
    dismissItem(itemId) {
        const index = this.uploadQueue.findIndex(i => i.id === itemId);
        if (index === -1) return;
        
        const item = this.uploadQueue[index];
        
        // If uploading, abort the XHR request
        if (item.status === 'uploading' && item.xhr) {
            item.xhr.abort();
            item.status = 'cancelled';
            if (typeof window.showToast === 'function') {
                window.showToast('Upload dibatalkan', 'error');
            }
        }
        
        // Remove from queue
        this.uploadQueue.splice(index, 1);
        
        // Remove from both lists
        document.getElementById(itemId)?.remove();
        document.getElementById('dropdown_' + itemId)?.remove();
        
        // Update UI
        this.updateSummary();
        this.updateBadge();
        this.syncToDropdown();
    }
    
    // Clear all completed/error items
    clearCompleted() {
        this.uploadQueue = this.uploadQueue.filter(item => {
            if (item.status === 'success' || item.status === 'error') {
                document.getElementById(item.id)?.remove();
                document.getElementById('dropdown_' + item.id)?.remove();
                return false;
            }
            return true;
        });
        
        this.updateSummary();
        this.updateBadge();
        this.syncToDropdown();
    }
    
    clearQueue() {
        this.uploadQueue = [];
        document.getElementById('uploadList').innerHTML = '';
        this.updateSummary();
        this.updateBadge();
        this.syncToDropdown();
    }
    
    startKeepalive() {
        if (this.keepaliveInterval) return;
        
        this.keepaliveInterval = setInterval(() => {
            if (this.isUploading) {
                fetch(`${this.baseUrl}/api/upload?action=keepalive`, {
                    method: 'GET',
                    credentials: 'same-origin'
                }).catch(() => {});
            }
        }, 30000);
    }
    
    stopKeepalive() {
        if (this.keepaliveInterval) {
            clearInterval(this.keepaliveInterval);
            this.keepaliveInterval = null;
        }
    }
    
    /**
     * Add files to upload queue and auto-hide panel
     */
    addFiles(files, category) {
        const fileArray = Array.from(files);
        
        if (fileArray.length === 0) return;
        
        // Check max files limit
        const totalFiles = this.uploadQueue.length + fileArray.length;
        if (totalFiles > this.maxFiles) {
            this.showToast(`Maksimal ${this.maxFiles} file dalam satu batch`, 'error');
            return;
        }
        
        // Validate files and add to queue
        for (const file of fileArray) {
            if (file.size > this.maxFileSize) {
                this.showToast(`File "${file.name}" melebihi batas 100MB`, 'error');
                continue;
            }
            
            const uploadItem = {
                id: this.generateId(),
                file: file,
                category: category,
                status: 'pending',
                progress: 0,
                error: null
            };
            
            this.uploadQueue.push(uploadItem);
            this.renderUploadItem(uploadItem);
        }
        
        // Show briefly then auto-minimize to dropdown when starting upload
        this.show();
        this.updateSummary();
        this.updateBadge();
        
        // Auto-hide after short delay and start processing
        setTimeout(() => {
            this.minimizeToDropdown();
        }, 500);
        
        this.processQueue();
    }
    
    generateId() {
        return 'upload_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }
    
    renderUploadItem(item) {
        const list = document.getElementById('uploadList');
        const icon = this.getFileIcon(item.file.name);
        
        const element = document.createElement('div');
        element.id = item.id;
        element.className = 'upload-item';
        element.innerHTML = `
            <div class="upload-item-icon">
                <i class="fas ${icon}"></i>
            </div>
            <div class="upload-item-info">
                <div class="upload-item-name">${this.escapeHtml(item.file.name)}</div>
                <div class="upload-item-meta">
                    <span>${this.formatFileSize(item.file.size)}</span>
                    <span>•</span>
                    <span class="upload-item-percent">Menunggu...</span>
                </div>
                <div class="upload-item-progress">
                    <div class="upload-item-progress-bar" style="width: 0%"></div>
                </div>
            </div>
            <div class="upload-item-status">
                <i class="fas fa-clock"></i>
            </div>
        `;
        
        list.appendChild(element);
    }
    
    updateUploadItem(item) {
        const element = document.getElementById(item.id);
        if (element) {
            // Update status class
            element.className = 'upload-item ' + item.status;
            
            // Update progress bar
            const progressBar = element.querySelector('.upload-item-progress-bar');
            if (progressBar) {
                progressBar.style.width = item.progress + '%';
            }
            
            // Update percentage text
            const percentText = element.querySelector('.upload-item-percent');
            if (percentText) {
                if (item.status === 'uploading') {
                    percentText.textContent = item.progress + '%';
                } else if (item.status === 'success') {
                    percentText.textContent = 'Selesai';
                } else if (item.status === 'error') {
                    percentText.textContent = item.error || 'Gagal';
                }
            }
            
            // Update status icon
            const statusIcon = element.querySelector('.upload-item-status i');
            if (statusIcon) {
                if (item.status === 'uploading') {
                    statusIcon.className = 'fas fa-spinner fa-spin';
                } else if (item.status === 'success') {
                    statusIcon.className = 'fas fa-check';
                } else if (item.status === 'error') {
                    statusIcon.className = 'fas fa-exclamation-circle';
                }
            }
        }
        
        // Also update dropdown item
        this.updateDropdownItem(item);
        this.updateBadge();
    }
    
    async processQueue() {
        if (this.isUploading) return;
        
        const pendingItems = this.uploadQueue.filter(item => item.status === 'pending');
        if (pendingItems.length === 0) return;
        
        this.isUploading = true;
        
        for (const item of pendingItems) {
            await this.uploadFile(item);
        }
        
        this.isUploading = false;
        this.onComplete(this.uploadQueue);
        this.updateSummary();
        this.updateBadge();
        this.syncToDropdown();
        
        // Show completion toast
        const successCount = this.uploadQueue.filter(i => i.status === 'success').length;
        const failCount = this.uploadQueue.filter(i => i.status === 'error').length;
        
        if (successCount > 0) {
            this.showToast(`${successCount} file berhasil diupload${failCount > 0 ? `, ${failCount} gagal` : ''}`, failCount > 0 ? 'info' : 'success');
        } else if (failCount > 0) {
            this.showToast(`${failCount} file gagal diupload`, 'error');
        }
    }
    
    async uploadFile(item) {
        item.status = 'uploading';
        item.progress = 0;
        this.updateUploadItem(item);
        
        try {
            const formData = new FormData();
            formData.append('file', item.file);
            formData.append('category', item.category);
            formData.append('action', 'upload');
            
            const result = await new Promise((resolve, reject) => {
                const xhr = new XMLHttpRequest();
                
                // Store xhr reference on item so we can abort it
                item.xhr = xhr;
                
                xhr.upload.addEventListener('progress', (e) => {
                    if (e.lengthComputable) {
                        item.progress = Math.round((e.loaded / e.total) * 100);
                        this.updateUploadItem(item);
                        this.onProgress(item);
                    }
                });
                
                xhr.addEventListener('load', () => {
                    item.xhr = null; // Clear reference
                    const raw = (xhr.responseText || '');
                    let parsed = null;
                    try { parsed = raw ? JSON.parse(raw) : null; } catch (e) { parsed = null; }

                    if (xhr.status >= 200 && xhr.status < 300) {
                        if (parsed) {
                            resolve(parsed);
                            return;
                        }
                        reject(new Error('Invalid JSON response'));
                        return;
                    }

                    // Non-2xx: try to surface server message
                    if (parsed && (parsed.message || parsed.error)) {
                        reject(new Error(parsed.message || parsed.error));
                        return;
                    }
                    const snippet = raw.replace(/\s+/g, ' ').trim().slice(0, 160);
                    reject(new Error(`HTTP ${xhr.status}${snippet ? ': ' + snippet : ''}`));
                });
                
                xhr.addEventListener('error', () => {
                    item.xhr = null; // Clear reference
                    reject(new Error('Network error'));
                });
                
                xhr.addEventListener('abort', () => {
                    item.xhr = null; // Clear reference
                    reject(new Error('Upload cancelled'));
                });
                
                xhr.open('POST', `${this.baseUrl}/api/upload`);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.send(formData);
            });
            
            if (result.success) {
                item.status = 'success';
                item.progress = 100;
                item.result = result.data;
            } else {
                item.status = 'error';
                item.error = result.message || 'Upload gagal';
            }
        } catch (error) {
            // Don't set error if cancelled - item may already be removed
            if (item.status === 'cancelled') {
                return; // Item already handled by dismissItem
            }
            item.status = 'error';
            item.error = error.message;
            this.onError(item, error);
        }
        
        this.updateUploadItem(item);
        this.updateSummary();
    }
    
    updateSummary() {
        const summary = document.getElementById('uploadSummary');
        const title = document.getElementById('uploadManagerTitle');
        
        const total = this.uploadQueue.length;
        const completed = this.uploadQueue.filter(i => i.status === 'success').length;
        const failed = this.uploadQueue.filter(i => i.status === 'error').length;
        const uploading = this.uploadQueue.filter(i => i.status === 'uploading').length;
        
        if (summary) {
            if (total === 0) {
                summary.textContent = '0 files';
            } else if (uploading > 0) {
                summary.textContent = `Mengupload ${uploading} dari ${total} file...`;
            } else {
                summary.textContent = `${completed} berhasil${failed > 0 ? `, ${failed} gagal` : ''} dari ${total} file`;
            }
        }
        
        if (title) {
            if (total === 0) {
                title.textContent = 'Upload Files';
            } else if (uploading > 0) {
                title.textContent = `Uploading (${completed}/${total})`;
            } else {
                title.textContent = `Upload Selesai`;
            }
        }
        
        // Update dropdown summary too
        this.updateDropdownSummary();
    }
    
    showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `upload-toast ${type}`;
        
        let icon = 'fa-info-circle';
        if (type === 'success') icon = 'fa-check-circle';
        if (type === 'error') icon = 'fa-exclamation-circle';
        
        toast.innerHTML = `
            <div class="upload-toast-icon"><i class="fas ${icon}"></i></div>
            <div class="upload-toast-message">${this.escapeHtml(message)}</div>
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            // Use appropriate slide-out animation based on screen size
            const isMobile = window.innerWidth <= 480;
            const slideOutAnim = isMobile ? 'toastSlideUp 0.3s ease forwards' : 'toastSlideOut 0.3s ease forwards';
            toast.style.animation = slideOutAnim;
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }
    
    getFileIcon(filename) {
        const ext = filename.split('.').pop().toLowerCase();
        
        const iconMap = {
            'pdf': 'fa-file-pdf',
            'doc': 'fa-file-word',
            'docx': 'fa-file-word',
            'xls': 'fa-file-excel',
            'xlsx': 'fa-file-excel',
            'ppt': 'fa-file-powerpoint',
            'pptx': 'fa-file-powerpoint',
            'txt': 'fa-file-alt',
            'rtf': 'fa-file-alt',
            'jpg': 'fa-file-image',
            'jpeg': 'fa-file-image',
            'png': 'fa-file-image',
            'gif': 'fa-file-image',
            'webp': 'fa-file-image',
            'svg': 'fa-file-image',
            'bmp': 'fa-file-image',
            'heic': 'fa-file-image',
            'mp4': 'fa-file-video',
            'avi': 'fa-file-video',
            'mkv': 'fa-file-video',
            'mov': 'fa-file-video',
            'wmv': 'fa-file-video',
            'webm': 'fa-file-video',
            'mp3': 'fa-file-audio',
            'wav': 'fa-file-audio',
            'ogg': 'fa-file-audio',
            'flac': 'fa-file-audio',
            'm4a': 'fa-file-audio',
            'zip': 'fa-file-archive',
            'rar': 'fa-file-archive',
            '7z': 'fa-file-archive',
            'tar': 'fa-file-archive',
            'gz': 'fa-file-archive',
            'html': 'fa-file-code',
            'css': 'fa-file-code',
            'js': 'fa-file-code',
            'php': 'fa-file-code',
            'py': 'fa-file-code',
            'java': 'fa-file-code',
            'json': 'fa-file-code',
            'exe': 'fa-cog',
            'msi': 'fa-cog',
            'dmg': 'fa-apple-alt',
            'apk': 'fa-android',
            'ipa': 'fa-apple-alt'
        };
        
        return iconMap[ext] || 'fa-file';
    }
    
    formatFileSize(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Global instance
let uploadManager = null;

/**
 * Initialize upload manager for a category page
 */
function initUploadManager(category, baseUrl) {
    uploadManager = new UploadManager({
        category: category,
        baseUrl: baseUrl,
        onComplete: async (queue) => {
            const successCount = queue.filter(i => i.status === 'success').length;
            if (successCount > 0 && typeof reloadFilesTable === 'function') {
                try {
                    await reloadFilesTable();
                } catch (e) {
                    console.warn('Could not reload files table:', e);
                }
            }
        }
    });
    
    return uploadManager;
}

/**
 * Open batch file selector - auto hides upload panel when files selected
 */
function openBatchFileSelector(category) {
    let input = document.getElementById('batchFileInput');
    if (!input) {
        input = document.createElement('input');
        input.type = 'file';
        input.id = 'batchFileInput';
        input.className = 'batch-file-input';
        input.multiple = true;
        input.style.display = 'none';
        document.body.appendChild(input);
    }
    
    input.removeAttribute('accept');
    
    input.onchange = (e) => {
        if (e.target.files.length > 0) {
            if (!uploadManager) {
                uploadManager = initUploadManager(category, window.baseUrl || '');
            }
            uploadManager.addFiles(e.target.files, category);
        }
        input.value = '';
    };
    
    input.click();
}
