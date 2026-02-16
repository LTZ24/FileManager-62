// Main JavaScript File
// Ensure confirm dialog is available ASAP (some pages are swapped via AJAX / history).
try { initConfirmModal(); } catch (e) { /* ignore */ }
try { initAccessCodeModal(); } catch (e) { /* ignore */ }

document.addEventListener('DOMContentLoaded', function() {
    // Never let one init failure break the rest
    try { initDarkMode(); } catch (e) { /* ignore */ }
    try { initTooltips(); } catch (e) { /* ignore */ }
    try { initModals(); } catch (e) { /* ignore */ }
    try { initConfirmModal(); } catch (e) { /* ignore */ }
    try { initAccessCodeModal(); } catch (e) { /* ignore */ }
    try { autoHideAlerts(); } catch (e) { /* ignore */ }
    try { initToastStyles(); } catch (e) { /* ignore */ }
});

// =========================================
// GLOBAL TOAST NOTIFICATION SYSTEM
// =========================================

function initToastStyles() {
    // Inject toast CSS if not already present
    if (document.getElementById('global-toast-styles')) return;
    
    const style = document.createElement('style');
    style.id = 'global-toast-styles';
    style.textContent = `
        .global-toast {
            position: fixed;
            top: 20px;
            right: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
            z-index: 10001;
            max-width: 400px;
            animation: globalToastSlideIn 0.3s ease;
            font-size: 14px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .global-toast.success {
            border-left: 4px solid #22c55e;
        }
        
        .global-toast.error {
            border-left: 4px solid #ef4444;
        }
        
        .global-toast.info {
            border-left: 4px solid #3b82f6;
        }
        
        .global-toast.warning {
            border-left: 4px solid #f59e0b;
        }
        
        .global-toast-icon {
            font-size: 18px;
            flex-shrink: 0;
        }
        
        .global-toast.success .global-toast-icon {
            color: #22c55e;
        }
        
        .global-toast.error .global-toast-icon {
            color: #ef4444;
        }
        
        .global-toast.info .global-toast-icon {
            color: #3b82f6;
        }
        
        .global-toast.warning .global-toast-icon {
            color: #f59e0b;
        }
        
        .global-toast-message {
            flex: 1;
            color: #1e293b;
            line-height: 1.4;
        }
        
        @keyframes globalToastSlideIn {
            from {
                transform: translateX(100px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes globalToastSlideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100px);
                opacity: 0;
            }
        }
        
        @media (max-width: 480px) {
            .global-toast {
                left: 16px;
                right: 16px;
                top: 16px;
                max-width: none;
                animation: globalToastSlideDown 0.3s ease;
            }
            
            @keyframes globalToastSlideDown {
                from {
                    transform: translateY(-100%);
                    opacity: 0;
                }
                to {
                    transform: translateY(0);
                    opacity: 1;
                }
            }
        }
        
        @keyframes globalToastSlideUp {
            from {
                transform: translateY(0);
                opacity: 1;
            }
            to {
                transform: translateY(-100%);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);
}

/**
 * Show a toast notification
 * @param {string} message - The message to display
 * @param {string} type - 'success', 'error', 'info', or 'warning'
 * @param {number} duration - Duration in ms (default 4000)
 */
window.showToast = function(message, type = 'info', duration = 4000) {
    // Ensure styles are injected
    initToastStyles();
    
    const toast = document.createElement('div');
    toast.className = `global-toast ${type}`;
    
    let icon = 'fa-info-circle';
    if (type === 'success') icon = 'fa-check-circle';
    if (type === 'error') icon = 'fa-exclamation-circle';
    if (type === 'warning') icon = 'fa-exclamation-triangle';
    
    // Escape HTML
    const escapeHtml = (str) => {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    };
    
    toast.innerHTML = `
        <div class="global-toast-icon"><i class="fas ${icon}"></i></div>
        <div class="global-toast-message">${escapeHtml(message)}</div>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        // Use appropriate slide-out animation based on screen size
        const isMobile = window.innerWidth <= 480;
        const slideOutAnim = isMobile ? 'globalToastSlideUp 0.3s ease forwards' : 'globalToastSlideOut 0.3s ease forwards';
        toast.style.animation = slideOutAnim;
        setTimeout(() => toast.remove(), 300);
    }, duration);
    
    return toast;
};

