# 🎓 Student Result Management System (SRMS)
## Product Requirements Document

**Version:** 2.1.0
**Last Updated:** March 2026
**Platform:** Web-based (PHP + MySQL / MariaDB)
**Server:** XAMPP (Apache + MySQL)
**License:** MIT

---

## 📦 Project Overview

A complete, production-ready **Student Result Management System** built with PHP and MySQL. It provides a public-facing result portal, a student login portal, and a full-featured admin panel for managing students, exams, subjects, results, notices, FAQs, and grade configurations.

---

## 🗂️ File Structure

```
srms-master/
├── index.php                   # Homepage with hero section & banner image
├── login.php                   # Admin login page
├── result-search.php           # Public result search & display
├── download-result.php         # PDF result download
├── student/
│   ├── login.php               # Student portal login
│   ├── dashboard.php           # Student dashboard
│   └── results.php             # Student result view
├── admin/
│   ├── dashboard.php           # Admin control panel
│   ├── students.php            # Student management
│   ├── exams.php               # Exam management
│   ├── subjects.php            # Subject management
│   ├── results.php             # Result entry & publishing
│   ├── notices.php             # Notice board management
│   ├── faqs.php                # FAQ management
│   ├── grade-settings.php      # Grade threshold configuration
│   ├── settings.php            # System settings
│   ├── support-tickets.php     # Support ticket management
│   ├── activity-logs.php       # Audit trail
│   └── view-student.php        # Individual student view
├── includes/
│   ├── config.php              # Database & app configuration
│   ├── auth.php                # Authentication logic
│   ├── functions.php           # Utility functions
│   └── security.php            # Security functions (bcrypt, CSRF, XSS)
├── css/
│   ├── variables.css           # CSS custom properties / design tokens
│   ├── reset.css               # CSS reset
│   ├── components.css          # Reusable UI components (cards, buttons, grade badges)
│   ├── layout.css              # Grid & layout utilities
│   ├── animations.css          # Transitions & keyframe animations
│   └── admin-premium.css       # Admin panel specific styles
├── js/
│   └── main.js                 # Theme toggle, navbar, notifications
├── images/
│   └── hero-banner.png         # Hero section background image
├── database/
│   └── srms_upgraded.sql       # Complete database schema + seed data
├── docs/
│   └── screenshots/            # System screenshots for documentation
├── PRD.md                      # This document
└── README.md                   # Setup & usage guide
```

---

## 🗄️ Database Tables (16 Tables + 2 Views)

| Table | Purpose |
|-------|---------|
| `admin_users` | Admin accounts with bcrypt passwords & roles |
| `admin_login` | Legacy login table (kept for compatibility) |
| `students` | Student records with roll number & class |
| `classes` | Class/section definitions |
| `exams` | Exam definitions with publish status |
| `subjects` | Subject list linked to classes |
| `results` | Individual subject-level results per student per exam |
| `result_summary` | Aggregated totals, percentage, overall grade |
| `grade_settings` | Configurable grade thresholds (A+ to F) |
| `notices` | Published announcements with priority |
| `faqs` | Frequently asked questions |
| `support_tickets` | Student support requests |
| `settings` | Key-value system configuration store |
| `password_resets` | Password reset token management |
| `activity_logs` | Full audit trail of admin & student actions |
| `class` | Legacy class table (kept for compatibility) |
| `vw_dashboard_stats` | View: aggregated stats for dashboard |
| `vw_student_results` | View: joined student result data for easy querying |

---

## 👤 Default Login Credentials

| Role | Username | Password |
|------|----------|----------|
| Admin | `admin` | `admin123` |

> ⚠️ **Change the default password immediately after first login.**

---

## 🎯 Core Features

### Public / Student Side
- **Public Result Search** — Search by roll number, class, and exam (no login required)
- **Student Portal** — Secure login for students to view their own results
- **Subject-wise Marks Display** — Marks obtained, total marks, percentage, grade per subject
- **Rich Grade Badges** — Color-coded pill badges (A+ = green, F = red gradient)
- **PDF Download** — Printable/downloadable result slip
- **Notice Board** — Latest published announcements on homepage
- **FAQ Section** — Collapsible FAQ accordion on homepage
- **Dark Mode** — Toggleable, persisted via localStorage
- **Mobile Responsive** — Bottom navigation, responsive grid, 44px touch targets

### Admin Panel
- **Dashboard** — KPI cards (students, exams, results, notices), statistics view
- **Student Management** — Add, edit, delete students; bulk import via CSV/Excel
- **Class Management** — Create and manage classes/sections
- **Exam Management** — Create exams, set dates, publish/unpublish
- **Subject Management** — Define subjects per class
- **Result Entry** — Enter marks per student per subject; bulk upload
- **Result Publishing** — Publish/unpublish results to control student visibility
- **Grade Settings** — Configure custom percentage ranges for each grade (A+ to F)
- **Notice Management** — Create notices with priority levels and expiry dates
- **FAQ Management** — Add/edit/reorder FAQs
- **Support Tickets** — Respond to and manage student support requests
- **System Settings** — Institute name, email, phone, address, logo
- **Activity Logs** — Full audit trail of all actions
- **Role-Based Access** — `super_admin`, `admin`, `staff` roles

