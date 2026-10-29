# Static test results (October 29, 2026)

- PHP CLI syntax check: **90 project PHP files linted; 0 syntax errors**.
- PHP version available in the execution container: **PHP 8.4.24**.
- **Not tested:** actual MySQL schema, SQL migration execution, PHP mysqli runtime, browser rendering, native BarcodeDetector, camera permissions, QR image rendering with GD, end-to-end login/attendance/payment/clearance.
- Reason: the uploaded ZIP did not contain a database dump or live XAMPP connection; container PHP did not provide the `mysqli` extension.

## Structural security checks (source inspection)
- PASS: authentication verifies password
- PASS: role gates
- PASS: protected student qr
- PASS: anti-CSRF scan
- PASS: unique four-slot index
- PASS: semester clearance blockers
- PASS: payment records prepared
- PASS: student edit denied
- PASS: event cancellation uses soft status
- PASS: no legacy get clearance mutation

## Installation acceptance tests

Run the 10-step test sequence in `README-UPGRADE.md` against a **backup copy** of your MySQL database before production use.

A passing syntax check is not a guarantee that SQL or browser features work.
