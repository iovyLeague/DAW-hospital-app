<?php
// config/mailer.php
require_once __DIR__ . '/config.php';

/**
 * send_mail($to, $subject, $html): bool
 * - If APP_OFFLINE=1 → logs to storage/dev_mail.log and returns true (no real send)
 * - Else tries PHPMailer SMTP (port 587 by default for InfinityFree)
 * - If PHPMailer/vender missing → falls back to mail() and logs failures
 */
function send_mail(string $to, string $subject, string $html): bool
{
    // OFFLINE: write to storage and short-circuit
    if (APP_OFFLINE) {
        @file_put_contents(
            STORAGE_PATH . '/dev_mail.log',
            "[" . date('c') . "] TO: {$to}\nSUBJECT: {$subject}\n{$html}\n\n",
            FILE_APPEND
        );
        return true;
    }

    // ONLINE: try PHPMailer SMTP
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (file_exists($autoload)) {
        require_once $autoload;
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = env('SMTP_HOST', 'smtp.gmail.com');
            $mail->SMTPAuth = true;
            $mail->Username = env('SMTP_USER', '');
            $mail->Password = 'iumm puhz jkab fmhs';
            $mail->SMTPSecure  = 'tls'; 
            $mail->Port = 587;
            $mail->SMTPAutoTLS = true;

            $mail->CharSet = 'UTF-8';
            $mail->Timeout = 8;
            $mail->SMTPKeepAlive = false;

            $fromEmail = env('SMTP_FROM', env('SMTP_USER', 'no-reply@example.com'));
            $fromName = env('SMTP_FROM_NAME', 'Mini Hospital');
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $html;
            $mail->AltBody = strip_tags($html);

            $mail->send();
            return true;

        } catch (Throwable $e) {
            error_log('PHPMailer failed: ' . $e->getMessage());
            // If send fails online, also log to storage for debugging
            @file_put_contents(
                STORAGE_PATH . '/dev_mail.log',
                "[" . date('c') . "] PHPMailer FAIL → TO: {$to}\nSUBJECT: {$subject}\n{$html}\n\n",
                FILE_APPEND
            );
            // fall through to mail()
        }
    }

    // Fallback: PHP mail() (often blocked on hosts/Windows)
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=utf-8',
        'From: ' . (env('SMTP_FROM_NAME', 'Mini Hospital')) . ' <' . (env('SMTP_FROM', 'no-reply@example.com')) . '>',
    ];
    $ok = @mail($to, $subject, $html, implode("\r\n", $headers));
    if (!$ok) {
        error_log('mail() failed to send to ' . $to . ' subject=' . $subject);
        @file_put_contents(
            STORAGE_PATH . '/dev_mail.log',
            "[" . date('c') . "] mail() FAIL → TO: {$to}\nSUBJECT: {$subject}\n{$html}\n\n",
            FILE_APPEND
        );
    }
    return $ok;
}
