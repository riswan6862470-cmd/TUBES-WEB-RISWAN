// ============================================================
// Smart Waste Management System - Main JavaScript
// ============================================================

// Sidebar Toggle
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    if (sidebar) {
        sidebar.classList.toggle('open');
        if (overlay) overlay.classList.toggle('open');
    }
}

// Close sidebar on outside click
document.addEventListener('click', function(e) {
    const sidebar = document.getElementById('sidebar');
    const toggle = document.getElementById('sidebarToggle');
    const overlay = document.getElementById('sidebarOverlay');
    if (sidebar && sidebar.classList.contains('open')) {
        if (!sidebar.contains(e.target) && e.target !== toggle) {
            sidebar.classList.remove('open');
            if (overlay) overlay.classList.remove('open');
        }
    }
});

// ============================================================
// Image Preview on Upload
// ============================================================
function initImagePreview(inputId, previewId) {
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);
    if (!input || !preview) return;

    input.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const allowed = ['image/jpeg', 'image/jpg', 'image/png'];
            if (!allowed.includes(file.type)) {
                showAlert('Format file harus JPG, JPEG, atau PNG!', 'error');
                this.value = '';
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                showAlert('Ukuran file maksimal 5MB!', 'error');
                this.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML = `<img src="${e.target.result}" class="img-fluid rounded" style="max-height:200px; object-fit:cover; width:100%;">
                    <div class="mt-2 text-center">
                        <small class="text-muted"><i class="bi bi-check-circle-fill text-success me-1"></i>${file.name}</small>
                    </div>`;
                preview.classList.add('has-image');
            };
            reader.readAsDataURL(file);
        }
    });

    // Allow clicking on preview to change
    preview.addEventListener('click', () => input.click());
}

