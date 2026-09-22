<?php
if (!defined('IN_APP')) { die('Acceso denegado'); }

class App
{
    public mysqli $db;

    public function __construct()
    {
        $this->db = $GLOBALS['mysqli'];
    }

    /** Escapa texto para pintarlo en HTML. Usar SIEMPRE al imprimir datos. */
    public static function e($text): string
    {
        return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
    }

    /** Devuelve JSON y termina la ejecución. */
    public static function json(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** Redirige (relativa a la app o absoluta) y termina la ejecución. */
    public function redirect(string $url): void
    {
        if (!preg_match('#^https?://#i', $url)) {
            $base = rtrim($GLOBALS['_config']['url'] ?? '', '/');
            $url = $base . '/' . ltrim($url, '/');
        }
        header('Location: ' . $url);
        exit;
    }

    /** Establece la cookie de sesión firmada para el usuario indicado (login / alta tras pago). */
    public function loginAs(int $idUser): void
    {
        $sig = hash_hmac('sha256', (string)$idUser, $GLOBALS['_config']['secret']);
        setcookie('auth', $idUser . ':' . $sig, [
            'expires' => time() + 60 * 60 * 24 * 30,
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    /** Mensaje de un solo uso tras una redirección del servidor (lo recoge y muestra assets/js/app.js). */
    public static function flash(string $msg, string $type = 'ok'): void
    {
        setcookie('flash', $msg, ['expires' => time() + 30, 'path' => '/', 'samesite' => 'Lax']);
        setcookie('flash_type', $type, ['expires' => time() + 30, 'path' => '/', 'samesite' => 'Lax']);
    }

    /** Comprueba si el usuario logueado tiene alguno de los roles indicados. */
    public function hasRole(string ...$roles): bool
    {
        $user = $GLOBALS['_user'] ?? null;
        if (!$user) return false;
        return in_array($user['rol'], $roles, true);
    }

    /** Exige uno de los roles indicados o corta la ejecución (panel: redirige; ajax: 403). */
    public function requireRole(string ...$roles): void
    {
        if ($this->hasRole(...$roles)) return;
        if (defined('IS_AJAX')) {
            self::json(['ok' => false, 'error' => 'forbidden'], 403);
        }
        $this->redirect('/');
    }

    /** Auditoría ligera: acción + entidad afectada, ligada a la cuenta/usuario actual. */
    public function log(string $action, int $refId = 0, int $idAccount = 0): void
    {
        $idUser = (int)($GLOBALS['_user']['id_user'] ?? 0);
        if ($idAccount === 0) $idAccount = (int)($GLOBALS['_user']['id_account'] ?? 0);
        $stmt = $this->db->prepare(
            'INSERT INTO activity_log (id_account, id_user, action, ref_id, created_at) VALUES (?, ?, ?, ?, NOW())'
        );
        $stmt->bind_param('iisi', $idAccount, $idUser, $action, $refId);
        $stmt->execute();
        $stmt->close();
    }

    /** IP real del cliente tras proxy (Cloudflare / X-Forwarded-For). */
    public static function clientIp(): string
    {
        $remote = $_SERVER['REMOTE_ADDR'] ?? '';
        $isPrivate = $remote === '' || !filter_var($remote, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
        if ($isPrivate) {
            if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
                return $_SERVER['HTTP_CF_CONNECTING_IP'];
            }
            if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                return trim($parts[0]);
            }
        }
        return $remote;
    }
}