---

## 🔒 Security Features

- ✅ **bcrypt password hashing** (cost factor 10–12)
- ✅ **CSRF token validation** on all POST forms
- ✅ **XSS prevention** via `htmlspecialchars()` / `escape()` helper
- ✅ **SQL injection protection** via prepared statements (MySQLi)
- ✅ **Session fingerprinting** (IP + User-Agent hash)
- ✅ **Rate limiting** on login (session-based, configurable lockout)
- ✅ **Input sanitization** for strings, integers, floats, emails, URLs
- ✅ **Role-based access control** on all admin routes
- ✅ **Activity logging** for auditing

---

## 🎨 Design System

### Color Palette
| Token | Value | Use |
|-------|-------|-----|
| Primary | `#1565C0` | Buttons, links, accents |
| Primary Dark | `#0D47A1` | Hover states, gradients |
| Success | `#2E7D32` | A/A+ grades, success alerts |
| Warning | `#F57C00` | C grade, warnings |
| Error | `#C62828` | F grade, error alerts |
| Info | `#0288D1` | Info alerts |

### Typography
- **Headings:** Playfair Display (serif, bold)
- **Body:** Poppins (sans-serif, 300–700 weights)
- **Font source:** Google Fonts CDN

### Grade Badge Colors
| Grade | Background Gradient |
|-------|-------------------|
| A+ | `#1b5e20` → `#2e7d32` (deep green) |
| A | `#2e7d32` → `#43a047` (green) |
| B+ | `#0d47a1` → `#1565c0` (deep blue) |
| B | `#1565c0` → `#1976d2` (blue) |
| C | `#e65100` → `#f57c00` (orange) |
| D | `#bf360c` → `#e64a19` (deep orange) |
| F | `#b71c1c` → `#c62828` (red) |

### Spacing System
Base unit: `8px` — xs: 4px | sm: 8px | md: 16px | lg: 24px | xl: 32px | 2xl: 48px | 3xl: 64px | 4xl: 96px

---

## 📱 Responsive Breakpoints

| Breakpoint | Range | Layout |
|------------|-------|--------|
| Mobile | < 768px | Bottom nav, single column, stacked cards |
| Tablet | 768–1024px | 2-column grids |
| Desktop | > 1024px | 3–4 column grids, full sidebar |

---

## 🛠️ Technology Stack

| Component | Technology |
|-----------|-----------|
| Backend | PHP 7.4+ (OOP-lite, procedural helpers) |
| Database | MySQL 5.7+ / MariaDB 10.3+ |
| Frontend | HTML5, Vanilla CSS3, Vanilla JavaScript |
| Design | Material Design-inspired, custom CSS variables |
| Icons | Font Awesome 6.5.1 |
| Fonts | Google Fonts (Poppins, Playfair Display) |
| Server | Apache (XAMPP on Windows / Linux) |

---

## 🚀 Quick Setup

```sql
-- 1. Create database
CREATE DATABASE srms;

-- 2. Import schema
-- Run database/srms_upgraded.sql in phpMyAdmin or mysql CLI
```

```php
// 3. Edit includes/config.php
define('DB_HOST', 'localhost');
define('DB_NAME', 'srms');
define('DB_USER', 'root');
define('DB_PASS', '');
```

```
// 4. Access the system
http://localhost/srms-master/
Admin Login: http://localhost/srms-master/login.php
Username: admin | Password: admin123
```

---

## 📊 Known Issues Fixed (v2.0)

| Issue | Fix Applied |
|-------|------------|
| `grade_settings` table missing | Table created with 7 default grades (use srms_upgraded.sql) |
| Admin login "Invalid username or password" | Password re-hashed with bcrypt; credential is `admin` / `admin123` |
| `Undefined array key 'marks_obtained'` | Fixed: column is `total_marks_obtained` in `results` table |
| `number_format(): Passing null` deprecation | `formatNumber()` now casts to `(float)` with null coalescing |
| Result header h1 text dark instead of white | Explicit `color: #fff !important` overrides added |
| Grade badges plain/unstyled | Replaced with rich pill-shaped gradient badges |
| CSS `grade-a+` selector broken | PHP now outputs `grade-aplus` / `grade-bplus` CSS class names |

---

## 📈 Scalability

| School Size | Students | Status |
|-------------|---------|--------|
| Small School | 100–500 | ✅ Supported |
| Medium College | 500–5,000 | ✅ Supported |
| Large University | 5,000+ | ✅ Supported (with DB indexing) |

---

## ✅ Quality Checklist

- [x] Mobile responsive (all pages)
- [x] Dark mode support
- [x] Cross-browser compatible
- [x] ARIA labels for accessibility
- [x] SEO meta tags on public pages
- [x] Print-friendly result pages
- [x] Security hardened (bcrypt, CSRF, XSS, SQLi protection)
- [x] Activity audit logging
- [x] Grade system configurable via admin UI
- [x] Hero banner image on homepage

---

## 🎓 Educational Impact

| Stakeholder | Benefit |
|------------|---------|
| Students | 24/7 result access, no manual queries |
| Faculty | Streamlined digital result entry |
| Administration | Data-driven reports and analytics |
| Parents | Transparent progress visibility |
| Institution | Professional image, reduced paperwork |