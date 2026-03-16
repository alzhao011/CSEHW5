<?php

// Email via Resend API (https://resend.com) — free tier, no SMTP needed.
// Sign up, get an API key, and paste it below.
// From address must be a verified domain in your Resend account,
// OR use the sandbox address onboarding@resend.dev while testing.

define('RESEND_API_KEY', 'YOUR_RESEND_API_KEY_HERE');
define('MAIL_FROM', 'onboarding@resend.dev');   // replace with your verified domain address
define('MAIL_FROM_NAME', 'Analytics Platform');

function sendMail(string $to, string $subject, string $body): bool {
    $payload = json_encode([
        'from'    => MAIL_FROM_NAME . ' <' . MAIL_FROM . '>',
        'to'      => [$to],
        'subject' => $subject,
        'html'    => $body,
    ]);

    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . RESEND_API_KEY,
            'Content-Type: application/json',
        ],
    ]);
    $response = curl_exec($ch);
    $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $status === 200 || $status === 201;
}

function sendPasswordResetEmail(string $to, string $token): bool {
    $link = 'https://reporting.alansdomain.xyz/reset-password?token=' . urlencode($token);
    $subject = 'Reset your password — Analytics Platform';
    $body = '
<!DOCTYPE html>
<html>
<body style="font-family:sans-serif; color:#333; max-width:520px; margin:0 auto; padding:20px;">
    <h2>Password Reset Request</h2>
    <p>Someone requested a password reset for the account associated with this email.</p>
    <p>Click the button below to reset your password. This link expires in <strong>1 hour</strong>.</p>
    <p style="margin:28px 0;">
        <a href="' . htmlspecialchars($link) . '"
           style="background:#0d6efd; color:#fff; padding:12px 24px; border-radius:5px; text-decoration:none; font-size:15px;">
            Reset Password
        </a>
    </p>
    <p style="font-size:13px; color:#888;">Or copy this link into your browser:<br>
        <a href="' . htmlspecialchars($link) . '">' . htmlspecialchars($link) . '</a>
    </p>
    <hr style="margin-top:32px;">
    <p style="font-size:12px; color:#aaa;">If you did not request this, you can safely ignore this email.</p>
</body>
</html>';
    return sendMail($to, $subject, $body);
}
