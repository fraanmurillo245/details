<?php
if (!defined('IN_APP')) { die('Acceso denegado'); }

/**
 * Envío de correo sin dependencias externas: cliente SMTP mínimo por socket
 * (STARTTLS/SSL, AUTH LOGIN) si hay servidor configurado; si no, cae a
 * mail() de PHP (requiere sendmail/postfix local en el servidor). Nunca
 * lanza al llamador: un fallo de correo no debe romper un RSVP o un alta.
 */
class Mailer
{
    public static function isConfigured(): bool
    {
        return !empty($GLOBALS['_config']['smtp_host']);
    }

    public static function send(string $to, string $toName, string $subject, string $html, string $text = ''): bool
    {
        try {
            if (self::isConfigured()) {
                self::sendSmtp($to, $toName, $subject, $html, $text);
            } else {
                self::sendPhpMail($to, $toName, $subject, $html, $text);
            }
            return true;
        } catch (Throwable $e) {
            error_log('Mailer: ' . $e->getMessage());
            return false;
        }
    }

    private static function sendPhpMail(string $to, string $toName, string $subject, string $html, string $text): void
    {
        $cfg = $GLOBALS['_config'];
        $from = $cfg['mail_from'];
        $fromName = $cfg['mail_from_name'];
        $boundary = uniqid('bnd_', true);

        $headers = 'From: ' . self::encodeHeader($fromName) . ' <' . $from . '>' . "\r\n"
                 . 'MIME-Version: 1.0' . "\r\n"
                 . 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';

        $body = self::buildBody($boundary, $html, $text);
        $encodedSubject = self::encodeHeader($subject);
        if (!mail($to, $encodedSubject, $body, $headers)) {
            throw new RuntimeException('php_mail_failed');
        }
    }

    private static function sendSmtp(string $to, string $toName, string $subject, string $html, string $text): void
    {
        $cfg = $GLOBALS['_config'];
        $host = $cfg['smtp_host'];
        $port = (int)$cfg['smtp_port'];
        $secure = $cfg['smtp_secure'];
        $user = $cfg['smtp_user'];
        $pass = $cfg['smtp_pass'];
        $from = $cfg['mail_from'];
        $fromName = $cfg['mail_from_name'];
        $localHost = parse_url($cfg['url'] ?? '', PHP_URL_HOST) ?: 'localhost';

        $remote = ($secure === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
        $fp = @stream_socket_client($remote, $errno, $errstr, 15);
        if (!$fp) throw new RuntimeException('smtp_connect_failed: ' . $errstr);
        stream_set_timeout($fp, 15);

        self::expect($fp, 220);
        self::command($fp, 'EHLO ' . $localHost, 250);

        if ($secure === 'tls') {
            self::command($fp, 'STARTTLS', 220);
            if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('smtp_starttls_failed');
            }
            self::command($fp, 'EHLO ' . $localHost, 250);
        }

        if ($user !== '') {
            self::command($fp, 'AUTH LOGIN', 334);
            self::command($fp, base64_encode($user), 334);
            self::command($fp, base64_encode($pass), 235);
        }

        self::command($fp, 'MAIL FROM:<' . $from . '>', 250);
        self::command($fp, 'RCPT TO:<' . $to . '>', 250);
        self::command($fp, 'DATA', 354);

        $boundary = uniqid('bnd_', true);
        $headers = 'Date: ' . date('r') . "\r\n"
                 . 'Message-ID: <' . uniqid('msg_', true) . '@' . $localHost . '>' . "\r\n"
                 . 'To: ' . self::encodeHeader($toName) . ' <' . $to . '>' . "\r\n"
                 . 'From: ' . self::encodeHeader($fromName) . ' <' . $from . '>' . "\r\n"
                 . 'Subject: ' . self::encodeHeader($subject) . "\r\n"
                 . 'MIME-Version: 1.0' . "\r\n"
                 . 'Content-Type: multipart/alternative; boundary="' . $boundary . '"' . "\r\n";

        $data = $headers . "\r\n" . self::buildBody($boundary, $html, $text);
        $data = preg_replace('/^\./m', '..', $data); // dot-stuffing (RFC 5321)
        fwrite($fp, $data . "\r\n.\r\n");
        self::expect($fp, 250);

        self::command($fp, 'QUIT', 221);
        fclose($fp);
    }

    /** @param resource $fp */
    private static function command($fp, string $cmd, int $expectCode): void
    {
        fwrite($fp, $cmd . "\r\n");
        self::expect($fp, $expectCode);
    }

    /** @param resource $fp */
    private static function expect($fp, int $expectCode): void
    {
        $line = '';
        do {
            $line = fgets($fp, 515);
            if ($line === false) throw new RuntimeException('smtp_read_failed');
        } while (isset($line[3]) && $line[3] === '-'); // "250-..." continúa, "250 ..." es la última línea
        $code = (int)substr($line, 0, 3);
        if ($code !== $expectCode) {
            throw new RuntimeException('smtp_unexpected_response: ' . trim($line));
        }
    }

    private static function encodeHeader(string $text): string
    {
        return '=?UTF-8?B?' . base64_encode($text) . '?=';
    }

    private static function buildBody(string $boundary, string $html, string $text): string
    {
        if ($text === '') {
            $text = trim(html_entity_decode(strip_tags(preg_replace('/<(br|\/p|\/div)[^>]*>/i', "\n", $html))));
        }
        return "--$boundary\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
             . chunk_split(base64_encode($text))
             . "--$boundary\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
             . chunk_split(base64_encode($html))
             . "--$boundary--\r\n";
    }
}
