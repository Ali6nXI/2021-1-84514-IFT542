# IFT542 Security Test Results

## AUTH-01 â€” Valid student login

- **Purpose:** Confirm that a valid fictitious student can authenticate.
- **Precondition:** Apache, MariaDB, and the application are running on localhost.
- **Test account:** `student@example.test`
- **Password:** Fictitious seeded password used locally; not recorded here.
- **Steps:**
  1. Open `http://localhost/ift542_app/login.php`.
  2. Enter the fictitious student credentials.
  3. Submit the login form.
- **Expected result:** The user is redirected to the dashboard and the student role is displayed.
- **Actual result:** Dashboard displayed successfully with the student role.
- **Status:** Passed.
- **Evidence:** `tests/evidence/AUTH-01-valid-login.jpg`


## AUTH-02 â€” Invalid credentials

- **Purpose:** Confirm that invalid credentials are rejected without revealing account details.
- **Precondition:** The application is running on localhost.
- **Test account:** `unknown@example.test`
- **Password:** Incorrect fictitious password; not recorded here.
- **Steps:**
  1. Log out of the application.
  2. Open `http://localhost/ift542_app/login.php`.
  3. Enter the fictitious unknown email and an incorrect password.
  4. Submit the form.
- **Expected result:** The generic message `Invalid email or password.` is displayed.
- **Actual result:** Generic invalid-login message displayed.
- **Status:** Passed.
- **Evidence:** `tests/evidence/AUTH-02-invalid-login.jpg`

## AUTH-03 â€” Password hash storage

- **Purpose:** Confirm that user passwords are stored as slow salted hashes.
- **Precondition:** The `ift542_app` database is available.
- **Steps:**
  1. Open phpMyAdmin.
  2. Select the `ift542_app` database.
  3. Open the SQL tab.
  4. Run the password-hash inspection query.
- **Expected result:** The database contains hash values, not plaintext passwords.
- **Actual result:** Password values were stored as bcrypt hash prefixes and no plaintext passwords were present.
- **Status:** Passed.
- **Evidence:** `tests/evidence/AUTH-03-password-hashes.jpg`

## AUTH-04 â€” Student administrator-access denial

- **Purpose:** Confirm that a student cannot access administrator functions.
- **Precondition:** The application is running and the student account is available.
- **Steps:**
  1. Log in as the fictitious student.
  2. Open `http://localhost/ift542_app/admin/`.
- **Expected result:** The request is rejected with `Access denied.` and an authorization event is logged.
- **Actual result:** Student received `Access denied.` and an authorization-denied event was logged.
- **Status:** Passed.
- **Evidence:** `tests/evidence/AUTH-04-admin-access-denied.jpg`

----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

## SQL-01 â€” Parameterized profile update

- **Purpose:** Confirm that a value containing an apostrophe is handled as data.
- **Precondition:** The student account is available and the application is connected to MariaDB.
- **Test value:** `O'Connor Test`
- **Steps:**
  1. Log in as the fictitious student.
  2. Open `http://localhost/ift542_app/profile.php`.
  3. Change the full name to `O'Connor Test`.
  4. Keep the email unchanged.
  5. Click `Save changes`.
- **Expected result:** The update succeeds without a SQL error, and the exact name is stored and displayed.
- **Actual result:** The name containing an apostrophe was stored and displayed successfully without a SQL error.
- **Status:** Passed.
- **Evidence:** `tests/evidence/SQL-01-parameterized-profile.jpg`


## CSRF-01 â€” Missing CSRF token

- **Purpose:** Confirm that a state-changing request without a CSRF token is rejected.
- **Precondition:** The student is logged in and the profile form is available.
- **Steps:**
  1. Open `http://localhost/ift542_app/profile.php`.
  2. Press `F12` to open browser Developer Tools.
  3. Select the **Elements** tab.
  4. Find the hidden input named `csrf_token`.
  5. Delete that hidden input from the page.
  6. Submit the profile form.
- **Expected result:** The request is rejected with a CSRF-verification error.
- **Actual result:** Profile update was rejected when the CSRF token was removed.
- **Status:** Passed.
- **Evidence:** `tests/evidence/CSRF-01-missing-token.jpg`

## XSS-01 â€” Contextual output encoding

- **Purpose:** Confirm that a displayed profile value is safely encoded.
- **Precondition:** The student is logged in and the profile page is available.
- **Test value:** `<b>Safe Marker</b>`
- **Steps:**
  1. Open `http://localhost/ift542_app/profile.php`.
  2. Set the full name to `<b>Safe Marker</b>`.
  3. Keep the email unchanged.
  4. Save the profile.
  5. View the updated profile or dashboard.
