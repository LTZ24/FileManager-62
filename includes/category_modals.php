<!-- Edit Link Modal -->
<div id="editLinkModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Edit Link</h3>
            <button class="close" onclick="closeEditLinkModal()">&times;</button>
        </div>
        <div class="modal-body">
            <iframe id="editLinkFrame" style="width: 100%; height: 500px; border: none;"></iframe>
        </div>
    </div>
</div>

<!-- Edit Form Modal -->
<div id="editFormModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Edit Form</h3>
            <button class="close" onclick="closeEditFormModal()">&times;</button>
        </div>
        <div class="modal-body">
            <iframe id="editFormFrame" style="width: 100%; height: 500px; border: none;"></iframe>
        </div>
    </div>
</div>

<!-- Detail Link Modal -->
<div id="detailLinkModal" class="modal">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3><i class="fas fa-info-circle"></i> Detail Link</h3>
            <button class="close" onclick="closeDetailLinkModal()">&times;</button>
        </div>
        <div class="modal-body" id="detailLinkContent" style="padding: 1.5rem;">
            <!-- Detail content will be inserted here -->
        </div>
    </div>
</div>

<!-- Detail Form Modal -->
<div id="detailFormModal" class="modal">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3><i class="fas fa-info-circle"></i> Detail Form</h3>
            <button class="close" onclick="closeDetailFormModal()">&times;</button>
        </div>
        <div class="modal-body" id="detailFormContent" style="padding: 1.5rem;">
            <!-- Detail content will be inserted here -->
        </div>
    </div>
</div>

<script>
// Edit Link Modal
function editLink(index) {
    const link = linksData[index];
    document.getElementById('editLinkFrame').src = '<?php echo BASE_URL; ?>/pages/links/edit_ctr?id=' + encodeURIComponent(String(link.id)) + '&category=' + encodeURIComponent(String(categoryKey));
    document.getElementById('editLinkModal').style.display = 'block';
}

function closeEditLinkModal() {
    document.getElementById('editLinkModal').style.display = 'none';
    document.getElementById('editLinkFrame').src = '';
}

// Edit Form Modal
function editForm(index) {
    const form = formsData[index];
    document.getElementById('editFormFrame').src = '<?php echo BASE_URL; ?>/pages/forms/edit_ctr?id=' + encodeURIComponent(String(form.id)) + '&category=' + encodeURIComponent(String(categoryKey));
    document.getElementById('editFormModal').style.display = 'block';
}

function closeEditFormModal() {
    document.getElementById('editFormModal').style.display = 'none';
    document.getElementById('editFormFrame').src = '';
}

// Detail Link Modal
function viewLinkDetail(index) {
    const link = linksData[index];
    const content = `
        <div style="margin-bottom: 1rem;">
            <label style="font-weight: 600; color: #64748b; font-size: 0.875rem; display: block; margin-bottom: 0.25rem;">Judul</label>
            <p style="margin: 0; padding: 0.75rem; background: #f8fafc; border-radius: 0.5rem; border: 1px solid #e2e8f0;">${link.title}</p>
        </div>
        <div style="margin-bottom: 1rem;">
            <label style="font-weight: 600; color: #64748b; font-size: 0.875rem; display: block; margin-bottom: 0.25rem;">URL</label>
            <div style="display: flex; gap: 0.5rem; align-items: center;">
                <p style="flex: 1; margin: 0; padding: 0.75rem; background: #f8fafc; border-radius: 0.5rem; border: 1px solid #e2e8f0; word-break: break-all;">${link.url}</p>
                <button onclick="copyToClipboard('${link.url.replace(/'/g, "\\'")}'); return false;" class="btn btn-sm btn-secondary" title="Copy URL">
                    <i class="fas fa-copy"></i>
                </button>
            </div>
        </div>
        <div style="margin-bottom: 1rem;">
            <label style="font-weight: 600; color: #64748b; font-size: 0.875rem; display: block; margin-bottom: 0.25rem;">Tanggal Dibuat</label>
            <p style="margin: 0; padding: 0.75rem; background: #f8fafc; border-radius: 0.5rem; border: 1px solid #e2e8f0;">${new Date(link.date).toLocaleDateString('id-ID', {day: '2-digit', month: 'long', year: 'numeric'})}</p>
        </div>
        <div style="margin-top: 1.5rem; text-align: right;">
            <a href="${link.url}" target="_blank" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-external-link-alt"></i> Buka Link
            </a>
        </div>
    `;
    document.getElementById('detailLinkContent').innerHTML = content;
    document.getElementById('detailLinkModal').style.display = 'block';
}

function closeDetailLinkModal() {
    document.getElementById('detailLinkModal').style.display = 'none';
}

