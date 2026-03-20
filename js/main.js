/**
 * Main JavaScript
 * SRMS v2.0.0
 * 
 * Core functionality for the application
 */

// ============================================================
// Dark Mode Toggle
// ============================================================

class DarkMode {
    constructor() {
        this.theme = localStorage.getItem('theme') || 'light';
        this.init();
    }

    init() {
        this.applyTheme();
        this.setupToggle();
    }

    applyTheme() {
        document.documentElement.setAttribute('data-theme', this.theme);

        // Update toggle icons
        const toggleButtons = document.querySelectorAll('[data-theme-toggle]');
        toggleButtons.forEach(button => {
            const icon = button.querySelector('i');
            if (icon) {
                if (this.theme === 'dark') {
                    icon.className = 'fas fa-sun';
                } else {
                    icon.className = 'fas fa-moon';
                }
            }
        });
    }

    toggle() {
        this.theme = this.theme === 'light' ? 'dark' : 'light';
        localStorage.setItem('theme', this.theme);
        this.applyTheme();
    }

    setupToggle() {
        const toggleButtons = document.querySelectorAll('[data-theme-toggle]');
        toggleButtons.forEach(button => {
            button.addEventListener('click', () => this.toggle());
        });
    }
}

// Initialize dark mode
const darkMode = new DarkMode();

// ============================================================
// Mobile Menu Toggle
// ============================================================

function initMobileMenu() {
    const toggle = document.querySelector('.navbar-toggle');
    const menu = document.querySelector('.navbar-menu');

    if (toggle && menu) {
        toggle.addEventListener('click', () => {
            menu.classList.toggle('active');
        });

        // Close menu when clicking outside
        document.addEventListener('click', (e) => {
            if (!toggle.contains(e.target) && !menu.contains(e.target)) {
                menu.classList.remove('active');
            }
        });
    }
}

// ============================================================
// Dropdown Functionality
// ============================================================

function initDropdowns() {
    // Toggle dropdown on click
    document.addEventListener('click', (e) => {
        const toggle = e.target.closest('.dropdown-toggle') || e.target.closest('[data-dropdown-toggle]');

        if (toggle) {
            e.preventDefault();
            e.stopPropagation();

            const dropdown = toggle.closest('.dropdown');
            if (!dropdown) return;

            const menu = dropdown.querySelector('.dropdown-menu');
            if (!menu) return;

            // Close other dropdowns
            document.querySelectorAll('.dropdown-menu.active').forEach(m => {
                if (m !== menu) m.classList.remove('active');
            });

            menu.classList.toggle('active');
        } else {
            // Close all dropdowns when clicking elsewhere
            document.querySelectorAll('.dropdown-menu.active').forEach(menu => {
                menu.classList.remove('active');
            });
        }
    });

    // Close on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.dropdown-menu.active').forEach(menu => {
                menu.classList.remove('active');
            });
        }
    });
}

// ============================================================
// Modal Functionality
// ============================================================

class Modal {
    constructor(modalId) {
        this.modal = document.getElementById(modalId);
        if (this.modal) {
            this.init();
        }
    }