// ============================================================
// Alert Toast System
// ============================================================
function showAlert(message, type = 'info', duration = 4000) {
    let container = document.getElementById('alert-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'alert-container';
        container.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-width: 380px;
        `;
        document.body.appendChild(container);
    }

    const icons = {
        success: 'bi-check-circle-fill',
        error: 'bi-x-circle-fill',
        warning: 'bi-exclamation-triangle-fill',
        info: 'bi-info-circle-fill'
    };

    const colors = {
        success: '#15803d',
        error: '#dc2626',
        warning: '#b45309',
        info: '#2563eb'
    };

    const bgs = {
        success: '#f0fdf4',
        error: '#fef2f2',
        warning: '#fffbeb',
        info: '#eff6ff'
    };

    const toast = document.createElement('div');
    toast.style.cssText = `
        background: ${bgs[type]};
        color: ${colors[type]};
        border-left: 4px solid ${colors[type]};
        border-radius: 10px;
        padding: 14px 18px;
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 10px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.12);
        animation: slideInRight 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
        user-select: none;
    `;

    toast.innerHTML = `<i class="bi ${icons[type]}" style="font-size:16px;flex-shrink:0;"></i><span>${message}</span>`;
    toast.addEventListener('click', () => removeToast(toast));

    container.appendChild(toast);

    const styleEl = document.getElementById('toast-style');
    if (!styleEl) {
        const s = document.createElement('style');
        s.id = 'toast-style';
        s.textContent = `
            @keyframes slideInRight { from { opacity:0; transform:translateX(30px); } to { opacity:1; transform:translateX(0); } }
            @keyframes slideOutRight { from { opacity:1; transform:translateX(0); } to { opacity:0; transform:translateX(30px); } }
        `;
        document.head.appendChild(s);
    }

    setTimeout(() => removeToast(toast), duration);
}

function removeToast(el) {
    if (!el || !el.parentNode) return;
    el.style.animation = 'slideOutRight 0.3s ease forwards';
    setTimeout(() => el.remove(), 300);
}

// ============================================================
// Confirm Delete
// ============================================================
function confirmDelete(formId, itemName) {
    if (confirm(`Apakah Anda yakin ingin menghapus "${itemName}"?\nTindakan ini tidak dapat dibatalkan.`)) {
        const form = document.getElementById(formId);
        if (form) form.submit();
    }
}

// ============================================================
// Form Validation
// ============================================================
function validateLoginForm() {
    const username = document.getElementById('username');
    const password = document.getElementById('password');
    let valid = true;

    clearErrors();

    if (!username || !username.value.trim()) {
        showFieldError(username, 'Username tidak boleh kosong');
        valid = false;
    }
    if (!password || !password.value.trim()) {
        showFieldError(password, 'Password tidak boleh kosong');
        valid = false;
    }

    return valid;
}

function validateRegisterForm() {
    const fields = {
        nama: 'Nama lengkap tidak boleh kosong',
        username: 'Username tidak boleh kosong',
        password: 'Password tidak boleh kosong',
        alamat: 'Alamat tidak boleh kosong',
        no_hp: 'Nomor HP tidak boleh kosong'
    };

    let valid = true;
    clearErrors();

    for (const [id, msg] of Object.entries(fields)) {
        const el = document.getElementById(id);
        if (el && !el.value.trim()) {
            showFieldError(el, msg);
            valid = false;
        }
    }

    const password = document.getElementById('password');
    if (password && password.value.trim().length < 6) {
        showFieldError(password, 'Password minimal 6 karakter');
        valid = false;
    }

    const phone = document.getElementById('no_hp');
    if (phone && phone.value.trim() && !/^[0-9]{10,13}$/.test(phone.value.trim())) {
        showFieldError(phone, 'Nomor HP tidak valid (10-13 digit angka)');
        valid = false;
    }

    return valid;
}

function validateWasteForm() {
    const fields = {
        nama_sampah: 'Nama sampah tidak boleh kosong',
        kategori: 'Kategori harus dipilih',
        berat: 'Berat tidak boleh kosong',
        lokasi_pengumpulan: 'Lokasi tidak boleh kosong',
        tanggal_input: 'Tanggal tidak boleh kosong'
    };

    let valid = true;
    clearErrors();

    for (const [id, msg] of Object.entries(fields)) {
        const el = document.getElementById(id);
        if (el && !el.value.trim()) {
            showFieldError(el, msg);
            valid = false;
        }
    }

    const berat = document.getElementById('berat');
    if (berat && berat.value && (isNaN(parseFloat(berat.value)) || parseFloat(berat.value) <= 0)) {
        showFieldError(berat, 'Berat harus berupa angka positif');
        valid = false;
    }

    return valid;
}

function validateBinForm() {
    const fields = {
        lokasi: 'Lokasi tidak boleh kosong',
        kapasitas_max: 'Kapasitas maksimal tidak boleh kosong',
        tingkat_kepenuhan: 'Tingkat kepenuhan tidak boleh kosong'
    };

    let valid = true;
    clearErrors();

    for (const [id, msg] of Object.entries(fields)) {
        const el = document.getElementById(id);
        if (el && !el.value.trim()) {
            showFieldError(el, msg);
            valid = false;
        }
    }

    const cap = document.getElementById('kapasitas_max');
    const fill = document.getElementById('tingkat_kepenuhan');

    if (cap && cap.value && (isNaN(parseInt(cap.value)) || parseInt(cap.value) <= 0)) {
        showFieldError(cap, 'Kapasitas harus angka positif');
        valid = false;
    }

    if (fill && fill.value) {
        const v = parseInt(fill.value);
        if (isNaN(v) || v < 0 || v > 100) {
            showFieldError(fill, 'Tingkat kepenuhan antara 0-100');
            valid = false;
        }
    }

    return valid;
}

function validateReportForm() {
    const fields = {
        lokasi: 'Lokasi tidak boleh kosong',
        jenis_sampah: 'Jenis sampah tidak boleh kosong',
        deskripsi: 'Deskripsi tidak boleh kosong'
    };

    let valid = true;
    clearErrors();

    for (const [id, msg] of Object.entries(fields)) {
        const el = document.getElementById(id);
        if (el && !el.value.trim()) {
            showFieldError(el, msg);
            valid = false;
        }
    }

    return valid;
}

function showFieldError(el, msg) {
    if (!el) return;
    el.classList.add('is-invalid');
    let errEl = el.nextElementSibling;
    if (!errEl || !errEl.classList.contains('invalid-feedback')) {
        errEl = document.createElement('div');
        errEl.className = 'invalid-feedback';
        el.parentNode.insertBefore(errEl, el.nextSibling);
    }
    errEl.textContent = msg;
}

function clearErrors() {
    document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    document.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
}

// ============================================================
// Bin Level Live Update
// ============================================================
function updateBinStatus() {
    const fillInput = document.getElementById('tingkat_kepenuhan');
    const statusBadge = document.getElementById('status-preview');
    if (!fillInput || !statusBadge) return;

    fillInput.addEventListener('input', function() {
        const val = parseInt(this.value) || 0;
        if (val >= 80) {
            statusBadge.className = 'badge-status badge-penuh';
            statusBadge.innerHTML = '<i class="bi bi-exclamation-circle-fill me-1"></i>PENUH';
        } else {
            statusBadge.className = 'badge-status badge-normal';
            statusBadge.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>NORMAL';
        }
    });
}

// ============================================================
// Auto-dismiss alerts
// ============================================================
document.addEventListener('DOMContentLoaded', function () {
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            if (bsAlert) bsAlert.close();
        }, 5000);
    });

    // Init bin status preview
    updateBinStatus();

    // Init image preview if elements exist
    initImagePreview('foto', 'foto-preview');

    // Animate stat values
    animateCounters();
});

// ============================================================
// Animate stat counter numbers
// ============================================================
function animateCounters() {
    const counters = document.querySelectorAll('[data-count]');
    counters.forEach(counter => {
        const target = parseFloat(counter.getAttribute('data-count'));
        const duration = 1200;
        const start = performance.now();
        const isFloat = target % 1 !== 0;

        function update(now) {
            const elapsed = now - start;
            const progress = Math.min(elapsed / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            const value = target * eased;
            counter.textContent = isFloat ? value.toFixed(1) : Math.round(value);
            if (progress < 1) requestAnimationFrame(update);
        }

        requestAnimationFrame(update);
    });
}

// ============================================================
// Search / Filter Table
// ============================================================
function filterTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    if (!input || !table) return;

    input.addEventListener('keyup', function() {
        const filter = this.value.toLowerCase();
        const rows = table.querySelectorAll('tbody tr');
        let count = 0;
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            if (text.includes(filter)) {
                row.style.display = '';
                count++;
            } else {
                row.style.display = 'none';
            }
        });
        const emptyRow = table.querySelector('.no-results');
        if (count === 0) {
            if (!emptyRow) {
                const tr = document.createElement('tr');
                tr.className = 'no-results';
                tr.innerHTML = `<td colspan="100" class="text-center py-4 text-muted"><i class="bi bi-search me-2"></i>Tidak ada data yang cocok</td>`;
                table.querySelector('tbody').appendChild(tr);
            }
        } else if (emptyRow) {
            emptyRow.remove();
        }
    });
}

// ============================================================
// Toggle Password Visibility
// ============================================================
function togglePassword(inputId, btnId) {
    const input = document.getElementById(inputId);
    const btn = document.getElementById(btnId);
    if (!input || !btn) return;

    btn.addEventListener('click', function() {
        if (input.type === 'password') {
            input.type = 'text';
            btn.innerHTML = '<i class="bi bi-eye-slash"></i>';
        } else {
            input.type = 'password';
            btn.innerHTML = '<i class="bi bi-eye"></i>';
        }
    });
}
