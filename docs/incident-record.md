# Fictitious Security Incident Record

## Incident identification

- **Incident ID:** IR-001
- **Application:** IFT542 Student Registration Web Application
- **Environment:** Localhost
- **Severity:** Medium
- **Status:** Closed after verification
- **Detection source:** `audit_logs`
- **Data classification:** Fictitious academic data

## Incident summary

During authorized localhost testing, the application recorded repeated failed-login attempts, a temporary rate-limit event, and rejected URL-preview requests.

The events were generated using fictitious accounts and local test URLs. No public website, third-party service, external database, or FUT Minna system was contacted.

## Detection

The incident was detected through the following security events:

- `login_failure`
- `login_rate_limited`
- `url_preview_rejected`

The events contained timestamps, local IP information, event types, and safe reasons. No passwords, session tokens, CSRF tokens, or API keys were recorded.

## Timeline

- **[Insert test date/time]:** Repeated invalid login attempts were submitted using a fictitious account.
- **[Insert test date/time]:** The login rate limiter blocked further attempts after the configured threshold.
- **[Insert test date/time]:** A loopback URL was submitted to the URL-preview feature and rejected.
- **[Insert test date/time]:** A metadata-address URL was submitted and rejected.
- **[Insert test date/time]:** An unsupported file-scheme URL was submitted and rejected.
- **[Insert test date/time]:** Audit logs were reviewed and the security controls were confirmed to be working.

## Affected assets

- Authentication endpoint.
- URL-preview endpoint.
- Security-event log table.
- Fictitious test accounts only.

## Impact assessment

- No real users were affected.
- No real personal information was used.
- No password or session token was exposed.
- No uploaded document was exposed.
- No database records were altered by unauthorized users.
- No external system was contacted.
- Application availability was not affected.

## Containment

- Login rate limiting blocked repeated failed attempts.
- The URL-preview allowlist rejected unauthorized destinations.
- Loopback, private, metadata, and unsupported-scheme destinations were rejected.
- Testing remained restricted to localhost.

## Eradication and corrective actions

- Verified password hashing and password verification.
- Verified generic login errors.
- Verified server-side role authorization.
- Verified prepared SQL statements.
- Verified CSRF-token validation.
- Verified URL destination validation.
- Verified security-event logging.
- Verified security headers and configuration hardening.

## Recovery

- Waited for the login rate-limit window to expire.
- Confirmed that valid student authentication worked again.
- Confirmed that the approved local mock URL could be previewed.
- Confirmed that rejected URL requests continued to be blocked.
- Reviewed the relevant audit-log entries.

## Root cause

The events were generated deliberately during defensive security testing. They represented expected handling of invalid authentication attempts and unsafe or unauthorized URL destinations.

## Lessons learned

- Rate limiting reduces repeated authentication attempts.
- Security logs provide useful evidence for investigation.
- Generic errors prevent unnecessary information disclosure.
- URL allowlisting must be combined with address validation.
- Local security testing should use fictitious data and isolated mock services.
- Security tests should be retained as reproducible regression tests.

## Evidence references

- `tests/evidence/AUTH-05-rate-limit.png`
- `tests/evidence/SSRF-02-loopback-rejected.png`
- `tests/evidence/SSRF-03-metadata-rejected.png`
- `tests/evidence/SSRF-04-unsupported-scheme.png`
- `tests/evidence/LOG-01-security-event-coverage.png`

## Approval

**Prepared by:** [Your name]  
**Matriculation number:** [Your matriculation number]  
**Date:** [Insert date]  
**Reviewed by:** [Supervisor or lecturer, if applicable]