# RMMC ITE Event & Attendance System — Upgrade Package

## What this package contains

This ZIP is a **modification of the supplied `ITE_Event_System-master(1).zip`**, not a replacement framework. The original `admin/`, `officer/`, `student/`, `assets/`, `qr_codes/`, and `libraries/` directories were retained, with important PHP routes replaced or protected. The application uses its existing `mysqli` connection in `config/database.php`. **The uploaded ZIP did not contain a SQL export or the PDO/Composer PSR-4 migration-based application described in the written prompt.** The actual ZIP has mysqli, a `users` table with text `role`, no SQL migrations, and legacy QR PNG files. Do not apply this upgrade to a different local project revision without comparing their schemas.

## XAMPP installation and database backup

1. **Back up the current project folder and the MySQL database before proceeding.** The ZIP's `config/database.php` targets database `ite_event_system` on localhost with MySQL user `root` and an empty password. These values may differ on your machine.
2. For example, in Windows PowerShell (adjust database user and path if needed):

   ```powershell
   & 'C:\XAMPP 2\mysql\bin\mysqldump.exe' -u root --databases ite_event_system > "$HOME\Desktop\ite_event_system_backup.sql"
   ```

3. Extract this ZIP and **copy the contents inside `ITE_Event_System-master`** to a backed-up copy of `C:\XAMPP 2\htdocs\rmmc-ite-system`, if that is indeed the matching old mysqli project. Your VirtualHost document root must point to the new project's root; links assume `/auth/`, `/admin/`, `/officer/`, `/student/` paths start at the web root.
4. Update `config/database.php` for your actual database, if needed.
5. Review `migrations/001_upgrade.sql` and run the additive migration in XAMPP's PHP CLI:

   ```powershell
   cd 'C:\XAMPP 2\htdocs\rmmc-ite-system'
   & 'C:\XAMPP 2\php\php.exe' scripts\migrate.php
   & 'C:\XAMPP 2\php\php.exe' scripts\migrate.php --apply
   ```

   The first command is a safety message; **only `--apply` changes the database**. The script creates dedicated attendance-scan, fines, payments, clearance, staff-profile, and audit tables without dropping existing tables, adds QR tokens and metadata columns where absent, and widens legacy event/attendance status ENUMs if necessary. Existing event, student, attendance, and payment data are not deleted. This script has not been executed against your database because it was not attached.
6. Open `http://rmmc-ite.local/` and select Student or Admin/Officer. Accounts use their **actual database credentials**, not test/demo passwords. Existing plaintext passwords, if any, are migrated to secure hashes only after successful authentication, with a mandatory password change.

## Recommended test sequence

1. Confirm Admin, Officer, and Student login; verify incorrect passwords and wrong portals are rejected.
2. Confirm student first-login password change, logout, session timeout, and unauthorized page rejection.
3. Check student counts, course/year filters, individual student pages, and report totals using existing real data.
4. Add a test event under Events, configure `school_year`, `semester`, organizer (ITE or COMSOC), `is_required`, date, and the specific morning/afternoon slot times. Set to **Ongoing** on the event date.
5. Student opens My QR Code and downloads the secure new QR image. **Old printed QR images encode legacy student IDs, not the new cryptographic tokens; print/download new QR codes.**
6. Admin/Officer opens Scanner. Use a compatible browser with `BarcodeDetector`, or a USB QR scanner. Camera permissions generally require `https://` or `http://localhost`; the custom `http://rmmc-ite.local` domain often counts as an insecure origin. The built-in camera option may therefore be blocked unless HTTPS is configured.
7. Select a slot, scan once, verify success, then scan again to verify duplicate rejection. A configured slot is open from **30 minutes before to 60 minutes after** its scheduled time. Scan each required slot separately. Verify status remains Incomplete until all configured slots are recorded.
8. Set a finished, **required** event to `Finished` after the event date, then use **Finalize fines**. Fine generation is idempotent: one ₱20 fine per student with no attendance for that required event. Partial scans are left for human review and are **not** automatically fined per missing scan.
9. Staff records a payment using an actual receipt reference; verify the new ledger and the student's view. Then verify eligible semester clearance approval, and that outstanding fines/pending required events block clearance.
10. Try the pages and forms on desktop/mobile. Test a backup restoration procedure separately before using real records.

## Data model and compatibility

- `ite_attendance_scans` stores each unique event/student/date/slot scan with recorder and timestamp. The older `attendance` table remains for compatibility; same-day scans update its four legacy time columns and incomplete/present status.
- `ite_event_fines` and `ite_fine_payments` track **new** event fines and verified payments independently of the legacy `attendance.fine` field, which used inconsistent ₱25 logic. **Existing legacy fines and any previous payment records are preserved but are not automatically imported or reconciled into the new ledger.** Reconcile them with the department before operational use.
- `ite_clearance_approvals` holds semester/year approvals and their approvers. The existing `clearance` table is not deleted or overwritten; **old clearance approvals are not automatically migrated**.
- `ite_staff_profiles` stores editable staff names and birthdays without altering student information. `ite_audit_logs` records sensitive modifications in the new pages.
- The new `students.qr_token` is a private random token. Avoid posting this token publicly and regenerate exposed tokens if needed. The original publicly stored QR PNGs are left unchanged for backup/reference and are not used by the new scanner.

## Known limitations — not yet complete or live-verified

- **Database integration is unverified.** The supplied ZIP contained no SQL schema export or database contents, and this environment has PHP CLI without `mysqli`, MySQL server, or a browser-connected XAMPP instance. PHP syntax and structural checks passed; database queries, browser camera access, and end-to-end workflow **must be tested locally**.
- Multi-day event sessions are **not yet implemented**. The scan handler intentionally accepts only the configured single `event_date` and refuses other dates. Multiple date-specific windows, per-date fine rules, and cross-day rollups require additional implementation.
- The new clearance and fine ledgers are not a migration of legacy payment/clearance history. **Reconciliation and department-approved correction/override workflows must be added** before production use.
- Existing student registration by students and legacy student event-registration flow were retired in this upgrade. Student events are now read-only, and creation of student accounts is restricted to Admin CSV import. The new importer does not edit pre-existing student profiles.
- Staff display names, birthdays and middle initials can be populated through Settings, but legacy officer profiles may require manual synchronization. Student middle initials are shown only if an existing schema field supports them.
- QR camera scanning relies on the browser's native `BarcodeDetector` API; for browsers without it, use the USB QR reader input or integrate a reviewed local QR decoding library. There is no offline camera fallback library bundled.
- Advanced features like a full multi-day attendance schedule editor, payment reversal workflow, printable receipts, and comprehensive browser accessibility tests remain pending.
- The ZIP's legacy pages and PHP dependencies may contain additional bugs; continue a formal security review before internet deployment. **This is a local prototype upgrade, not a certified production release.**

## Modified source files

See `docs/MODIFIED-FILES.md` for an exact list of added and modified files and `docs/STATIC-TEST-RESULTS.md` for automated static checks.
