# Contributing to Student Result Management System (SRMS)

First off, thank you for considering contributing to SRMS! It's people like you that make such tools better for everyone. By participating in this project, you agree to abide by our [Code of Conduct](CODE_OF_CONDUCT.md) and respect the proprietary nature of this codebase.

> **Note:** This project operates under an "All Rights Reserved" proprietary license. Contributions are welcome, but any code you submit will become subject to the project's central proprietary license, and you waive the right to distribute the combined work on your own terms.

## How Can I Contribute?

### Reporting Bugs
If you find a bug, please first check our [Issue Tracker](https://github.com/vishal-dev1128/Student-Result-Management-System/issues) to see if it has already been reported. If not, open a new issue and include:
*   A clear and descriptive title.
*   Steps to reproduce the behavior.
*   The exact error messages or unexpected behavior.
*   Your environment details (PHP version, MySQL version, browser).

*Note: For **security vulnerabilities**, please refer to our [SECURITY.md](SECURITY.md) instead of opening a public issue.*

### Suggesting Enhancements
Enhancement suggestions are tracked as GitHub issues. When suggesting an enhancement, please include:
*   A clear and descriptive title.
*   A detailed description of the proposed functionality.
*   The reasoning behind why this feature would be useful to the majority of users.
*   Mockups or examples if applicable.

### Pull Requests
We accept Pull Requests (PRs) for bug fixes and approved enhancements!

1.  **Fork** the repository and create your branch from `main`.
2.  If you've added code that should be tested, add tests.
3.  Ensure your code adheres to standard PHP standards (PSR-12 recommended) and strictly uses `mysqli` prepared statements for **any** database interactions.
4.  If you've changed APIs or core functions, update the Wiki documentation accordingly.
5.  Open a Pull Request with a clear title and description explaining the *why* and *how* of your changes.

## Development Setup

1.  Clone your fork locally.
2.  Ensure you are using **PHP 8.2+** and **MySQL 8.0+**.
3.  Import the database schema from `database/srms_upgraded.sql`.
4.  Configure `includes/config.php` with your local database credentials.
5.  Create a feature branch: `git checkout -b my-new-feature`
6.  Commit your changes: `git commit -m 'feat: add some feature'`
7.  Push to the branch: `git push origin my-new-feature`
8.  Submit a pull request.

Thank you for your contributions!
