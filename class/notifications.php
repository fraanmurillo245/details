<?php
if (!defined('IN_APP')) { die('Acceso denegado'); }

/** Contenido de los correos transaccionales de la app. Un fallo de envío nunca debe romper el flujo que lo dispara (Mailer::send ya no lanza). */
class Notifications
{
    public static function welcome(string $toEmail, string $toName): bool
    {
        $url = rtrim($GLOBALS['_config']['url'] ?? '', '/');
        $body = '<p>¡Hola ' . App::e($toName) . '!</p>'
            . '<p>Tu cuenta en <strong>details</strong> ya está lista. Desde el panel puedes crear vuestra invitación, gestionar la lista de invitados y personalizar el diseño.</p>'
            . EmailTemplate::button($url . '/', 'Entrar al panel');
        return Mailer::send($toEmail, $toName, 'Bienvenido a details', EmailTemplate::render('Tu cuenta está lista', $body));
    }

    public static function rsvpGuestConfirmation(array $wedding, string $toEmail, string $toName, bool $attending): bool
    {
        if ($toEmail === '') return false;
        $names = $wedding['partner1_name'] . ' & ' . $wedding['partner2_name'];
        $msg = $attending
            ? '<p>¡Gracias, ' . App::e($toName) . '! Hemos registrado tu confirmación de asistencia a la boda de <strong>' . App::e($names) . '</strong>.</p>'
            : '<p>Hola ' . App::e($toName) . ', hemos registrado que no podrás asistir a la boda de <strong>' . App::e($names) . '</strong>. ¡Gracias por avisar!</p>';
        if ($attending && !empty($wedding['event_date'])) {
            $msg .= '<p style="color:#7783c4;font-size:14px;">' . App::e(date('d/m/Y', strtotime($wedding['event_date']))) . '</p>';
        }
        return Mailer::send($toEmail, $toName, 'Confirmación recibida — ' . $names, EmailTemplate::render('Hemos recibido tu respuesta', $msg));
    }

    public static function rsvpOwnerAlert(array $wedding, string $ownerEmail, string $ownerName, string $guestName, bool $attending, int $companions): bool
    {
        if ($ownerEmail === '') return false;
        $status = $attending ? 'confirma asistencia' : 'no podrá asistir';
        $body = '<p>Hola ' . App::e($ownerName) . ',</p>'
            . '<p><strong>' . App::e($guestName) . '</strong> ' . $status . ($attending && $companions > 0 ? ' (+' . $companions . ' acompañante' . ($companions > 1 ? 's' : '') . ')' : '') . '.</p>'
            . EmailTemplate::button(rtrim($GLOBALS['_config']['url'] ?? '', '/') . '/guests/', 'Ver lista de invitados');
        return Mailer::send($ownerEmail, $ownerName, 'Nueva respuesta de ' . $guestName, EmailTemplate::render('Nueva respuesta a vuestra invitación', $body));
    }

    public static function passwordReset(string $toEmail, string $toName, string $resetUrl): bool
    {
        $body = '<p>Hola ' . App::e($toName) . ',</p>'
            . '<p>Hemos recibido una solicitud para restablecer tu contraseña. Si no has sido tú, puedes ignorar este correo.</p>'
            . EmailTemplate::button($resetUrl, 'Restablecer contraseña')
            . '<p style="font-size:12px;color:#9a9a9a;">El enlace caduca en 1 hora.</p>';
        return Mailer::send($toEmail, $toName, 'Restablecer tu contraseña', EmailTemplate::render('Restablece tu contraseña', $body));
    }
}