// =========================================

function initDarkMode() {
    // Optional feature: apply persisted dark mode preference if any.
    // Safe no-op if preference/toggle doesn't exist.
    try {
        const stored = localStorage.getItem('dark_mode');
        const enabled = stored === '1' || stored === 'true';
        document.body.classList.toggle('dark-mode', enabled);

        // If a toggle exists on any page, bind it.
        const toggle = document.getElementById('darkModeToggle') || document.querySelector('[data-dark-mode-toggle]');
        if (toggle && !toggle.dataset.bound) {
            toggle.dataset.bound = '1';
            if (toggle.type === 'checkbox') {
                toggle.checked = enabled;
                toggle.addEventListener('change', function() {
                    const on = !!toggle.checked;
                    document.body.classList.toggle('dark-mode', on);
                    localStorage.setItem('dark_mode', on ? '1' : '0');
                });
            } else {
                toggle.addEventListener('click', function() {
                    const on = !document.body.classList.contains('dark-mode');
                    document.body.classList.toggle('dark-mode', on);
                    localStorage.setItem('dark_mode', on ? '1' : '0');
                });
            }
        }
    } catch (e) {
        // ignore
    }
}


function initTooltips() {
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(element => {
        element.addEventListener('mouseenter', function() {
            const text = this.getAttribute('data-tooltip');
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = text;
            document.body.appendChild(tooltip);
            
            const rect = this.getBoundingClientRect();
            tooltip.style.top = (rect.top - tooltip.offsetHeight - 5) + 'px';
            tooltip.style.left = (rect.left + (rect.width - tooltip.offsetWidth) / 2) + 'px';
        });
        
        element.addEventListener('mouseleave', function() {
            const tooltip = document.querySelector('.tooltip');
            if (tooltip) {
                tooltip.remove();
            }
        });
    });
}

function initModals() {
    const modalTriggers = document.querySelectorAll('[data-modal]');
    modalTriggers.forEach(trigger => {
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            const modalId = this.getAttribute('data-modal');
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'block';
            }
        });
    });
    
    const modalCloses = document.querySelectorAll('.modal-close');
    modalCloses.forEach(close => {
        close.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                modal.style.display = 'none';
            }
        });
    });
    
    window.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            e.target.style.display = 'none';
        }
    });
}

function autoHideAlerts() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        if (!alert.hasAttribute('data-persistent')) {
            setTimeout(() => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            }, 5000);
        }
    });
}

// Backward-compatible helper (returns a Promise<boolean>)
function confirmDelete(message = 'Apakah Anda yakin ingin menghapus data ini?') {
    if (typeof window.showConfirmDialog === 'function') {
        return window.showConfirmDialog({
            title: 'Konfirmasi Hapus',
            message: message,
            confirmText: 'Hapus',
            cancelText: 'Batal',
            danger: true
        });
    }
    // Fallback (should rarely happen)
    return Promise.resolve(window.confirm(message));
}

