<?php
require_once __DIR__.'/../functions/bootstrap.php';
rmmc_require_roles('Student');
$st = rmmc_student();
if (!$st) { http_response_code(404); exit('QR unavailable'); }
$token = (string)($st['qr_token'] ?? '');
// Requires migration. No insecure public QR image serving or use of password data.
if ($token === '') { http_response_code(503); exit('Run the QR-token upgrade first'); }
require_once __DIR__.'/../libraries/phpqrcode/qrlib.php';
header('Content-Type: image/png');header('Cache-Control: private, no-store');header('X-Content-Type-Options: nosniff');
if (isset($_GET['download'])) header('Content-Disposition: attachment; filename="rmmc-ite-qr.png"');
QRcode::png($token, false, QR_ECLEVEL_M, 8, 3);
