<?php
if (!defined('IN_APP')) { die('Acceso denegado'); }

/** Catálogo de plantillas visuales para la invitación pública. Fuente única
 *  de verdad: la usa tanto el selector del editor como el render público. */
class InviteTemplate
{
    private const TEMPLATES = [
        'classic' => [
            'label'         => 'Clásica',
            'swatch'        => ['#b76e79', '#faf6f2'],
            'font_name'     => "'Playfair Display', serif",
            'font_heading'  => "'Playfair Display', serif",
            'font_body'     => "'Cormorant Garamond', serif",
            'eyebrow_css'   => 'letter-spacing:.25em;text-transform:uppercase;font-style:normal;font-weight:400;',
            'divider'       => 'line',
            'hero_align'    => 'center',
        ],
        'modern' => [
            'label'         => 'Moderna',
            'swatch'        => ['#2b2b2b', '#f4f4f2'],
            'font_name'     => "'Poppins', sans-serif",
            'font_heading'  => "'Poppins', sans-serif",
            'font_body'     => "'Poppins', sans-serif",
            'eyebrow_css'   => 'letter-spacing:.35em;text-transform:uppercase;font-style:normal;font-weight:600;',
            'divider'       => 'square',
            'hero_align'    => 'left',
        ],
        'botanical' => [
            'label'         => 'Botánica',
            'swatch'        => ['#6b7a4f', '#f7f5ea'],
            'font_name'     => "'Great Vibes', cursive",
            'font_heading'  => "'Playfair Display', serif",
            'font_body'     => "'Cormorant Garamond', serif",
            'eyebrow_css'   => 'letter-spacing:.2em;text-transform:uppercase;font-style:italic;font-weight:400;',
            'divider'       => 'leaf',
            'hero_align'    => 'center',
        ],
    ];

    public static function all(): array
    {
        return self::TEMPLATES;
    }

    public static function get(string $key): array
    {
        return self::TEMPLATES[$key] ?? self::TEMPLATES['classic'];
    }

    public static function isValid(string $key): bool
    {
        return isset(self::TEMPLATES[$key]);
    }
}
