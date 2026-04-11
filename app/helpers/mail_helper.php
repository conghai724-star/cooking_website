<?php

declare(strict_types=1);

function send_email_change_verification(
    string $toEmail,
    string $verifyUrl,
    string $displayName = '',
    string $expiresAtText = '30 phút'
): bool {
    $name = trim($displayName) !== '' ? trim($displayName) : 'bạn';
    $subject = '[Cooking Website] Xác nhận đổi email';
    $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $safeUrl = htmlspecialchars($verifyUrl, ENT_QUOTES, 'UTF-8');
    $safeExpire = htmlspecialchars($expiresAtText, ENT_QUOTES, 'UTF-8');

    $htmlBody = '<!doctype html><html lang="vi"><head><meta charset="UTF-8"><title>Xác nhận đổi email</title></head>'
        . '<body style="font-family:Arial,sans-serif;line-height:1.6;color:#0f172a;">'
        . '<h2 style="margin:0 0 12px;">Xác nhận đổi email</h2>'
        . '<p>Xin chào <strong>' . $safeName . '</strong>,</p>'
        . '<p>Bạn vừa yêu cầu đổi email đăng nhập cho tài khoản Cooking Website.</p>'
        . '<p><a href="' . $safeUrl . '" style="display:inline-block;padding:10px 14px;background:#f59f0a;color:#fff;text-decoration:none;border-radius:8px;font-weight:700;">Xác nhận email mới</a></p>'
        . '<p>Hoặc mở liên kết này trong trình duyệt:</p>'
        . '<p><a href="' . $safeUrl . '">' . $safeUrl . '</a></p>'
        . '<p>Liên kết sẽ hết hạn sau <strong>' . $safeExpire . '</strong>.</p>'
        . '<p>Nếu không phải bạn thực hiện, hãy bỏ qua email này.</p>'
        . '</body></html>';

    $textBody = "Xin chào {$name},\n\n"
        . "Bạn vừa yêu cầu đổi email đăng nhập cho tài khoản Cooking Website.\n"
        . "Xác nhận tại: {$verifyUrl}\n"
        . "Liên kết hết hạn sau {$expiresAtText}.\n"
        . "Nếu không phải bạn thực hiện, hãy bỏ qua email này.\n";

    $sent = send_mail_message($toEmail, $subject, $htmlBody, $textBody);
    log_outgoing_mail($toEmail, $subject, $textBody);

    return $sent;
}

function send_password_reset_email(
    string $toEmail,
    string $resetUrl,
    string $displayName = '',
    string $expiresAtText = '30 phút'
): bool {
    $name = trim($displayName) !== '' ? trim($displayName) : 'bạn';
    $subject = '[Cooking Website] Đặt lại mật khẩu';
    $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $safeUrl = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');
    $safeExpire = htmlspecialchars($expiresAtText, ENT_QUOTES, 'UTF-8');

    $htmlBody = '<!doctype html><html lang="vi"><head><meta charset="UTF-8"><title>Đặt lại mật khẩu</title></head>'
        . '<body style="font-family:Arial,sans-serif;line-height:1.6;color:#0f172a;">'
        . '<h2 style="margin:0 0 12px;">Đặt lại mật khẩu</h2>'
        . '<p>Xin chào <strong>' . $safeName . '</strong>,</p>'
        . '<p>Bạn vừa yêu cầu đặt lại mật khẩu cho tài khoản Cooking Website.</p>'
        . '<p><a href="' . $safeUrl . '" style="display:inline-block;padding:10px 14px;background:#f59f0a;color:#fff;text-decoration:none;border-radius:8px;font-weight:700;">Đặt lại mật khẩu</a></p>'
        . '<p>Hoặc mở liên kết này trong trình duyệt:</p>'
        . '<p><a href="' . $safeUrl . '">' . $safeUrl . '</a></p>'
        . '<p>Liên kết sẽ hết hạn sau <strong>' . $safeExpire . '</strong>.</p>'
        . '<p>Nếu bạn không yêu cầu, hãy bỏ qua email này.</p>'
        . '</body></html>';

    $textBody = "Xin chào {$name},\n\n"
        . "Bạn vừa yêu cầu đặt lại mật khẩu cho tài khoản Cooking Website.\n"
        . "Mở liên kết sau để tiếp tục: {$resetUrl}\n"
        . "Liên kết sẽ hết hạn sau {$expiresAtText}.\n"
        . "Nếu bạn không yêu cầu, hãy bỏ qua email này.\n";

    $sent = send_mail_message($toEmail, $subject, $htmlBody, $textBody);
    log_outgoing_mail($toEmail, $subject, $textBody);

    return $sent;
}

function send_mail_message(string $toEmail, string $subject, string $htmlBody, string $textBody = ''): bool
{
    $driver = defined('MAIL_DRIVER') ? strtolower((string) MAIL_DRIVER) : 'mail';

    if ($driver === 'smtp') {
        return smtp_send_mail($toEmail, $subject, $htmlBody, $textBody);
    }

    return php_mail_send($toEmail, $subject, $htmlBody);
}

