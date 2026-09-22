<?php
if (!defined('IN_APP')) { die('Acceso denegado'); }

class Headers
{
    public function checkHeaders(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header("Content-Security-Policy: frame-ancestors 'self'");
    }
}