function initConfirmModal() {
    // Always define window.showConfirmDialog so other code can rely on it.
    // This function binds to the current modal element (important for AJAX page swaps).
    window.__confirmModalState = window.__confirmModalState || { resolver: null, keyHandlerBound: false };

    function ensureModalExists() {
        let modal = document.getElementById('confirmModal');
        if (modal) return modal;

        // If modal HTML isn't present (e.g. page without footer), create it on the fly.
        modal = document.createElement('div');
        modal.id = 'confirmModal';
        modal.className = 'confirm-modal';
        modal.setAttribute('aria-hidden', 'true');
        modal.innerHTML = `
            <div class="confirm-modal__backdrop" data-confirm-cancel="1"></div>
            <div class="confirm-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="confirmModalTitle" aria-describedby="confirmModalMessage">
                <div class="confirm-modal__header">
                    <div class="confirm-modal__icon" aria-hidden="true"><i class="fas fa-triangle-exclamation"></i></div>
                    <div class="confirm-modal__titles">
                        <div id="confirmModalTitle" class="confirm-modal__title">Konfirmasi</div>
                        <div id="confirmModalMessage" class="confirm-modal__message">Apakah Anda yakin?</div>
                    </div>
                    <button type="button" class="confirm-modal__close" aria-label="Tutup" data-confirm-cancel="1"><i class="fas fa-times"></i></button>
                </div>
                <div class="confirm-modal__actions">
                    <button type="button" class="btn btn-outline" data-confirm-cancel="1">Batal</button>
                    <button type="button" class="btn btn-danger" id="confirmModalOk">Hapus</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        return modal;
    }

    function bindModal(modal) {
        if (!modal || modal.dataset.bound === '1') return;
        modal.dataset.bound = '1';

        function closeWith(value) {
            if (!modal.classList.contains('active')) return;
            modal.classList.remove('active');
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
            const r = window.__confirmModalState.resolver;
            window.__confirmModalState.resolver = null;
            if (typeof r === 'function') r(value);
        }

        const okBtn = modal.querySelector('#confirmModalOk');
        if (okBtn) {
            okBtn.addEventListener('click', function() {
                closeWith(true);
            });
        }

        modal.addEventListener('click', function(e) {
            const cancel = e.target.closest('[data-confirm-cancel="1"]');
            if (cancel) {
                e.preventDefault();
                closeWith(false);
            }
        });
    }

    if (typeof window.showConfirmDialog !== 'function') {
        window.showConfirmDialog = function(opts = {}) {
            const modal = ensureModalExists();
            bindModal(modal);

            const titleEl = modal.querySelector('#confirmModalTitle');
            const messageEl = modal.querySelector('#confirmModalMessage');
            const okBtn = modal.querySelector('#confirmModalOk');

            const title = opts.title || 'Konfirmasi';
            const message = opts.message || 'Apakah Anda yakin?';
            const confirmText = opts.confirmText || 'Hapus';
            const cancelText = opts.cancelText || 'Batal';
            const danger = (opts.danger !== false);

            if (titleEl) titleEl.textContent = title;
            if (messageEl) messageEl.textContent = message;
            if (okBtn) {
                okBtn.textContent = confirmText;
                okBtn.classList.toggle('btn-danger', !!danger);
                okBtn.classList.toggle('btn-primary', !danger);
            }

            // Only set label for the main cancel action button(s), not the top-right X button.
            const cancelButtons = modal.querySelectorAll('button.btn.btn-outline[data-confirm-cancel="1"]');
            cancelButtons.forEach(btn => {
                btn.textContent = cancelText;
            });

            modal.classList.add('active');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';

            if (!window.__confirmModalState.keyHandlerBound) {
                window.__confirmModalState.keyHandlerBound = true;
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        const activeModal = document.getElementById('confirmModal');
                        if (activeModal && activeModal.classList.contains('active')) {
                            activeModal.classList.remove('active');
                            activeModal.setAttribute('aria-hidden', 'true');
                            document.body.style.overflow = '';
                            const r = window.__confirmModalState.resolver;
                            window.__confirmModalState.resolver = null;
                            if (typeof r === 'function') r(false);
                        }
                    }
                });
            }

            return new Promise(resolve => {
                window.__confirmModalState.resolver = resolve;
                setTimeout(() => {
                    const btn = modal.querySelector('#confirmModalOk');
                    if (btn) btn.focus();
                }, 0);
            });
        };
    } else {
        // showConfirmDialog already exists; just ensure current modal is bound.
        const modal = document.getElementById('confirmModal');
        if (modal) bindModal(modal);
    }
}

// Access Code Modal (simple prompt with 1 or 2 password inputs)
function initAccessCodeModal() {
    window.__accessCodeModalState = window.__accessCodeModalState || { resolver: null, keyHandlerBound: false };

    function ensureModalExists() {
        let modal = document.getElementById('accessCodeModal');
        if (modal) return modal;

        modal = document.createElement('div');
        modal.id = 'accessCodeModal';
        // Reuse confirm modal base styles.
        modal.className = 'confirm-modal';
        modal.setAttribute('aria-hidden', 'true');
        modal.innerHTML = `
            <div class="confirm-modal__backdrop" data-access-cancel="1"></div>
            <div class="confirm-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="accessCodeModalTitle" aria-describedby="accessCodeModalMessage">
                <div class="confirm-modal__header">
                    <div class="confirm-modal__icon" aria-hidden="true"><i class="fas fa-shield-halved"></i></div>
                    <div class="confirm-modal__titles">
                        <div id="accessCodeModalTitle" class="confirm-modal__title">Verifikasi</div>
                        <div id="accessCodeModalMessage" class="confirm-modal__message">Masukkan kode akses internal.</div>
                    </div>
                    <button type="button" class="confirm-modal__close" aria-label="Tutup" data-access-cancel="1"><i class="fas fa-times"></i></button>
                </div>
                <div class="confirm-modal__actions" style="flex-direction: column; align-items: stretch;">
                    <div style="display:grid; gap:10px; width:100%;">
                        <div id="accessCodeFieldOldWrap" style="display:none;">
                            <label style="display:block; font-size:0.85rem; color:#475569; margin-bottom:6px;">Kode akses lama</label>
                            <input id="accessCodeOld" type="password" autocomplete="current-password" inputmode="text" style="width:100%; padding:12px 14px; border:1px solid rgba(226,232,240,.9); border-radius:12px; outline:none;" />
                        </div>
                        <div>
                            <label id="accessCodeNewLabel" style="display:block; font-size:0.85rem; color:#475569; margin-bottom:6px;">Kode akses</label>
                            <input id="accessCodeNew" type="password" autocomplete="one-time-code" inputmode="text" style="width:100%; padding:12px 14px; border:1px solid rgba(226,232,240,.9); border-radius:12px; outline:none;" />
                        </div>
                        <div id="accessCodeHint" style="font-size:0.82rem; color:#64748b; line-height:1.4;"></div>
                        <div id="accessCodeError" style="display:none; font-size:0.85rem; color:#b91c1c; background:rgba(248,113,113,.12); border:1px solid rgba(248,113,113,.28); padding:10px 12px; border-radius:12px;"></div>
                    </div>

                    <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:14px;">
                        <button type="button" class="btn btn-outline" data-access-cancel="1">Batal</button>
                        <button type="button" class="btn btn-primary" id="accessCodeOk">Lanjut</button>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        return modal;
    }

    function setBusy(modal, busy) {
        const okBtn = modal.querySelector('#accessCodeOk');
        const cancelBtns = modal.querySelectorAll('[data-access-cancel="1"]');
        if (okBtn) {
            okBtn.disabled = !!busy;
            okBtn.dataset.loading = busy ? '1' : '0';
            okBtn.style.opacity = busy ? '0.85' : '';
        }
        cancelBtns.forEach(btn => {
            if (btn && btn.tagName === 'BUTTON') btn.disabled = !!busy;
        });
    }

    function clearError(modal) {
        const errEl = modal.querySelector('#accessCodeError');
        if (!errEl) return;
        errEl.style.display = 'none';
        errEl.textContent = '';
    }

    function showError(modal, message) {
        const errEl = modal.querySelector('#accessCodeError');
        if (!errEl) return;
        errEl.textContent = message || 'Terjadi kesalahan.';
        errEl.style.display = 'block';
    }

    function bindModal(modal) {
        if (!modal || modal.dataset.bound === '1') return;
        modal.dataset.bound = '1';

        function closeWith(value) {
            if (!modal.classList.contains('active')) return;
            modal.classList.remove('active');
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
            const r = window.__accessCodeModalState.resolver;
            window.__accessCodeModalState.resolver = null;
            if (typeof r === 'function') r(value);
        }

        const okBtn = modal.querySelector('#accessCodeOk');
        if (okBtn) {
            okBtn.addEventListener('click', async function() {
                const oldEl = modal.querySelector('#accessCodeOld');
                const newEl = modal.querySelector('#accessCodeNew');
                const payload = {
                    oldCode: oldEl ? (oldEl.value || '') : '',
                    code: newEl ? (newEl.value || '') : ''
                };

                clearError(modal);

                const validate = window.__accessCodeModalState && window.__accessCodeModalState.validate;
                if (typeof validate !== 'function') {
                    closeWith(payload);
                    return;
                }

                try {
                    setBusy(modal, true);
                    const result = await validate(payload);
                    // Support boolean or object.
                    const ok = (result === true) || (result && result.ok === true);
                    if (ok) {
                        closeWith(payload);
                        return;
                    }
                    const msg = (result && result.error) ? result.error : (typeof result === 'string' ? result : 'Kode akses tidak valid.');
                    showError(modal, msg);
                    setBusy(modal, false);
                } catch (e) {
                    showError(modal, 'Terjadi kesalahan. Silakan coba lagi.');
                    setBusy(modal, false);
                }
            });
        }

        modal.addEventListener('click', function(e) {
            const cancel = e.target.closest('[data-access-cancel="1"]');
            if (cancel) {
                e.preventDefault();
                closeWith(null);
            }
        });

        if (!window.__accessCodeModalState.keyHandlerBound) {
            window.__accessCodeModalState.keyHandlerBound = true;
            document.addEventListener('keydown', function(e) {
                const activeModal = document.getElementById('accessCodeModal');
                if (!activeModal || !activeModal.classList.contains('active')) return;
                if (e.key === 'Escape') {
                    e.preventDefault();
                    closeWith(null);
                }
                if (e.key === 'Enter') {
                    const ok = activeModal.querySelector('#accessCodeOk');
                    if (ok) ok.click();
                }
            });
        }
    }

    window.showAccessCodeDialog = function(opts = {}) {
        const modal = ensureModalExists();
        bindModal(modal);

        const titleEl = modal.querySelector('#accessCodeModalTitle');
        const msgEl = modal.querySelector('#accessCodeModalMessage');
        const hintEl = modal.querySelector('#accessCodeHint');
        const errEl = modal.querySelector('#accessCodeError');
        const okBtn = modal.querySelector('#accessCodeOk');
        const oldWrap = modal.querySelector('#accessCodeFieldOldWrap');
        const oldEl = modal.querySelector('#accessCodeOld');
        const newEl = modal.querySelector('#accessCodeNew');
        const newLabel = modal.querySelector('#accessCodeNewLabel');

        const mode = opts.mode || 'verify'; // verify | change

        // If provided, OK will run this async validation and keep modal open on failure.
        window.__accessCodeModalState.validate = (typeof opts.validate === 'function') ? opts.validate : null;

        if (titleEl) titleEl.textContent = opts.title || (mode === 'change' ? 'Ubah Kode Akses' : 'Verifikasi Kode Akses');
        if (msgEl) msgEl.textContent = opts.message || (mode === 'change'
            ? 'Masukkan kode akses lama dan kode akses baru.'
            : 'Masukkan kode akses internal untuk melanjutkan login.');
        if (hintEl) hintEl.textContent = opts.hint || '';
        if (errEl) { errEl.style.display = 'none'; errEl.textContent = ''; }

        if (oldWrap) oldWrap.style.display = (mode === 'change') ? 'block' : 'none';
        if (newLabel) newLabel.textContent = (mode === 'change') ? 'Kode akses baru' : 'Kode akses';
        if (okBtn) okBtn.textContent = opts.confirmText || (mode === 'change' ? 'Simpan' : 'Lanjut');

        if (oldEl) oldEl.value = '';
        if (newEl) newEl.value = '';

        modal.classList.add('active');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';

        setBusy(modal, false);

        return new Promise(resolve => {
            window.__accessCodeModalState.resolver = resolve;
            setTimeout(() => {
                const focusTarget = (mode === 'change') ? (oldEl || newEl) : (newEl || oldEl);
                if (focusTarget) focusTarget.focus();
            }, 0);
        });
    };

    window.setAccessCodeDialogError = function(message) {
        const modal = document.getElementById('accessCodeModal');
        if (!modal) return;
        showError(modal, message);
    };
}

function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

function showLoading() {
    const loading = document.createElement('div');
    loading.className = 'loading-overlay';
    loading.innerHTML = '<div class="spinner"></div>';
    document.body.appendChild(loading);
}

function hideLoading() {
    const loading = document.querySelector('.loading-overlay');
    if (loading) {
        loading.remove();
    }
}

async function fetchData(url, options = {}) {
    try {
        showLoading();
        const mergedOptions = Object.assign({}, options);
        mergedOptions.headers = Object.assign({
            'X-Requested-With': 'XMLHttpRequest'
        }, options.headers || {});

        const response = await fetch(url, mergedOptions);
        const data = await response.json();
        hideLoading();
        return data;
    } catch (error) {
        hideLoading();
        console.error('Error fetching data:', error);
        alert('Terjadi kesalahan. Silakan coba lagi.');
        return null;
    }
}

window.confirmDelete = confirmDelete;
window.formatNumber = formatNumber;
window.showLoading = showLoading;
window.hideLoading = hideLoading;
window.fetchData = fetchData;
