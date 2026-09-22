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

    /** Formatea céntimos como cantidad monetaria legible (490 -> "4,90 €"). */
    public static function money(int $cents, string $currency = 'eur'): string
    {
        $amount = number_format($cents / 100, 2, ',', '.');
        $symbol = ['eur' => '€', 'usd' => '$', 'gbp' => '£'][strtolower($currency)] ?? strtoupper($currency) . ' ';
        return $amount . ' ' . $symbol;
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

    /** Establece la cookie de sesión firmada para el usuario indicado (login / alta / tras pago) y registra la conexión. */
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

        $stmt = $this->db->prepare('SELECT language FROM users WHERE id_user = ? LIMIT 1');
        $stmt->bind_param('i', $idUser);
        $stmt->execute();
        $lang = $stmt->get_result()->fetch_assoc()['language'] ?? '';
        $stmt->close();
        if ($lang !== '') {
            setcookie('lang', $lang, ['expires' => time() + 60 * 60 * 24 * 365, 'path' => '/', 'samesite' => 'Lax']);
        }

        $stmt = $this->db->prepare('UPDATE users SET last_login_at = NOW() WHERE id_user = ?');
        $stmt->bind_param('i', $idUser);
        $stmt->execute();
        $stmt->close();
    }

    /** Mensaje de un solo uso tras una redirección del servidor (lo recoge y muestra assets/js/app.js). */
    public static function flash(string $msg, string $type = 'ok'): void
    {
        setcookie('flash', $msg, ['expires' => time() + 30, 'path' => '/', 'samesite' => 'Lax']);
        setcookie('flash_type', $type, ['expires' => time() + 30, 'path' => '/', 'samesite' => 'Lax']);
    }

    /** Selector de idioma (ES/EN/FR/IT), enlaces por GET a setlang.php que vuelven a la página actual. Válido logueado o no. */
    public static function langSwitcher(): string
    {
        $current = $GLOBALS['_lang'] ?? 'es';
        $labels = ['es' => 'ES', 'en' => 'EN', 'fr' => 'FR', 'it' => 'IT'];
        $redirect = urlencode($_SERVER['REQUEST_URI'] ?? '/');
        $html = '<div class="flex items-center gap-1 text-xs font-medium">';
        foreach ($labels as $code => $label) {
            if ($code === $current) {
                $html .= '<span class="px-1.5 py-0.5 rounded text-gray-400 dark:text-gray-500">' . $label . '</span>';
            } else {
                $html .= '<a href="/setlang.php?lang=' . $code . '&amp;redirect=' . $redirect . '" class="px-1.5 py-0.5 rounded hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-500 dark:text-gray-400">' . $label . '</a>';
            }
        }
        return $html . '</div>';
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