// Detail Form Modal
function viewFormDetail(index) {
    const form = formsData[index];
    const content = `
        <div style="margin-bottom: 1rem;">
            <label style="font-weight: 600; color: #64748b; font-size: 0.875rem; display: block; margin-bottom: 0.25rem;">Judul</label>
            <p style="margin: 0; padding: 0.75rem; background: #f8fafc; border-radius: 0.5rem; border: 1px solid #e2e8f0;">${form.title}</p>
        </div>
        <div style="margin-bottom: 1rem;">
            <label style="font-weight: 600; color: #64748b; font-size: 0.875rem; display: block; margin-bottom: 0.25rem;">URL</label>
            <div style="display: flex; gap: 0.5rem; align-items: center;">
                <p style="flex: 1; margin: 0; padding: 0.75rem; background: #f8fafc; border-radius: 0.5rem; border: 1px solid #e2e8f0; word-break: break-all;">${form.url}</p>
                <button onclick="copyToClipboard('${form.url.replace(/'/g, "\\'")}'); return false;" class="btn btn-sm btn-secondary" title="Copy URL">
                    <i class="fas fa-copy"></i>
                </button>
            </div>
        </div>
        <div style="margin-bottom: 1rem;">
            <label style="font-weight: 600; color: #64748b; font-size: 0.875rem; display: block; margin-bottom: 0.25rem;">Tanggal Dibuat</label>
            <p style="margin: 0; padding: 0.75rem; background: #f8fafc; border-radius: 0.5rem; border: 1px solid #e2e8f0;">${new Date(form.date).toLocaleDateString('id-ID', {day: '2-digit', month: 'long', year: 'numeric'})}</p>
        </div>
        <div style="margin-top: 1.5rem; text-align: right;">
            <a href="${form.url}" target="_blank" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-external-link-alt"></i> Buka Form
            </a>
        </div>
    `;
    document.getElementById('detailFormContent').innerHTML = content;
    document.getElementById('detailFormModal').style.display = 'block';
}

function closeDetailFormModal() {
    document.getElementById('detailFormModal').style.display = 'none';
}

// Delete Functions
function deleteLink(index) {
    const link = linksData[index];
    window.showConfirmDialog({
        title: 'Hapus Link',
        message: `Apakah Anda yakin ingin menghapus link "${link.title}"?`,
        confirmText: 'Hapus',
        cancelText: 'Batal',
        danger: true
    }).then(function(ok) {
        if (ok) {
            const fd = new FormData();
            fd.append('id', String(link.id));
            fd.append('category', String(categoryKey));
            fd.append('confirm', '1');
            fd.append('redirect', 'category');
            // csrf_token will be appended automatically by fetchJson wrappers where available.

            if (typeof fetchJson === 'function') {
                fetchJson('<?php echo BASE_URL; ?>/pages/links/delete', { method: 'POST', body: fd })
                    .then(function() {
                        if (typeof reloadLinksTable === 'function') {
                            return reloadLinksTable();
                        }
                    })
                    .catch(function(err) {
                        alert(err && err.message ? err.message : String(err));
                    });
            } else {
                // Fallback: submit a POST form
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '<?php echo BASE_URL; ?>/pages/links/delete?ajax=0';
                [['id', String(link.id)], ['category', String(categoryKey)], ['confirm', '1'], ['redirect', 'category'], ['csrf_token', (window.APP_CSRF_TOKEN || '')]].forEach(([k,v]) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = k;
                    input.value = v;
                    form.appendChild(input);
                });
                document.body.appendChild(form);
                form.submit();
            }
        }
    });
}

function deleteForm(index) {
    const form = formsData[index];
    window.showConfirmDialog({
        title: 'Hapus Form',
        message: `Apakah Anda yakin ingin menghapus form "${form.title}"?`,
        confirmText: 'Hapus',
        cancelText: 'Batal',
        danger: true
    }).then(function(ok) {
        if (ok) {
            const fd = new FormData();
            fd.append('id', String(form.id));
            fd.append('category', String(categoryKey));
            fd.append('confirm', '1');
            fd.append('redirect', 'category');
            // csrf_token will be appended automatically by fetchJson wrappers where available.

            if (typeof fetchJson === 'function') {
                fetchJson('<?php echo BASE_URL; ?>/pages/forms/delete', { method: 'POST', body: fd })
                    .then(function() {
                        if (typeof reloadFormsTable === 'function') {
                            return reloadFormsTable();
                        }
                    })
                    .catch(function(err) {
                        alert(err && err.message ? err.message : String(err));
                    });
            } else {
                // Fallback: submit a POST form
                const formEl = document.createElement('form');
                formEl.method = 'POST';
                formEl.action = '<?php echo BASE_URL; ?>/pages/forms/delete?ajax=0';
                [['id', String(form.id)], ['category', String(categoryKey)], ['confirm', '1'], ['redirect', 'category'], ['csrf_token', (window.APP_CSRF_TOKEN || '')]].forEach(([k,v]) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = k;
                    input.value = v;
                    formEl.appendChild(input);
                });
                document.body.appendChild(formEl);
                formEl.submit();
            }
        }
    });
}

// Copy to Clipboard Function
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        // Show success notification
        const notification = document.createElement('div');
        notification.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #10b981; color: white; padding: 1rem 1.5rem; border-radius: 0.5rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1); z-index: 10000; animation: slideIn 0.3s ease;';
        notification.innerHTML = '<i class="fas fa-check-circle"></i> URL berhasil disalin!';
        document.body.appendChild(notification);
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }, 2000);
    }).catch(function(err) {
        alert('Gagal menyalin URL: ' + err);
    });
}
</script>