    init() {
        const closeButtons = this.modal.querySelectorAll('[data-modal-close]');
        const backdrop = this.modal.querySelector('.modal-backdrop');

        closeButtons.forEach(button => {
            button.addEventListener('click', () => this.close());
        });

        if (backdrop) {
            backdrop.addEventListener('click', () => this.close());
        }

        // Close on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.modal.classList.contains('active')) {
                this.close();
            }
        });
    }

    open() {
        this.modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    close() {
        this.modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// ============================================================
// Form Validation
// ============================================================

class FormValidator {
    constructor(form) {
        this.form = form;
        this.init();
    }

    init() {
        this.form.addEventListener('submit', (e) => {
            if (!this.validate()) {
                e.preventDefault();
            }
        });

        // Real-time validation
        const inputs = this.form.querySelectorAll('.form-control');
        inputs.forEach(input => {
            input.addEventListener('blur', () => this.validateField(input));
            input.addEventListener('input', () => this.clearError(input));
        });
    }

    validate() {
        let isValid = true;
        const inputs = this.form.querySelectorAll('.form-control[required]');

        inputs.forEach(input => {
            if (!this.validateField(input)) {
                isValid = false;
            }
        });

        return isValid;
    }

    validateField(input) {
        const value = input.value.trim();
        const type = input.type;
        let isValid = true;
        let errorMessage = '';

        // Required validation
        if (input.hasAttribute('required') && !value) {
            isValid = false;
            errorMessage = 'This field is required';
        }

        // Email validation
        else if (type === 'email' && value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                isValid = false;
                errorMessage = 'Please enter a valid email address';
            }
        }

        // Number validation
        else if (type === 'number' && value) {
            const min = input.getAttribute('min');
            const max = input.getAttribute('max');

            if (min && parseFloat(value) < parseFloat(min)) {
                isValid = false;
                errorMessage = `Value must be at least ${min}`;
            }

            if (max && parseFloat(value) > parseFloat(max)) {
                isValid = false;
                errorMessage = `Value must be at most ${max}`;
            }
        }

        // Password validation
        else if (input.name === 'password' && value) {
            if (value.length < 8) {
                isValid = false;
                errorMessage = 'Password must be at least 8 characters';
            }
        }

        // Confirm password validation
        else if (input.name === 'confirm_password' && value) {
            const password = this.form.querySelector('[name="password"]');
            if (password && value !== password.value) {
                isValid = false;
                errorMessage = 'Passwords do not match';
            }
        }

        this.showValidation(input, isValid, errorMessage);
        return isValid;
    }

    showValidation(input, isValid, errorMessage) {
        const formGroup = input.closest('.form-group');
        let errorElement = formGroup.querySelector('.form-error');

        if (isValid) {
            input.classList.remove('is-invalid');
            input.classList.add('is-valid');
            if (errorElement) errorElement.remove();
        } else {
            input.classList.remove('is-valid');
            input.classList.add('is-invalid');

            if (!errorElement) {
                errorElement = document.createElement('span');
                errorElement.className = 'form-error';
                input.parentNode.appendChild(errorElement);
            }
            errorElement.textContent = errorMessage;
        }
    }

    clearError(input) {
        input.classList.remove('is-invalid', 'is-valid');
        const formGroup = input.closest('.form-group');
        const errorElement = formGroup.querySelector('.form-error');
        if (errorElement) errorElement.remove();
    }
}

// ============================================================
// Notification System
// ============================================================

class Notification {
    static show(message, type = 'info', duration = 5000) {
        const container = this.getContainer();
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} fade-in`;
        notification.style.cssText = 'margin-bottom: 1rem; animation: slideInRight 0.3s ease-out;';

        const icon = this.getIcon(type);
        notification.innerHTML = `
            <span class="alert-icon">${icon}</span>
            <div class="alert-content">${message}</div>
        `;

        container.appendChild(notification);

        // Auto remove
        setTimeout(() => {
            notification.classList.add('fade-out');
            setTimeout(() => notification.remove(), 300);
        }, duration);
    }

    static getContainer() {
        let container = document.getElementById('notification-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'notification-container';
            container.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; max-width: 400px;';
            document.body.appendChild(container);
        }
        return container;
    }

    static getIcon(type) {
        const icons = {
            success: '<i class="fa fa-check-circle"></i>',
            error: '<i class="fa fa-times-circle"></i>',
            warning: '<i class="fa fa-exclamation-triangle"></i>',
            info: '<i class="fa fa-info-circle"></i>'
        };
        return icons[type] || icons.info;
    }
}

// ============================================================
// AJAX Helper
// ============================================================

async function fetchData(url, options = {}) {
    try {
        const response = await fetch(url, {
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            },
            ...options
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        return await response.json();
    } catch (error) {
        console.error('Fetch error:', error);
        Notification.show('An error occurred. Please try again.', 'error');
        throw error;
    }
}

// ============================================================
// Confirmation Dialog  (custom – replaces native confirm())
// ============================================================

function confirmDialog(message, onConfirm, onCancel, options) {
    options = options || {};
    var title = options.title || 'Confirm Action';
    var confirmText = options.confirmText || 'Delete';
    var cancelText = options.cancelText || 'Cancel';
    var isDanger = options.danger !== false; // default true

    // Remove any existing dialog
    var existing = document.getElementById('srms-confirm-overlay');
    if (existing) existing.remove();

    // Inject styles once
    if (!document.getElementById('srms-confirm-styles')) {
        var style = document.createElement('style');
        style.id = 'srms-confirm-styles';
        style.textContent = [
            '#srms-confirm-overlay {',
            '  position:fixed;inset:0;z-index:99999;',
            '  display:flex;align-items:center;justify-content:center;',
            '  background:rgba(0,0,0,0.55);',
            '  backdrop-filter:blur(4px);',
            '  animation:srms-fade-in .18s ease;',
            '}',
            '@keyframes srms-fade-in{from{opacity:0}to{opacity:1}}',
            '#srms-confirm-box {',
            '  background:var(--color-surface,#fff);',
            '  border:1px solid var(--color-border,#e2e8f0);',
            '  border-radius:16px;',
            '  box-shadow:0 24px 64px rgba(0,0,0,0.3);',
            '  max-width:420px;width:90%;',
            '  animation:srms-pop-in .22s cubic-bezier(.34,1.56,.64,1);',
            '  overflow:hidden;',
            '}',
            '@keyframes srms-pop-in{from{opacity:0;transform:scale(.88) translateY(12px)}to{opacity:1;transform:scale(1) translateY(0)}}',
            '#srms-confirm-box .srms-c-head {',
            '  padding:24px 24px 0;display:flex;align-items:center;gap:12px;',
            '}',
            '#srms-confirm-box .srms-c-icon {',
            '  width:44px;height:44px;border-radius:50%;flex-shrink:0;',
            '  display:flex;align-items:center;justify-content:center;font-size:20px;',
            '  background:rgba(239,68,68,.12);color:#ef4444;',
            '}',
            '#srms-confirm-box .srms-c-icon.safe{background:rgba(79,70,229,.12);color:#4f46e5;}',
            '#srms-confirm-box .srms-c-title {',
            '  font-size:16px;font-weight:700;',
            '  color:var(--color-text,#1a202c);margin:0;',
            '}',
            '#srms-confirm-box .srms-c-body {',
            '  padding:12px 24px 0;font-size:14px;',
            '  color:var(--color-text-secondary,#64748b);line-height:1.6;',
            '}',
            '#srms-confirm-box .srms-c-foot {',
            '  padding:20px 24px;display:flex;justify-content:flex-end;gap:10px;',
            '}',
            '#srms-confirm-box .srms-btn {',
            '  padding:9px 20px;border-radius:8px;border:none;cursor:pointer;',
            '  font-size:14px;font-weight:600;font-family:inherit;',
            '  transition:all .15s ease;',
            '}',
            '#srms-confirm-box .srms-btn-cancel {',
            '  background:var(--color-surface-variant,#f1f5f9);',
            '  color:var(--color-text-secondary,#64748b);',
            '}',
            '#srms-confirm-box .srms-btn-cancel:hover{filter:brightness(.93);}',
            '#srms-confirm-box .srms-btn-confirm {',
            '  background:#ef4444;color:#fff;',
            '  box-shadow:0 4px 12px rgba(239,68,68,.35);',
            '}',
            '#srms-confirm-box .srms-btn-confirm.safe{background:#4f46e5;box-shadow:0 4px 12px rgba(79,70,229,.35);}',
            '#srms-confirm-box .srms-btn-confirm:hover{transform:translateY(-1px);filter:brightness(1.08);}',
        ].join('');
        document.head.appendChild(style);
    }

    var overlay = document.createElement('div');
    overlay.id = 'srms-confirm-overlay';

    var iconClass = isDanger ? '' : ' safe';
    var iconSymbol = isDanger ? '<i class="fas fa-trash"></i>' : '<i class="fas fa-question-circle"></i>';
    var btnClass = isDanger ? '' : ' safe';

    overlay.innerHTML =
        '<div id="srms-confirm-box">' +
        '<div class="srms-c-head">' +
        '<div class="srms-c-icon' + iconClass + '">' + iconSymbol + '</div>' +
        '<p class="srms-c-title">' + title + '</p>' +
        '</div>' +
        '<div class="srms-c-body"><p>' + message + '</p></div>' +
        '<div class="srms-c-foot">' +
        '<button class="srms-btn srms-btn-cancel" id="srms-cancel-btn">' + cancelText + '</button>' +
        '<button class="srms-btn srms-btn-confirm' + btnClass + '" id="srms-confirm-btn">' + confirmText + '</button>' +
        '</div>' +
        '</div>';

    document.body.appendChild(overlay);

    function close() { overlay.remove(); }

    document.getElementById('srms-confirm-btn').addEventListener('click', function () {
        close();
        if (onConfirm) onConfirm();
    });
    document.getElementById('srms-cancel-btn').addEventListener('click', function () {
        close();
        if (onCancel) onCancel();
    });
    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) { close(); if (onCancel) onCancel(); }
    });
    document.addEventListener('keydown', function esc(e) {
        if (e.key === 'Escape') { close(); document.removeEventListener('keydown', esc); }
    });
}

// ============================================================
// Table Row Actions
// ============================================================

function initTableActions() {
    // Delete confirmation
    document.querySelectorAll('[data-action="delete"]').forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            const url = this.getAttribute('href') || this.dataset.url;
            const message = this.dataset.message || 'Are you sure you want to delete this item?';

            confirmDialog(message, () => {
                window.location.href = url;
            });
        });
    });
}

// ============================================================
// Search/Filter
// ============================================================

function initSearch() {
    const searchInput = document.querySelector('[data-search]');
    if (!searchInput) return;

    const table = document.querySelector(searchInput.dataset.search);
    if (!table) return;

    searchInput.addEventListener('input', function () {
        const searchTerm = this.value.toLowerCase();
        const rows = table.querySelectorAll('tbody tr');

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });
}

// ============================================================
// Auto-dismiss Flash Messages
// ============================================================

function initFlashMessages() {
    const flashMessages = document.querySelectorAll('.flash-message');
    flashMessages.forEach(message => {
        setTimeout(() => {
            message.classList.add('fade-out');
            setTimeout(() => message.remove(), 300);
        }, 5000);
    });
}

// ============================================================
// Print Functionality
// ============================================================

function printPage() {
    window.print();
}

// ============================================================
// Initialize on DOM Ready
// ============================================================

document.addEventListener('DOMContentLoaded', () => {
    // Initialize components
    initMobileMenu();
    initDropdowns();
    initTableActions();
    initSearch();
    initFlashMessages();

    // Initialize form validation
    document.querySelectorAll('form[data-validate]').forEach(form => {
        new FormValidator(form);
    });

    // Add page enter animation
    document.body.classList.add('page-enter');
});

// ============================================================
// Utility Functions
// ============================================================

// Format number
function formatNumber(num, decimals = 2) {
    return parseFloat(num).toFixed(decimals);
}

// Format date
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-IN', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

// Debounce function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Export for use in other scripts
window.SRMS = {
    DarkMode: darkMode,
    Modal,
    FormValidator,
    Notification,
    fetchData,
    confirmDialog,
    formatNumber,
    formatDate,
    debounce
};
