# Security Policy

## Supported Versions

Currently, only the latest major release of the Student Result Management System (SRMS) receives active security updates.

| Version | Supported          | PHP Requirement |
| ------- | ------------------ | --------------- |
| 2.1.x   | :white_check_mark: | PHP 8.2+        |
| 2.0.x   | :white_check_mark: | PHP 7.4+        |
| < 2.0   | :x:                |                 |

## Reporting a Vulnerability

We take the security of SRMS seriously. If you discover a security vulnerability in this project, please **do not** report it by creating a public GitHub issue. 

Instead, please report it via private communication to ensure it can be addressed responsibly before public disclosure.

**To report a vulnerability:**
1. Send an email to: **vishalpawar.dev1128@gmail.com** (or your designated security contact).
2. Include a detailed description of the vulnerability.
3. Provide step-by-step instructions or a proof-of-concept (PoC) on how to reproduce the issue.
4. Allow up to 48 hours for an initial response and acknowledgment.

We will work with you to understand the problem, patch the vulnerability securely, and release an advisory once the patch is live.

## Built-in Security Features
To help administrators and users stay safe, SRMS includes the following robust, out-of-the-box security measures:
- **Bcrypt Password Hashing:** All passwords are automatically salted and hashed.
- **CSRF Protection:** Anti-CSRF tokens generate and validate on all state-changing POST requests.
- **SQL Injection Prevention:** Universal utilization of `mysqli` Prepared Statements for dynamic database queries.
- **XSS Mitigation:** Strict output escaping (`htmlspecialchars`) applied to dynamic front-end content.
- **Activity Auditing:** Extensive logging mechanisms for user and admin events.

Thank you for helping keep the SRMS project safe and secure!
