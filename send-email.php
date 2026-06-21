<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once 'PHPMailer/Exception.php';
require_once 'PHPMailer/PHPMailer.php';
require_once 'PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load .env
$envPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if (empty($trimmed) || strpos($trimmed, '#') === 0) continue;
        $eqIdx = strpos($trimmed, '=');
        if ($eqIdx === false) continue;
        $key = trim(substr($trimmed, 0, $eqIdx));
        $val = trim(substr($trimmed, $eqIdx + 1));
        $_ENV[$key] = $val;
    }
}

// ── CONFIGURATION ────────────────────────────────────────────
$SMTP_HOST   = $_ENV['SMTP_HOST']   ?? 'smtp.gmail.com';
$SMTP_USER   = $_ENV['SMTP_USER']   ?? 'Overseastravel.contact@gmail.com';
$SMTP_PASS   = $_ENV['SMTP_PASS']   ?? 'espj vgla vcez neks';
$SMTP_PORT   = isset($_ENV['SMTP_PORT']) ? (int)$_ENV['SMTP_PORT'] : 587;
$SMTP_SECURE = $_ENV['SMTP_SECURE'] ?? 'tls';
$SITE_OWNER  = $SMTP_USER;
// ─────────────────────────────────────────────────────────────

function sendNotificationEmails($data) {
    global $SMTP_HOST, $SMTP_USER, $SMTP_PASS, $SMTP_PORT, $SMTP_SECURE, $SITE_OWNER;

    $logFile = __DIR__ . '/email_debug.log';
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Received data: " . json_encode($data) . "\n", FILE_APPEND);

    $required = ['name', 'phone', 'email', 'service', 'amount', 'transactionId', 'paymentMethod'];
    foreach ($required as $field) {
        if (!isset($data[$field])) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - ERROR: Missing field: $field\n", FILE_APPEND);
            return ['success' => false, 'error' => "Missing required field: $field"];
        }
    }

    $methodNames = [
        'card'         => 'Credit Card / Quick Pay',
        'installments' => 'Tabby Installments',
        'apple_pay'    => 'Apple Pay',
        'google_pay'   => 'Google Pay',
    ];
    $paymentMethod = $data['paymentMethod'];
    $methodName    = $methodNames[$paymentMethod] ?? $paymentMethod;

    $serviceDisplay = $data['service'] === 'inquiry' ? 'Custom Inquiry / Service' : $data['service'];

    $inquiryHtml = '';
    $inquiryText = '';
    if ($data['service'] === 'inquiry' && !empty($data['inquiryMessage'])) {
        $inquiryMessage = htmlspecialchars($data['inquiryMessage']);
        $inquiryHtml = "
        <div class='inquiry-box'>
            <div class='inquiry-label'>📝 Inquiry / Custom Service Details</div>
            <div class='inquiry-message'>$inquiryMessage</div>
        </div>";
        $inquiryText = "Inquiry Details: {$data['inquiryMessage']}\n";
    }

    $amount        = htmlspecialchars($data['amount']);
    $transactionId = htmlspecialchars($data['transactionId']);
    $name          = htmlspecialchars($data['name']);
    $phone         = htmlspecialchars($data['phone']);
    $email         = htmlspecialchars($data['email']);
    $userEmail     = $data['email'];
    $dateNow       = date('Y-m-d H:i:s');

    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Processing payment: $transactionId for $name\n", FILE_APPEND);

    // ══════════════════════════════════════════════════════════════
    // COMMON STYLES — LTR English layout
    // ══════════════════════════════════════════════════════════════
    $commonStyles = <<<CSS
        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
            background: #eef0f3;
            padding: 30px 16px;
            direction: ltr;
            margin: 0;
            color: #111827;
        }
        .wrapper {
            max-width: 600px;
            margin: 0 auto;
        }
        .container {
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0,0,0,0.09);
        }
        /* ── Header ── */
        .header {
            background: #0a0a0a;
            text-align: center;
            padding: 26px 30px 22px;
            border-bottom: 3px solid #4BFDB3;
        }
        .header h1 {
            color: #ffffff;
            font-size: 20px;
            margin: 0 0 5px;
            letter-spacing: 0.3px;
        }
        .header .sub {
            color: #9ca3af;
            font-size: 13px;
            margin: 0;
        }
        /* ── Body ── */
        .body-content {
            padding: 26px 28px 28px;
        }
        /* ── Section Label ── */
        .section-title {
            font-size: 10.5px;
            font-weight: 700;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0 0 10px;
            padding-bottom: 7px;
            border-bottom: 1px solid #f0f0f0;
        }
        .section {
            margin-bottom: 22px;
        }
        /* ── Detail Rows — TABLE layout for perfect alignment ── */
        .detail-table {
            width: 100%;
            border-collapse: collapse;
        }
        .detail-table td {
            padding: 9px 0;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: middle;
            font-size: 13.5px;
        }
        .detail-table tr:last-child td {
            border-bottom: none;
        }
        .detail-table .col-label {
            color: #6b7280;
            font-weight: 500;
            white-space: nowrap;
            width: 40%;
            padding-right: 24px;
        }
        .detail-table .col-value {
            color: #111827;
            font-weight: 600;
        }
        /* ── Customer Block ── */
        .customer-block {
            background: #f8fffe;
            border: 1px solid #c6f6e8;
            border-radius: 12px;
            padding: 16px 18px;
            margin-bottom: 22px;
        }
        .customer-block-title {
            font-size: 10.5px;
            font-weight: 700;
            color: #047857;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin: 0 0 12px;
        }
        .customer-block .detail-table td {
            border-bottom-color: #d1fae5;
        }
        /* ── Transaction ID ── */
        .txn-value {
            color: #059669 !important;
            font-family: 'Courier New', monospace;
            font-size: 12.5px !important;
            letter-spacing: 0.5px;
        }
        /* ── Status Badge ── */
        .status-badge {
            display: inline-block;
            background: #dcfce7;
            color: #166534;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }
        /* ── Inquiry Box ── */
        .inquiry-box {
            background: #f0f7ff;
            border-radius: 10px;
            padding: 13px 15px;
            margin: 6px 0;
            border-left: 4px solid #4BFDB3;
        }
        .inquiry-label {
            font-size: 10.5px;
            color: #6b7280;
            font-weight: 700;
            margin-bottom: 5px;
            letter-spacing: 0.4px;
            text-transform: uppercase;
        }
        .inquiry-message {
            color: #111827;
            font-size: 13.5px;
            line-height: 1.6;
        }
        /* ── Total Block ── */
        .total-block {
            background: #f8fffe;
            border: 1px solid #c6f6e8;
            border-radius: 12px;
            padding: 18px 20px;
            margin-top: 6px;
            text-align: center;
        }
        .total-label {
            color: #6b7280;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            display: block;
            margin-bottom: 4px;
        }
        .total-value {
            color: #059669;
            font-size: 28px;
            font-weight: 800;
            letter-spacing: 0.5px;
            display: block;
        }
        /* ── Contact Box ── */
        .contact-box {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 14px 16px;
            margin-top: 20px;
            text-align: center;
        }
        .contact-box p {
            margin: 4px 0;
            font-size: 13px;
            color: #6b7280;
        }
        .contact-box a {
            color: #059669;
            font-weight: 600;
            text-decoration: none;
        }
        /* ── Footer ── */
        .footer {
            text-align: center;
            padding: 14px 28px 22px;
            font-size: 11.5px;
            color: #9ca3af;
            border-top: 1px solid #f3f4f6;
            margin-top: 22px;
        }
        .footer a { color: #4BFDB3; text-decoration: none; }
CSS;

    // ══════════════════════════════════════════════════════════════
    // EMAIL 1: TO SITE OWNER
    // ══════════════════════════════════════════════════════════════
    $ownerSubject = "🔔 New Payment Request — $name — $transactionId";

    $ownerHtml = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>$commonStyles</style>
</head>
<body>
<div class="wrapper">
    <div class="container">

        <div class="header">
            <h1>🔔 New Payment Request</h1>
            <p class="sub">A new payment has been received via the website</p>
        </div>

        <div class="body-content">

            <!-- Customer Info -->
            <div class="customer-block">
                <div class="customer-block-title">👤 Customer Data</div>
                <table class="detail-table">
                    <tr>
                        <td class="col-label">Full Name</td>
                        <td class="col-value">$name</td>
                    </tr>
                    <tr>
                        <td class="col-label">Phone Number</td>
                        <td class="col-value">$phone</td>
                    </tr>
                    <tr>
                        <td class="col-label">E-mail</td>
                        <td class="col-value">$email</td>
                    </tr>
                </table>
            </div>

            <!-- Payment Details -->
            <div class="section">
                <div class="section-title">💳 Payment Details</div>
                <table class="detail-table">
                    <tr>
                        <td class="col-label">Transaction Number</td>
                        <td class="col-value txn-value">$transactionId</td>
                    </tr>
                    <tr>
                        <td class="col-label">Required Service</td>
                        <td class="col-value">$serviceDisplay</td>
                    </tr>
                    <tr>
                        <td class="col-label">Payment Method</td>
                        <td class="col-value">$methodName</td>
                    </tr>
                    <tr>
                        <td class="col-label">Date of Operation</td>
                        <td class="col-value">$dateNow</td>
                    </tr>
                    <tr>
                        <td class="col-label">Status</td>
                        <td class="col-value"><span class="status-badge">✓ Completed</span></td>
                    </tr>
                </table>
                $inquiryHtml
            </div>

            <!-- Total -->
            <div class="total-block">
                <span class="total-label">Amount Paid</span>
                <span class="total-value">AED $amount</span>
            </div>

            <div class="footer">
                <p>This is an automated email from Over Seas Payment System — please do not reply.</p>
            </div>

        </div>
    </div>
</div>
</body>
</html>
HTML;

    $ownerPlain = <<<TEXT
🔔 New Payment Request - Over Seas
═══════════════════════════════════

👤 Customer Data:
  Full Name    : $name
  Phone        : $phone
  Email        : $email

💳 Payment Details:
  Transaction  : $transactionId
  Service      : $serviceDisplay
  {$inquiryText}Method       : $methodName
  Amount       : AED $amount
  Date         : $dateNow
  Status       : Completed ✓

═══════════════════════════════════
TEXT;

    // ══════════════════════════════════════════════════════════════
    // EMAIL 2: TO USER — Payment receipt
    // ══════════════════════════════════════════════════════════════
    $userSubject = "✅ Payment Confirmed — Over Seas — $transactionId";

    $userHtml = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>$commonStyles
    .thank-you {
        text-align: center;
        padding: 22px 0 18px;
        border-bottom: 1px solid #f0f0f0;
        margin-bottom: 22px;
    }
    .check-icon {
        width: 54px;
        height: 54px;
        background: #dcfce7;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 26px;
        margin-bottom: 12px;
    }
    .thank-you h2 {
        color: #111827;
        font-size: 19px;
        margin: 0 0 6px;
        font-weight: 700;
    }
    .thank-you p {
        color: #6b7280;
        font-size: 13.5px;
        margin: 0;
    }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="container">

        <div class="header">
            <h1>🛫 Over Seas</h1>
            <p class="sub">Official Payment Receipt</p>
        </div>

        <div class="body-content">

            <!-- Thank You -->
            <div class="thank-you">
                <div class="check-icon">✅</div>
                <h2>Thank you, $name!</h2>
                <p>Your payment has been received successfully. Here are your transaction details for reference.</p>
            </div>

            <!-- Payment Details -->
            <div class="section">
                <div class="section-title">📋 Transaction Details</div>
                <table class="detail-table">
                    <tr>
                        <td class="col-label">Transaction Number</td>
                        <td class="col-value txn-value">$transactionId</td>
                    </tr>
                    <tr>
                        <td class="col-label">Service</td>
                        <td class="col-value">$serviceDisplay</td>
                    </tr>
                    <tr>
                        <td class="col-label">Payment Method</td>
                        <td class="col-value">$methodName</td>
                    </tr>
                    <tr>
                        <td class="col-label">Date</td>
                        <td class="col-value">$dateNow</td>
                    </tr>
                    <tr>
                        <td class="col-label">Status</td>
                        <td class="col-value"><span class="status-badge">✓ Completed</span></td>
                    </tr>
                </table>
                $inquiryHtml
            </div>

            <!-- Total -->
            <div class="total-block">
                <span class="total-label">Amount Paid</span>
                <span class="total-value">AED $amount</span>
            </div>

            <!-- Contact -->
            <div class="contact-box">
                <p>Have a question? We're here to help.</p>
                <p>
                    <a href="https://wa.me/971564630165">💬 WhatsApp</a>
                    &nbsp;&nbsp;|&nbsp;&nbsp;
                    <a href="mailto:info@overseas.ae">✉️ info@overseas.ae</a>
                </p>
            </div>

            <div class="footer">
                <p>This is an automated receipt from Over Seas. Please keep it for your records.</p>
                <p>© Over Seas — All rights reserved</p>
            </div>

        </div>
    </div>
</div>
</body>
</html>
HTML;

    $userPlain = <<<TEXT
✅ Payment Confirmed - Over Seas
═══════════════════════════════════

Thank you, $name!
Your payment has been received successfully.

Transaction Details:
  Transaction  : $transactionId
  Service      : $serviceDisplay
  {$inquiryText}Method       : $methodName
  Amount       : AED $amount
  Date         : $dateNow
  Status       : Completed ✓

═══════════════════════════════════
Support: WhatsApp +971564630165
Email  : info@overseas.ae
TEXT;

    // ══════════════════════════════════════════════════════════════
    // SEND BOTH EMAILS
    // ══════════════════════════════════════════════════════════════
    $errors = [];

    $configureSMTP = function(PHPMailer $mail) use ($SMTP_HOST, $SMTP_USER, $SMTP_PASS, $SMTP_PORT, $SMTP_SECURE) {
        $mail->isSMTP();
        $mail->Host     = $SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = $SMTP_USER;
        $mail->Password = $SMTP_PASS;
        $mail->CharSet  = 'UTF-8';

        $secure = strtolower($SMTP_SECURE);
        if ($secure === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = $SMTP_PORT !== 587 ? $SMTP_PORT : 465;
        } elseif ($secure === 'none') {
            $mail->SMTPSecure  = false;
            $mail->SMTPAutoTLS = false;
            $mail->Port        = $SMTP_PORT !== 587 ? $SMTP_PORT : 25;
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $SMTP_PORT;
        }

        $mail->setFrom($SMTP_USER, 'Over Seas');
        $mail->Sender = $SMTP_USER;
        $mail->addCustomHeader('X-Mailer', 'OverSeas-Payments');
    };

    // ── Email 1: To Site Owner ──────────────────────────────────
    try {
        $mailOwner = new PHPMailer(true);
        $configureSMTP($mailOwner);
        $mailOwner->addAddress($SITE_OWNER);
        $mailOwner->addReplyTo($userEmail, $data['name']);
        $mailOwner->MessageID = '<' . $transactionId . '-owner-' . time() . '@overseas.ae>';

        $mailOwner->isHTML(true);
        $mailOwner->Subject = $ownerSubject;
        $mailOwner->Body    = $ownerHtml;
        $mailOwner->AltBody = $ownerPlain;

        $mailOwner->send();
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - ✅ Owner email sent to: $SITE_OWNER\n", FILE_APPEND);
    } catch (Exception $e) {
        $errorMsg = "Owner email failed: " . $mailOwner->ErrorInfo;
        $errors[] = $errorMsg;
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - ❌ " . $errorMsg . "\n", FILE_APPEND);
    }

    // ── Email 2: To User (Receipt) ──────────────────────────────
    try {
        $mailUser = new PHPMailer(true);
        $configureSMTP($mailUser);
        $mailUser->addAddress($userEmail, $data['name']);
        $mailUser->addReplyTo('info@overseas.ae', 'Over Seas Support');
        $mailUser->MessageID = '<' . $transactionId . '-receipt-' . time() . '@overseas.ae>';

        $mailUser->isHTML(true);
        $mailUser->Subject = $userSubject;
        $mailUser->Body    = $userHtml;
        $mailUser->AltBody = $userPlain;

        $mailUser->send();
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - ✅ User email sent to: $userEmail\n", FILE_APPEND);
    } catch (Exception $e) {
        $errorMsg = "User email failed: " . $mailUser->ErrorInfo;
        $errors[] = $errorMsg;
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - ❌ " . $errorMsg . "\n", FILE_APPEND);
    }

    // ── Response ─────────────────────────────────────────────────
    if (empty($errors)) {
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - ✅ Both emails sent successfully for $transactionId\n\n", FILE_APPEND);
        return ['success' => true];
    } else {
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - ❌ Email errors: " . implode(', ', $errors) . "\n\n", FILE_APPEND);
        return ['success' => false, 'errors' => $errors];
    }
}

// ── HTTP API Handler ──────────────────────────────────────────
if (basename($_SERVER['SCRIPT_FILENAME']) === 'send-email.php') {
    ignore_user_abort(true);
    set_time_limit(120);

    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    header('Content-Type: application/json; charset=utf-8');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }

    $json = file_get_contents('php://input');
    $data = json_decode($json, true) ?: [];

    $res = sendNotificationEmails($data);
    if ($res['success']) {
        echo json_encode([
            'success'        => true,
            'message'        => 'Both emails sent successfully',
            'transaction_id' => $data['transactionId'] ?? 'N/A'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'error'   => 'One or more emails failed',
            'details' => $res['errors'] ?? []
        ]);
    }
}
