        </div><!-- /admin-content -->
    </main><!-- /admin-main -->
</div><!-- /admin-layout -->

<!-- Sidebar Mobile Overlay -->
<div id="sidebarOverlay" style="
    display:none;position:fixed;inset:0;
    background:rgba(0,0,0,0.5);z-index:1029;
    backdrop-filter:blur(2px);
" onclick="closeMobileSidebar()"></div>

<!-- Core JS -->
<script src="../js/main.js"></script>

<script>
/* ════════════════════════════════════════════════════
   PREMIUM ADMIN JS — v3.0
   ════════════════════════════════════════════════════ */

// ── Theme toggle ──────────────────────────────────────
const themeToggle = document.getElementById('themeToggle');
const themeIcon   = document.getElementById('themeIcon');
const html        = document.documentElement;

function applyTheme(t) {
    html.setAttribute('data-theme', t);
    localStorage.setItem('admin_theme', t);
    themeIcon.className = t === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
}

// Apply on load
applyTheme(localStorage.getItem('admin_theme') || 'light');

themeToggle?.addEventListener('click', () => {
    const newTheme = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    themeToggle.style.transform = 'rotate(20deg) scale(0.85)';
    setTimeout(() => {
        applyTheme(newTheme);
        themeToggle.style.transform = '';
    }, 150);
});

// ── Sidebar collapse (desktop) ─────────────────────────
const layout       = document.getElementById('adminLayout');
const collapseBtn  = document.getElementById('sidebarCollapseBtn');
const collapseIcon = document.getElementById('collapseIcon');

let sidebarCollapsed = localStorage.getItem('sidebar_collapsed') === 'true';

function applySidebarState(animate) {
    if (!animate) layout.style.transition = 'none';
    if (sidebarCollapsed) {
        layout.classList.add('sidebar-collapsed');
        collapseIcon.className = 'fas fa-chevrons-right';
    } else {
        layout.classList.remove('sidebar-collapsed');
        collapseIcon.className = 'fas fa-chevrons-left';
    }
    if (!animate) requestAnimationFrame(() => layout.style.transition = '');
}

applySidebarState(false);

collapseBtn?.addEventListener('click', () => {
    sidebarCollapsed = !sidebarCollapsed;
    localStorage.setItem('sidebar_collapsed', sidebarCollapsed);
    applySidebarState(true);
});

// ── Mobile sidebar ─────────────────────────────────────
const mobileToggle  = document.getElementById('mobileSidebarToggle');
const sidebarEl     = document.getElementById('adminSidebar');
const overlay       = document.getElementById('sidebarOverlay');

function openMobileSidebar() {
    sidebarEl.classList.add('mobile-open');
    overlay.style.display = 'block';
}
function closeMobileSidebar() {
    sidebarEl.classList.remove('mobile-open');
    overlay.style.display = 'none';
}

mobileToggle?.addEventListener('click', () => {
    if (sidebarEl.classList.contains('mobile-open')) {
        closeMobileSidebar();
    } else {
        openMobileSidebar();
    }
});

// ── Notification dropdown ──────────────────────────────
const notifBtn      = document.getElementById('notifBtn');
const notifDropdown = document.getElementById('notifDropdown');

notifBtn?.addEventListener('click', (e) => {
    e.stopPropagation();
    notifDropdown.classList.toggle('open');
});

document.addEventListener('click', (e) => {
    if (notifDropdown && !notifDropdown.contains(e.target) && e.target !== notifBtn) {
        notifDropdown.classList.remove('open');
    }
});

// ── Dropdown menus (admin chip + others) ───────────────
document.querySelectorAll('[data-dropdown-toggle]').forEach(trigger => {
    trigger.addEventListener('click', (e) => {
        e.stopPropagation();
        const menu = trigger.nextElementSibling;
        const isOpen = menu.classList.contains('active');
        document.querySelectorAll('.dropdown-menu.active').forEach(m => m.classList.remove('active'));
        if (!isOpen) menu.classList.add('active');
    });
});
document.addEventListener('click', () => {
    document.querySelectorAll('.dropdown-menu.active').forEach(m => m.classList.remove('active'));
});

// ── Button Ripple Effect ───────────────────────────────
document.addEventListener('click', (e) => {
    const btn = e.target.closest('.btn');
    if (!btn) return;
    const ripple = document.createElement('span');
    ripple.className = 'ripple';
    const rect = btn.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height);
    ripple.style.cssText = `width:${size}px;height:${size}px;left:${e.clientX-rect.left-size/2}px;top:${e.clientY-rect.top-size/2}px;`;
    btn.appendChild(ripple);
    ripple.addEventListener('animationend', () => ripple.remove());
});

// ── Card Glow on Mousemove ─────────────────────────────
document.querySelectorAll('.kpi-card, .chart-card').forEach(card => {
    card.addEventListener('mousemove', (e) => {
        const rect = card.getBoundingClientRect();
        card.style.setProperty('--mouse-x', (e.clientX - rect.left) + 'px');
        card.style.setProperty('--mouse-y', (e.clientY - rect.top)  + 'px');
    });
});

// ── Animated counters (for KPI cards) ─────────────────
function animateCounter(el) {
    const target = parseInt(el.dataset.target || el.textContent, 10);
    if (isNaN(target)) return;
    const duration = 1200;
    const start = performance.now();
    const suffix = el.dataset.suffix || '';
    const prefix = el.dataset.prefix || '';

    function update(now) {
        const elapsed = now - start;
        const progress = Math.min(elapsed / duration, 1);
        const ease = 1 - Math.pow(1 - progress, 3); // ease-out cubic
        el.textContent = prefix + Math.round(target * ease).toLocaleString() + suffix;
        if (progress < 1) requestAnimationFrame(update);
    }
    requestAnimationFrame(update);
}

// Intersection observer — trigger counter when visible
const counterEls = document.querySelectorAll('.kpi-value[data-target]');
if (counterEls.length > 0) {
    const obs = new IntersectionObserver((entries) => {
        entries.forEach(e => {
            if (e.isIntersecting) {
                animateCounter(e.target);
                obs.unobserve(e.target);
            }
        });
    }, { threshold: 0.5 });
    counterEls.forEach(el => obs.observe(el));
}

// ── Page transition (fade out on nav click) ────────────
const pageOverlay = document.getElementById('pageOverlay');

// Always reset overlay on page show (handles bfcache / Back button blue tint)
window.addEventListener('pageshow', () => {
    if (pageOverlay) {
        pageOverlay.style.transition = 'none';
        pageOverlay.style.opacity = '0';
    }
});

document.querySelectorAll('a[href]:not([target="_blank"]):not([href^="#"]):not([href^="javascript"])').forEach(link => {
    link.addEventListener('click', (e) => {
        const href = link.getAttribute('href');
        if (!href || href === '#' || href.startsWith('javascript') || e.ctrlKey || e.metaKey) return;
        // Don't intercept form actions or external links
        if (href.startsWith('http') && !href.includes(location.hostname)) return;
        e.preventDefault();
        pageOverlay.style.opacity = '0.25';
        pageOverlay.style.transition = 'opacity 200ms ease';
        setTimeout(() => { window.location.href = href; }, 180);
    });
});

// ── Resize: auto-close mobile sidebar on desktop ────────
window.addEventListener('resize', () => {
    if (window.innerWidth > 1024) closeMobileSidebar();
});
</script>

<?php if (isset($extra_js)) echo $extra_js; ?>
</body>
</html>
