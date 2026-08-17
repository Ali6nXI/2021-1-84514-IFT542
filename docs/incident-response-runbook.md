# IFT542 Incident-Response Runbook

## Scope

This runbook applies to the localhost-only Student Registration Web Application. It uses fictitious data and is operated by the application owner and incident handler.

## 1. Preparation

- Maintain the latest source-code backup and database schema.
- Keep the application restricted to localhost.
- Use fictitious accounts and documents only.
- Keep security logging enabled.
- Protect passwords, environment variables, session tokens, and database credentials.
- Maintain contact roles:
  - Application owner.
  - System administrator.
  - Incident handler.
  - Academic supervisor.

## 2. Detection and Analysis

- Review `audit_logs` for:
  - Failed logins.
  - Rate-limited logins.
  - Authorization denials.
  - Rejected uploads.
  - Rejected URL-preview requests.
- Record the incident ID, timestamp, event type, affected route, user ID, and source address.
- Determine whether the event affected:
  - Accounts.
  - Profiles.
  - Courses.
  - Enrolments.
  - Documents.
  - Database records.
  - Application availability.
- Preserve relevant logs and screenshots.
- Do not include passwords, session tokens, CSRF tokens, or unnecessary personal data in the incident record.

## 3. Containment

- Disable the affected account or feature if necessary.
- Stop suspicious login or upload activity.
- Temporarily disable URL preview if SSRF activity is suspected.
- Rotate compromised credentials.
- Restrict access to localhost.
- Preserve the original logs and application state before making major changes.

## 4. Eradication

- Identify the root cause.
- Remove unsafe SQL, authorization weaknesses, malicious files, or insecure configuration.
- Verify prepared statements and server-side authorization.
- Check uploaded-file storage and database records.
- Disable debug mode and default credentials.
- Review dependencies and configuration.
- Add a regression test for the discovered weakness.

## 5. Recovery

- Deploy the corrected local application.
- Restore verified data if necessary.
- Run authentication, authorization, upload, CSRF, XSS, SSRF, and database tests.
- Confirm that security logging is functioning.
- Monitor the application for repeated suspicious events.
- Re-enable features only after the relevant controls pass testing.

## 6. Lessons Learned

- Document the incident timeline and root cause.
- Record affected assets and possible impact.
- Update the STRIDE risk register.
- Record the corrective controls.
- Update the test suite and README.
- Review whether any residual risk is accepted.
- Schedule a follow-up security review.

## Evidence handling

- Store evidence in the project’s protected documentation folder.
- Use fictitious and redacted data.
- Do not commit passwords, tokens, API keys, or private documents.
- Preserve timestamps and file paths.
- Reference each evidence file from the final report.

## Escalation criteria

Escalate to the academic supervisor or application owner if:

- An administrator account may be compromised.
- Password hashes or student documents may be exposed.
- Database records may have been altered.
- The application is unavailable.
- Testing appears to have reached a system outside localhost.