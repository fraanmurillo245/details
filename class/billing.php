<?php
if (!defined('IN_APP')) { die('Acceso denegado'); }

/**
 * Pago único de la tarifa plana vía Stripe Checkout. Sin SDK: llamadas
 * directas a la API REST de Stripe por cURL (mismo patrón que AiDesigner).
 * Si no hay claves configuradas, el alta se hace sin cobro (ver
 * modules/signup/index.php) — nunca falla el registro por falta de Stripe.
 */
class Billing
{
    public static function isConfigured(): bool
    {
        return !empty($GLOBALS['_config']['stripe_secret_key']) && !empty($GLOBALS['_config']['stripe_price_id']);
    }

    /** Crea una Checkout Session de pago único y devuelve el array decodificado de Stripe (incluye 'url'). */
    public static function createCheckoutSession(string $successUrl, string $cancelUrl, string $customerEmail, array $metadata): array
    {
        $fields = [
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'customer_email' => $customerEmail,
            'line_items[0][price]' => $GLOBALS['_config']['stripe_price_id'],
            'line_items[0][quantity]' => 1,
        ];
        foreach ($metadata as $k => $v) {
            $fields['metadata[' . $k . ']'] = $v;
        }
        return self::request('POST', 'https://api.stripe.com/v1/checkout/sessions', $fields);
    }

    /** Recupera una Checkout Session (para verificar el pago al volver de Stripe). */
    public static function retrieveSession(string $sessionId): array
    {
        return self::request('GET', 'https://api.stripe.com/v1/checkout/sessions/' . urlencode($sessionId), []);
    }

    private static function request(string $method, string $url, array $fields): array
    {
        $apiKey = $GLOBALS['_config']['stripe_secret_key'] ?? '';
        if (!$apiKey) throw new RuntimeException('billing_not_configured');

        $ch = curl_init($url);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $apiKey],
            CURLOPT_TIMEOUT => 30,
        ];
        if ($method === 'POST') {
            $opts[CURLOPT_POST] = true;
            $opts[CURLOPT_POSTFIELDS] = http_build_query($fields);
        }
        curl_setopt_array($ch, $opts);
        $raw = curl_exec($ch);
        if ($raw === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('stripe_request_failed: ' . $err);
        }
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $body = json_decode($raw, true);
        if ($status >= 400 || !is_array($body)) {
            $msg = is_array($body) ? ($body['error']['message'] ?? 'unknown') : $raw;
            throw new RuntimeException('stripe_error: ' . $msg);
        }
        return $body;
    }
}
