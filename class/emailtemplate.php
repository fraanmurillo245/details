<?php
if (!defined('IN_APP')) { die('Acceso denegado'); }

/** Envuelve el contenido de un correo en una plantilla HTML con la marca (CSS inline: los clientes de correo no soportan <style>). */
class EmailTemplate
{
    public static function render(string $preheader, string $bodyHtml): string
    {
        $logoUrl = rtrim($GLOBALS['_config']['url'] ?? '', '/') . '/assets/img/logo.png';
        return '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>'
            . '<body style="margin:0;padding:0;background:#f1f2fb;font-family:Georgia,\'Times New Roman\',serif;color:#2a2422;">'
            . '<span style="display:none;font-size:0;line-height:0;color:#f1f2fb;">' . App::e($preheader) . '</span>'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f2fb;padding:32px 16px;">'
            . '<tr><td align="center">'
            . '<table role="presentation" width="480" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;max-width:480px;width:100%;">'
            . '<tr><td style="padding:32px 32px 16px;text-align:center;">'
            . '<img src="' . App::e($logoUrl) . '" alt="details" height="32" style="height:32px;border:0;">'
            . '</td></tr>'
            . '<tr><td style="padding:0 32px 32px;font-size:15px;line-height:1.6;">' . $bodyHtml . '</td></tr>'
            . '<tr><td style="background:#f1f2fb;padding:16px 32px;text-align:center;font-size:12px;color:#9a9a9a;">details · invitaciones de boda</td></tr>'
            . '</table></td></tr></table></body></html>';
    }

    public static function button(string $url, string $label): string
    {
        return '<p style="text-align:center;margin:28px 0;">'
            . '<a href="' . App::e($url) . '" style="background:linear-gradient(90deg,#7783c4,#616da9);color:#ffffff;text-decoration:none;'
            . 'padding:12px 28px;border-radius:9999px;font-size:14px;display:inline-block;">' . App::e($label) . '</a></p>';
    }
}
