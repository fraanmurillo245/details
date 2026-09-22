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

    public static function publishPaymentReceived(array $wedding, string $toEmail, string $toName): bool
    {
        $url = rtrim($GLOBALS['_config']['url'] ?? '', '/') . '/invite/' . $wedding['slug'] . '/';
        $body = '<p>¡Hola ' . App::e($toName) . '!</p>'
            . '<p>Hemos recibido el pago y vuestra invitación ya está <strong>publicada</strong>. Ya podéis compartir el enlace con vuestros invitados:</p>'
            . '<p style="font-size:14px;word-break:break-all;"><a href="' . App::e($url) . '" style="color:#7783c4;">' . App::e($url) . '</a></p>'
            . EmailTemplate::button($url, 'Ver invitación');
        return Mailer::send($toEmail, $toName, '¡Vuestra invitación ya está publicada!', EmailTemplate::render('Pago recibido', $body));
    }

    /** Recordatorio a los 3 días de inactividad sin haber publicado (invita a retomarlo). */
    public static function reminderDay3(string $toEmail, string $toName, string $lang = 'es'): bool
    {
        $url = rtrim($GLOBALS['_config']['url'] ?? '', '/') . '/design/';
        $copy = [
            'es' => ['subject' => 'Vuestra invitación os está esperando 💌', 'preheader' => 'Le faltan un par de retoques',
                'body' => '<p>¡Hola ' . App::e($toName) . '!</p><p>Vimos que empezasteis a montar vuestra invitación pero no habéis vuelto por aquí. Está guardada tal cual la dejasteis — solo os queda darle el último toque y publicarla cuando estéis listos.</p>',
                'btn' => 'Retomar mi invitación'],
            'en' => ['subject' => 'Your invitation is still waiting for you 💌', 'preheader' => "It's almost ready",
                'body' => '<p>Hi ' . App::e($toName) . '!</p><p>We noticed you started building your invitation but haven\'t been back. Everything you did is saved exactly as you left it — just a few finishing touches and it\'s ready to publish whenever you are.</p>',
                'btn' => 'Pick up where I left off'],
            'fr' => ['subject' => 'Votre faire-part vous attend toujours 💌', 'preheader' => 'Plus que quelques détails',
                'body' => '<p>Bonjour ' . App::e($toName) . ' !</p><p>Nous avons vu que vous aviez commencé à créer votre faire-part, mais vous n\'êtes pas revenus. Tout est enregistré tel que vous l\'avez laissé — il ne reste que la touche finale avant de le publier.</p>',
                'btn' => 'Reprendre mon faire-part'],
            'it' => ['subject' => 'Il vostro invito vi sta aspettando 💌', 'preheader' => 'Manca solo l\'ultimo tocco',
                'body' => '<p>Ciao ' . App::e($toName) . '!</p><p>Abbiamo visto che avevate iniziato a creare il vostro invito, ma non siete più tornati. È tutto salvato esattamente come lo avete lasciato: basta un ultimo ritocco e sarà pronto da pubblicare.</p>',
                'btn' => 'Riprendi il mio invito'],
        ][$lang] ?? null;
        if (!$copy) $copy = self::reminderFallbackCopy($toName); // fallback defensivo, no debería ocurrir (idioma fuera de es/en/fr/it)
        $body = $copy['body'] . EmailTemplate::button($url, $copy['btn']);
        return Mailer::send($toEmail, $toName, $copy['subject'], EmailTemplate::render($copy['preheader'], $body));
    }

    /** Recordatorio a los 7 días de inactividad (más personal: ofrece ayuda / feedback). */
    public static function reminderDay7(string $toEmail, string $toName, string $lang = 'es'): bool
    {
        $url = rtrim($GLOBALS['_config']['url'] ?? '', '/') . '/dashboard/';
        $copy = [
            'es' => ['subject' => '¿Hay algo en lo que podamos ayudaros?', 'preheader' => 'Solo queríamos saber cómo va todo',
                'body' => '<p>Hola ' . App::e($toName) . ',</p><p>Vimos que empezasteis vuestra invitación hace ya una semana y no habéis vuelto. Si os habéis quedado atascados con algo, si algo no os ha convencido o si simplemente tenéis una duda, contestad a este correo — lo leemos nosotros, no un buzón automático.</p><p style="font-size:14px;color:#6b6b6b;">Y si ya no os interesa, ningún problema: no hace falta que hagáis nada más.</p>',
                'btn' => 'Volver a mi cuenta'],
            'en' => ['subject' => 'Is there anything we can help with?', 'preheader' => 'Just checking in',
                'body' => '<p>Hi ' . App::e($toName) . ',</p><p>We noticed you started your invitation a week ago and haven\'t been back. If you got stuck on something, if something wasn\'t quite what you expected, or if you just have a question — reply to this email, a real person reads it, not a bot.</p><p style="font-size:14px;color:#6b6b6b;">And if you\'ve changed your mind, that\'s completely fine too — no need to do anything.</p>',
                'btn' => 'Back to my account'],
            'fr' => ['subject' => 'Pouvons-nous vous aider ?', 'preheader' => 'On prend juste des nouvelles',
                'body' => '<p>Bonjour ' . App::e($toName) . ',</p><p>Nous avons vu que vous aviez commencé votre faire-part il y a une semaine et que vous n\'étiez pas revenus. Si vous êtes bloqués sur quelque chose, si quelque chose ne vous a pas convaincus, ou si vous avez simplement une question, répondez à cet e-mail — c\'est une vraie personne qui le lit, pas un robot.</p><p style="font-size:14px;color:#6b6b6b;">Et si vous avez changé d\'avis, pas de souci non plus : vous n\'avez rien à faire.</p>',
                'btn' => 'Retourner à mon compte'],
            'it' => ['subject' => 'C\'è qualcosa in cui possiamo aiutarvi?', 'preheader' => 'Volevamo solo sapere come va',
                'body' => '<p>Ciao ' . App::e($toName) . ',</p><p>Abbiamo visto che avete iniziato il vostro invito una settimana fa e non siete più tornati. Se vi siete bloccati su qualcosa, se qualcosa non vi ha convinto o se avete semplicemente una domanda, rispondete a questa email: la leggiamo noi, non un bot.</p><p style="font-size:14px;color:#6b6b6b;">E se avete cambiato idea, nessun problema: non dovete fare nulla.</p>',
                'btn' => 'Torna al mio account'],
        ][$lang] ?? null;
        if (!$copy) $copy = self::reminderFallbackCopy($toName);
        $body = $copy['body'] . EmailTemplate::button($url, $copy['btn']);
        return Mailer::send($toEmail, $toName, $copy['subject'], EmailTemplate::render($copy['preheader'], $body));
    }

    private static function reminderFallbackCopy(string $toName): array
    {
        return ['subject' => 'Vuestra invitación os está esperando', 'preheader' => '', 'body' => '<p>Hola ' . App::e($toName) . '</p>', 'btn' => 'Entrar'];
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
