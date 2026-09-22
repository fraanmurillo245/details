<?php
if (!defined('IN_APP')) { die('Acceso denegado'); }

/**
 * Genera una propuesta de diseño (bloques + tema) a partir de fotos y datos
 * del evento, usando la API de Claude (visión). Devuelve datos estructurados
 * que se aplican sobre las plantillas propias — nunca HTML/CSS libre.
 */
class AiDesigner
{
    private const MODEL = 'claude-sonnet-5';
    private const MAX_PHOTOS = 4;
    private const ALLOWED_BLOCKS = ['cover', 'countdown', 'location', 'gallery', 'gift', 'rsvp'];
    private const ALLOWED_FONTS = ['serif', 'sans', 'script'];

    private const SYSTEM_PROMPT = <<<TXT
Eres un diseñador de invitaciones de boda. A partir de fotos de la pareja/evento
y unos datos básicos, propones una combinación de bloques, una paleta de dos
colores y una tipografía para una plantilla web ya existente.

Responde ÚNICAMENTE con un objeto JSON (sin texto adicional, sin markdown) con
esta forma exacta:
{
  "blocks": ["cover", "countdown", "location", "gallery", "gift", "rsvp"],
  "theme": {"color_primary": "#RRGGBB", "color_secondary": "#RRGGBB", "font": "serif"},
  "rationale": "explicación breve en español, 1-2 frases"
}

Reglas:
- "blocks" es un subconjunto ordenado de: cover, countdown, location, gallery, gift, rsvp.
  Incluye siempre "cover"; incluye "gallery" solo si se han aportado fotos.
- "font" es exactamente uno de: serif, sans, script.
- Los colores deben tener buen contraste entre sí y encajar con el tono de las fotos.
TXT;

    public static function isConfigured(): bool
    {
        return !empty($GLOBALS['_config']['anthropic_api_key']);
    }

    /**
     * @param array $wedding Fila de weddings (partner1_name, partner2_name, event_date, venue_name...)
     * @param array $photoPaths Rutas absolutas en disco de las fotos subidas
     * @param string $styleNotes Notas de estilo escritas por el usuario (opcional)
     * @return array{blocks:array,theme:array,rationale:string}
     */
    public static function suggest(array $wedding, array $photoPaths, string $styleNotes): array
    {
        $apiKey = $GLOBALS['_config']['anthropic_api_key'] ?? '';
        if (!$apiKey) throw new RuntimeException('ai_not_configured');

        $content = [['type' => 'text', 'text' => self::buildPrompt($wedding, $styleNotes, count($photoPaths))]];

        foreach (array_slice($photoPaths, 0, self::MAX_PHOTOS) as $path) {
            if (!is_file($path)) continue;
            $mime = mime_content_type($path) ?: 'image/jpeg';
            $content[] = [
                'type' => 'image',
                'source' => ['type' => 'base64', 'media_type' => $mime, 'data' => base64_encode(file_get_contents($path))],
            ];
        }

        $payload = [
            'model' => self::MODEL,
            'max_tokens' => 1024,
            'system' => self::SYSTEM_PROMPT,
            'messages' => [['role' => 'user', 'content' => $content]],
        ];

        $raw = self::callApi($apiKey, $payload);
        $text = $raw['content'][0]['text'] ?? '';
        $json = self::extractJson($text);
        if ($json === null) throw new RuntimeException('ai_invalid_json');

        return self::sanitize($json);
    }

    private static function buildPrompt(array $wedding, string $styleNotes, int $photoCount): string
    {
        $lines = [
            'Pareja: ' . trim(($wedding['partner1_name'] ?? '') . ' & ' . ($wedding['partner2_name'] ?? '')),
            'Fecha: ' . ($wedding['event_date'] ?? 'sin definir'),
            'Lugar: ' . ($wedding['venue_name'] ?? 'sin definir'),
            'Fotos adjuntas: ' . $photoCount,
        ];
        if ($styleNotes !== '') $lines[] = 'Estilo deseado (indicado por la pareja): ' . $styleNotes;
        return implode("\n", $lines);
    }

    private static function callApi(string $apiKey, array $payload): array
    {
        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'content-type: application/json',
                'x-api-key: ' . $apiKey,
                'anthropic-version: 2023-06-01',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 60,
        ]);
        $raw = curl_exec($ch);
        if ($raw === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('ai_request_failed: ' . $err);
        }
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $body = json_decode($raw, true);
        if ($status !== 200 || !is_array($body)) {
            throw new RuntimeException('ai_bad_response');
        }
        return $body;
    }

    /** Extrae el primer bloque JSON de un texto, tolerando ```json ... ``` alrededor. */
    private static function extractJson(string $text): ?array
    {
        $text = trim($text);
        if (str_starts_with($text, '```')) {
            $text = preg_replace('/^```[a-z]*\s*|\s*```$/i', '', $text);
        }
        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        if ($start === false || $end === false || $end < $start) return null;
        $decoded = json_decode(substr($text, $start, $end - $start + 1), true);
        return is_array($decoded) ? $decoded : null;
    }

    private static function sanitize(array $json): array
    {
        $blocks = array_values(array_intersect(
            array_map('strval', $json['blocks'] ?? []),
            self::ALLOWED_BLOCKS
        ));
        if (!in_array('cover', $blocks, true)) array_unshift($blocks, 'cover');

        $theme = is_array($json['theme'] ?? null) ? $json['theme'] : [];
        $colorPrimary = preg_match('/^#[0-9a-fA-F]{6}$/', $theme['color_primary'] ?? '') ? $theme['color_primary'] : '#b76e79';
        $colorSecondary = preg_match('/^#[0-9a-fA-F]{6}$/', $theme['color_secondary'] ?? '') ? $theme['color_secondary'] : '#faf6f2';
        $font = in_array($theme['font'] ?? '', self::ALLOWED_FONTS, true) ? $theme['font'] : 'serif';

        return [
            'blocks' => $blocks,
            'theme' => ['color_primary' => $colorPrimary, 'color_secondary' => $colorSecondary, 'font' => $font],
            'rationale' => mb_substr((string)($json['rationale'] ?? ''), 0, 400),
        ];
    }
}
