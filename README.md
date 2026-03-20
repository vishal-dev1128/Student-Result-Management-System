# 🎓 Student Result Management System (SRMS)

[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-777bb4?style=for-the-badge&logo=php)](https://www.php.net/)
[![MySQL Version](https://img.shields.io/badge/MySQL-5.7%2B-4479a1?style=for-the-badge&logo=mysql)](https://www.mysql.com/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg?style=for-the-badge)](https://opensource.org/licenses/MIT)
[![Premium UI](https://img.shields.io/badge/UI-Premium-blueviolet?style=for-the-badge)](https://github.com/yourusername/srms)

A modern, high-performance, and secure **Student Result Management System** designed for educational institutions. Built with a focus on professional aesthetics, security, and ease of use.

---

## 📸 Visual Tour

### 🏛️ Landing Page
![Main Page](docs/screenshots/main%20page.png)

### 📊 Admin Dashboard
![Admin Dashboard](docs/screenshots/admin%20dashboard.png)

### 👨‍🎓 Student Experience
| Student Dashboard | Result Search |
|-------------------|---------------|
| ![Student Dashboard](docs/screenshots/student%20dashboard.png) | ![Check Result](docs/screenshots/check%20result.png) |

### 📑 Results & Exports
| Result View | PDF Export |
|-------------|------------|
| ![Student Result](docs/screenshots/student%20result.png) | ![Result PDF](docs/screenshots/result%20pdf.png) |

---

## ✨ Premium Features

### 🏢 Administration
- **Comprehensive Dashboard:** Real-time statistics and KPI cards.
- **Dynamic Management:** Full CRUD for Students, Classes, Exams, and Subjects.
- **Bulk Operations:** Import students and results via CSV/Excel.
- **Result Control:** One-click publishing/unpublishing of results.
- **System Customization:** Configure institute details, logo, and grade thresholds.

### 🎓 Student Portal
- **Secure Access:** Personal login for detailed result history.
- **Public Search:** Quick result check using Roll Number and Class.
- **Visual Feedback:** Color-coded grade badges (A+ to F).
- **Professional Exports:** High-quality PDF result downloads.

### 🎨 Design & Experience
- **Dark Mode Support:** Auto-persisted theme preference.
- **Responsive Design:** Optimized for mobile, tablet, and desktop.
- **Modern Typography:** Uses Google Fonts (Poppins & Playfair Display).
- **Micro-interactions:** Smooth transitions and hover effects.

---

## 🚀 Installation Guide

### 1. Requirements
- **Server:** XAMPP / WAMP / LAMP (Apache + MySQL)
- **PHP:** 7.4 or higher
- **Browser:** Modern browsers (Chrome, Firefox, Safari, Edge)

### 2. Database Setup
1. Start **XAMPP** (Apache & MySQL).
2. Go to [phpMyAdmin](http://localhost/phpmyadmin).
3. Create a new database named `srms`.
4. Import the file `database/srms_upgraded.sql`.

### 3. Configuration
Rename or edit `includes/config.php` with your database credentials:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'srms');
define('DB_USER', 'root');
define('DB_PASS', 'your_password'); // Standard XAMPP is empty ''
```

---

## 🔑 Default Credentials

| Role | Username | Password |
|------|----------|----------|
| **Admin** | `admin` | `admin123` |

> [!WARNING]
> Please change the default password immediately after the first login via the Admin Settings panel.

---

## 🔒 Security & Standards
- **Encryption:** passwords hashed with `bcrypt`.
- **Protection:** Built-in CSRF, XSS, and SQL Injection protection.
- **Audit Trail:** Comprehensive activity logging for all admin actions.
- **Standardized:** Follows modern PHP security practices.

---

## 📂 Project Structure
- **/admin:** Admin control panel and management pages.
- **/student:** Student portal and personal result viewing.
- **/includes:** Core configuration and helper functions.
- **/css:** Design tokens and modular stylesheets.
- **/docs:** Documentation and screenshots.
- **/database:** SQL schema and seed data.

---

## 📄 License
This project is licensed under the **MIT License**. See [LICENSE](LICENSE) for details.

---

*Designed with ❤️ by [Your Name/Organization]*
