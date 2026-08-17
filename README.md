# MATRICNO_IFT542 â€” Student Registration Web Application

> Replace `2021/1/84514cf` with the actual matriculation number before submission.

## Project overview

This project is a localhost-only PHP/MariaDB student-registration web application developed for the IFT 542 practical assignment.

The application provides:

- Student and administrator authentication.
- Student profile updates.
- Course listing and registration.
- Administrator course creation and enrolment review.
- Fictitious document upload.
- Local-only URL preview/import for SSRF-control testing.
- Security-event logging.

No public website, FUT Minna system, external database, or third-party service was tested. All data used by the project is fictitious.

## Technology and environment

- Windows with XAMPP.
- Apache HTTP Server.
- PHP 8.x.
- MariaDB/MySQL-compatible database.
- phpMyAdmin for local database administration.
- PDO with emulated prepares disabled.

The application was developed and tested on `localhost` only.

## Local installation

### 1. Install and start XAMPP

- Install XAMPP for Windows.
- Start Apache.
- Start MySQL/MariaDB.

### 2. Place the project

Place the project folder at:

```text
C:\xampp\htdocs\ift542_app
```

### 3. Create the database

- Open `http://localhost/phpmyadmin/`.
- Create a database named `ift542_app`.
- Import:

```text
database/schema.sql
```

### 4. Create the runtime database account

The application must not use the XAMPP `root` account at runtime. Create a dedicated local account with only the required permissions:

- `SELECT`
- `INSERT`
- `UPDATE`
- `DELETE`

Do not commit the database password to GitHub.

### 5. Configure local environment variables

Set local environment variables using a private password:

```text
IFT542_DB_USER=ift542_runtime
IFT542_DB_PASSWORD=<local-only-password>
```

The actual password must not appear in this repository, README, screenshots, or report.

For the isolated SSRF mock demonstration only, enable:

```text
IFT542_SSRF_LAB=1
```

This lab-only setting permits one exact localhost mock URL. It must be disabled or clearly documented before final packaging.

Restart the XAMPP Control Panel after changing environment variables.

### 6. Open the application

```text
http://localhost/ift542_app/
```

Login page:

```text
http://localhost/ift542_app/login.php
```

## Main routes

| Route | Purpose |
|---|---|
| `login.php` | Student and administrator login |
| `logout.php` | Session logout and invalidation |
| `dashboard.php` | Authenticated user dashboard |
| `profile.php` | Student profile update |
| `courses.php` | Student course listing and registration |
| `upload.php` | Secure document upload |
| `admin/` | Administrator course and enrolment functions |
| `url_preview.php` | Local-only URL preview and SSRF-control demonstration |
| `mock_target.php` | Fictitious localhost-only preview target |

## Security controls implemented

- Password hashing and secure password verification.
- PDO prepared statements.
- Input type, length, and format validation.
- Generic authentication errors.
- Session-ID regeneration after login.
- HttpOnly and SameSite session cookies.
- Login rate limiting.
- Server-side role authorization.
- CSRF tokens on state-changing forms.
- Contextual output encoding.
- Content Security Policy and other security headers.
- Upload MIME-type and size validation.
- Random upload filenames.
- File storage outside the web root.
- SSRF destination allowlisting and private-address rejection.
- Redirects disabled for URL preview requests.
- Response-size and timeout limits for URL preview.
- Security-event logging without passwords or tokens.
- Protected configuration, source-support, and SQL files.
- Apache and PHP version-disclosure reduction.
- Dedicated least-privilege runtime database account.

## Test results

Detailed procedures are recorded in `tests/test-results.md`. Evidence is stored in `tests/evidence/`.

| Test ID | Test | Status |
|---|---|---|
| AUTH-01 | Valid student login | Passed |
| AUTH-02 | Invalid credentials | Passed |
| AUTH-03 | Password hash storage | Passed |
| AUTH-04 | Student administrator-access denial | Passed |
| AUTH-05 | Login rate limiting | Passed |
| SQL-01 | Parameterized profile update | Passed |
| CSRF-01 | Missing CSRF token rejection | Passed |
| CSRF-02 | Valid CSRF token | Passed |
| XSS-01 | Contextual output encoding | Passed |
| UPLOAD-01 | Disallowed file type | Passed |
| UPLOAD-02 | Valid document upload | Passed |
| CONFIG-01 | Security response headers | Passed |
| SSRF-01 | Approved local mock preview | Passed |
| SSRF-02 | Loopback destination rejection | Passed |
| SSRF-03 | Metadata address rejection | Passed |
| SSRF-04 | Unsupported URL scheme | Passed |
| ADMIN-01 | Administrator course creation | Passed |
| ADMIN-02 | Administrator enrolment viewing | Passed |
| LOG-01 | Security-event coverage | Passed |
| CONFIG-02 | Least-privilege database account evidence | Deferred â€” complete before submission |

## Evidence and reports

The `docs/` directory should contain:

- Detailed IFT 542 report.
- Data-flow diagram with trust boundaries.
- STRIDE worksheet.
- Risk register.
- Top-three risk justifications.
- Security-header evidence.
- Redacted security logs.
- Incident-response runbook.
- Fictitious incident record.
- Signed ethics statement.

## Data-flow and risk files

- `docs/IFT542_data_flow_diagram.svg`
- `docs/IFT542_STRIDE_Risk_Register.xlsx`
- `docs/IFT542_STRIDE_Risk_Register_Guide.md`

## Incident response

The final submission must include a one-page runbook covering:

1. Preparation.
2. Detection and analysis.
3. Containment.
4. Eradication.
5. Recovery.
6. Lessons learned.

## Known limitations

- The application is a local academic prototype, not a production deployment.
- Rate limiting is based on the client IP; localhost users may share the same address.
- The SSRF lab exception is restricted to one exact localhost mock URL and must not be enabled for an exposed deployment.
- No external services are required or contacted.
- The runtime database account and environment variables must be verified before final submission.

## Privacy and submission safety

Do not commit or submit:

- Database passwords.
- Environment files containing secrets.
- Session tokens.
- API keys.
- Real personal data.
- Real identity documents.
- Unredacted logs or screenshots.
- Private upload-storage contents.

All submitted screenshots and logs must be readable and redacted.