function smtp_send_mail(string $toEmail, string $subject, string $htmlBody, string $textBody = ''): bool
{
    $host = defined('MAIL_HOST') ? (string) MAIL_HOST : '';
    $port = defined('MAIL_PORT') ? (int) MAIL_PORT : 587;
    $encryption = defined('MAIL_ENCRYPTION') ? strtolower((string) MAIL_ENCRYPTION) : 'tls';
    $username = defined('MAIL_USERNAME') ? (string) MAIL_USERNAME : '';
    $password = defined('MAIL_PASSWORD') ? (string) MAIL_PASSWORD : '';
    $fromEmail = defined('MAIL_FROM_EMAIL') ? (string) MAIL_FROM_EMAIL : $username;
    $fromName = defined('MAIL_FROM_NAME') ? (string) MAIL_FROM_NAME : 'Cooking Website';

    if ($host === '' || $username === '' || $password === '' || $fromEmail === '') {
        return php_mail_send($toEmail, $subject, $htmlBody);
    }

    $targetHost = $encryption === 'ssl' ? 'ssl://' . $host : $host;
    $socket = @stream_socket_client($targetHost . ':' . $port, $errno, $errstr, 15);
    if (!is_resource($socket)) {
        log_outgoing_mail($toEmail, '[SMTP ERROR]', "Không thể kết nối SMTP: {$errstr} ({$errno})");
        return false;
    }

    stream_set_timeout($socket, 20);

    try {
        smtp_expect($socket, [220]);
        smtp_cmd($socket, 'EHLO localhost', [250]);

        if ($encryption === 'tls') {
            smtp_cmd($socket, 'STARTTLS', [220]);
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('Không thể bật TLS cho SMTP.');
            }
            smtp_cmd($socket, 'EHLO localhost', [250]);
        }

        smtp_cmd($socket, 'AUTH LOGIN', [334]);
        smtp_cmd($socket, base64_encode($username), [334]);
        smtp_cmd($socket, base64_encode($password), [235]);
        smtp_cmd($socket, 'MAIL FROM:<' . $fromEmail . '>', [250]);
        smtp_cmd($socket, 'RCPT TO:<' . $toEmail . '>', [250, 251]);
        smtp_cmd($socket, 'DATA', [354]);

        $message = build_mime_message($fromEmail, $fromName, $toEmail, $subject, $htmlBody, $textBody);
        fwrite($socket, $message . "\r\n.\r\n");
        smtp_expect($socket, [250]);
        smtp_cmd($socket, 'QUIT', [221]);

        fclose($socket);
        return true;
    } catch (Throwable $e) {
        if (is_resource($socket)) {
            @fwrite($socket, "QUIT\r\n");
            @fclose($socket);
        }
        log_outgoing_mail($toEmail, '[SMTP ERROR]', $e->getMessage());
        return false;
    }
}

function smtp_cmd($socket, string $command, array $expectedCodes): string
{
    fwrite($socket, $command . "\r\n");
    return smtp_expect($socket, $expectedCodes);
}

function smtp_expect($socket, array $expectedCodes): string
{
    $response = '';
    while (($line = fgets($socket, 515)) !== false) {
        $response .= $line;
        if (preg_match('/^\d{3}\s/', $line) === 1) {
            break;
        }
    }

    if ($response === '') {
        throw new RuntimeException('SMTP không trả về phản hồi.');
    }

    $code = (int) substr($response, 0, 3);
    if (!in_array($code, $expectedCodes, true)) {
        throw new RuntimeException('SMTP lỗi: ' . trim($response));
    }

    return $response;
}

function build_mime_message(
    string $fromEmail,
    string $fromName,
    string $toEmail,
    string $subject,
    string $htmlBody,
    string $textBody = ''
): string {
    $boundary = 'bnd_' . bin2hex(random_bytes(8));
    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $encodedFromName = '=?UTF-8?B?' . base64_encode($fromName) . '?=';
    $fromHeader = $encodedFromName . ' <' . $fromEmail . '>';

    if ($textBody === '') {
        $textBody = strip_tags($htmlBody);
    }

    $headers = [];
    $headers[] = 'Date: ' . date(DATE_RFC2822);
    $headers[] = 'From: ' . $fromHeader;
    $headers[] = 'To: <' . $toEmail . '>';
    $headers[] = 'Subject: ' . $encodedSubject;
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';

    $parts = [];
    $parts[] = '--' . $boundary;
    $parts[] = 'Content-Type: text/plain; charset=UTF-8';
    $parts[] = 'Content-Transfer-Encoding: 8bit';
    $parts[] = '';
    $parts[] = $textBody;
    $parts[] = '--' . $boundary;
    $parts[] = 'Content-Type: text/html; charset=UTF-8';
    $parts[] = 'Content-Transfer-Encoding: 8bit';
    $parts[] = '';
    $parts[] = $htmlBody;
    $parts[] = '--' . $boundary . '--';
    $parts[] = '';

    return implode("\r\n", array_merge($headers, [''], $parts));
}

function php_mail_send(string $toEmail, string $subject, string $htmlBody): bool
{
    $fromEmail = defined('MAIL_FROM_EMAIL') ? (string) MAIL_FROM_EMAIL : 'no-reply@localhost';
    $fromName = defined('MAIL_FROM_NAME') ? (string) MAIL_FROM_NAME : 'Cooking Website';
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= 'From: ' . $fromName . ' <' . $fromEmail . ">\r\n";

    return @mail($toEmail, $subject, $htmlBody, $headers);
}

function log_outgoing_mail(string $toEmail, string $subject, string $body): void
{
    $logDir = APPROOT . '/storage/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0775, true);
    }

    $line = '[' . date('Y-m-d H:i:s') . "] TO: {$toEmail}\nSUBJECT: {$subject}\n{$body}\n----\n";
    file_put_contents($logDir . '/mail.log', $line, FILE_APPEND);
}