- **Expected result:** The marker appears as literal text and is not rendered in bold.
- **Actual result:** The marker appeared as literal text and was not interpreted as HTML.
- **Status:** Passed.
- **Evidence:** `tests/evidence/XSS-01-output-encoding.jpg`

## UPLOAD-01 â€” Disallowed file type

- **Purpose:** Confirm that unsupported upload types are rejected.
- **Precondition:** The student is logged in and the upload page is available.
- **Test file:** Harmless fictitious `.txt` file.
- **Steps:**
  1. Open `http://localhost/ift542_app/upload.php`.
  2. Select the harmless `.txt` test file.
  3. Submit the upload form.
- **Expected result:** The file is rejected with an allowed-file-type message.
- **Actual result:** The TXT file was rejected and no database record was created.
- **Status:** Passed.
- **Evidence:** `tests/evidence/UPLOAD-01-invalid-type.jpg`


## CONFIG-01 â€” Security response headers

- **Purpose:** Confirm that required security headers are returned by Apache.
- **Precondition:** Apache is running and the application is accessible.
- **Steps:**
  1. Request the login page headers using `curl.exe`.
  2. Inspect the HTTP response headers.
- **Expected result:** The response includes CSP, anti-framing, MIME-sniffing, referrer, and permissions policies.
- **Actual result:** Required security headers were present, Apache version details were reduced, and the X-Powered-By header was absent.
- **Status:** Passed.
- **Evidence:** `tests/evidence/CONFIG-01-security-headers.txt`

----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

## SSRF-01 â€” Approved local mock preview

- **Purpose:** Confirm that the exact approved localhost mock can be previewed.
- **Precondition:** The administrator is logged in and lab mode is enabled.
- **URL:** `http://localhost/ift542_app/mock_target.php`
- **Steps:**
  1. Open `http://localhost/ift542_app/url_preview.php`.
  2. Enter the exact approved mock URL.
  3. Submit the form.
- **Expected result:** The preview succeeds and displays the fictitious JSON response.
- **Actual result:** The exact approved localhost mock returned the fictitious JSON preview successfully.
- **Status:** Passed.
- **Evidence:** `tests/evidence/SSRF-01-approved-local-preview.jpg`

## SSRF-02 â€” Loopback destination rejection

- **Purpose:** Confirm that arbitrary loopback destinations are rejected.
- **Precondition:** The administrator is logged in.
- **Test URL:** `http://127.0.0.1/`
- **Steps:**
  1. Open `http://localhost/ift542_app/url_preview.php`.
  2. Enter the loopback URL.
  3. Submit the form.
- **Expected result:** The destination is rejected with a generic message.
- **Actual result:** The loopback destination was rejected and a `url_preview_rejected` security event was logged.
- **Status:** Passed.
- **Evidence:** `tests/evidence/SSRF-02-loopback-rejected.jpg`

## SSRF-03 â€” Metadata address rejection

- **Purpose:** Confirm that cloud metadata addresses are rejected.
- **Precondition:** The administrator is logged in.
- **Test URL:** `http://169.254.169.254/`
- **Steps:**
  1. Open `http://localhost/ift542_app/url_preview.php`.
  2. Enter the metadata-address test URL.
  3. Submit the form.
- **Expected result:** The destination is rejected with a generic message.
- **Actual result:** The metadata address was rejected and a `url_preview_rejected` event was logged.
- **Status:** Passed.
- **Evidence:** `tests/evidence/SSRF-03-metadata-rejected.jpg`

## SSRF-04 â€” Unsupported URL scheme

- **Purpose:** Confirm that non-HTTP schemes cannot be used for preview requests.
- **Precondition:** The administrator is logged in.
- **Test URL:** `file:///C:/xampp/ift542_nonexistent.txt`
- **Steps:**
  1. Open `http://localhost/ift542_app/url_preview.php`.
  2. Enter the file-scheme test URL.
  3. Submit the form.
- **Expected result:** The destination is rejected before any file access occurs.
- **Actual result:** The file scheme was rejected before any local file access occurred, and a `url_preview_rejected` event was logged.
- **Status:** Passed.
- **Evidence:** `tests/evidence/SSRF-04-unsupported-scheme.jpg`

## CSRF-02 â€” Valid CSRF token

- **Purpose:** Confirm that a state-changing request with a valid CSRF token succeeds.
- **Precondition:** The student is logged in and the profile form contains its hidden CSRF token.
- **Steps:**
  1. Open `http://localhost/ift542_app/profile.php`.
  2. Leave the hidden CSRF token unchanged.
  3. Change the full name to `CSRF Valid Test`.
  4. Keep the email unchanged.
  5. Submit the form.
- **Expected result:** The profile update succeeds.
- **Actual result:** The profile update succeeded when a valid CSRF token was submitted.
- **Status:** Passed.
- **Evidence:** `tests/evidence/CSRF-02-valid-token.jpg`


## UPLOAD-02 â€” Valid document upload

- **Purpose:** Confirm that an allowed document type can be uploaded and recorded.
- **Precondition:** The student is logged in and the upload directory is available.
- **Test file:** Harmless fictitious PDF, JPG, or PNG.
- **Steps:**
  1. Open `http://localhost/ift542_app/upload.php`.
  2. Select an allowed fictitious document.
  3. Submit the upload form.
- **Expected result:** The upload succeeds and metadata appears in the document list.
- **Actual result:** The allowed document was uploaded successfully and its metadata appeared in the document list.
- **Status:** Passed.
- **Evidence:** `tests/evidence/UPLOAD-02-valid-document.jpg`

## ADMIN-01 â€” Administrator course creation

- **Purpose:** Confirm that an administrator can create a course.
- **Precondition:** The administrator account is available.
- **Steps:**
  1. Log in as the administrator.
  2. Open `http://localhost/ift542_app/admin/`.
  3. Add a fictitious course.
- **Test course:** `IFT531 â€” Secure Software Practice`
- **Expected result:** The course is created and appears in the administrator course list.
- **Actual result:** The administrator created the fictitious course and it appeared in the course list.
- **Status:** Passed.
- **Evidence:** `tests/evidence/ADMIN-01-course-creation.jpg`

## ADMIN-02 â€” Administrator enrolment viewing

- **Purpose:** Confirm that an administrator can view registered student enrolments.
- **Precondition:** At least one fictitious student enrolment exists.
- **Steps:**
  1. Log in as the administrator.
  2. Open `http://localhost/ift542_app/admin/`.
  3. Review the Registered Students table.
- **Expected result:** Registered student and course information is displayed to the administrator.
- **Actual result:** The administrator successfully viewed registered student enrolment information.
- **Status:** Passed.
- **Evidence:** `tests/evidence/ADMIN-02-enrolment-view.jpg`

## AUTH-05 â€” Login rate limiting

- **Purpose:** Confirm that repeated failed logins are temporarily blocked.
- **Precondition:** The application is running and the rate-limit window is configured.
- **Steps:**
  1. Submit invalid credentials repeatedly from localhost.
  2. Continue until the rate limit is reached.
  3. Attempt another login.
  4. Wait for the five-minute window to expire.
  5. Attempt a valid login.
- **Expected result:** Excess attempts are blocked temporarily, a security event is logged, and valid login works again after the window expires.
- **Actual result:** Repeated failed attempts were blocked, a `login_rate_limited` event was recorded, and valid login succeeded after the five-minute window expired.
- **Status:** Passed.
- **Evidence:** `tests/evidence/AUTH-05-rate-limit.jpg`

## LOG-01 â€” Security event coverage

- **Purpose:** Confirm that important security events are recorded.
- **Precondition:** The relevant authentication, authorization, and SSRF tests have been performed.
- **Steps:**
  1. Open phpMyAdmin.
  2. Select the `ift542_app` database.
  3. Query the `audit_logs` table by event type and action.
- **Expected result:** Authentication, authorization, validation, and URL-preview events are present.
- **Actual result:** Security events for authentication, rate limiting, authorization denial, and URL-preview validation were present in `audit_logs`.
- **Status:** Passed.
- **Evidence:** `tests/evidence/LOG-01-security-event-coverage.jpg`

## CONFIG-02 â€” Least-privilege database account

- **Purpose:** Confirm that the application uses a dedicated database account with limited permissions.
- **Precondition:** The application is using environment-based database credentials.
- **Steps:**
  1. Open phpMyAdmin.
  2. Run `SHOW GRANTS` for the runtime database account.
  3. Confirm that the application still operates normally.
- **Expected result:** The account has only the required permissions on `ift542_app`; it does not have global administrative privileges.
- **Actual result:** The application runtime account had only SELECT, INSERT, UPDATE, and DELETE permissions on `ift542_app`, and application login continued to work.
- **Status:** Passed.
- **Evidence:** `tests/evidence/CONFIG-02-database-grants.jpg`
