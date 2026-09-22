<?php
if (!defined('IN_APP')) { die('Acceso denegado'); }

/**
 * Pago único de la tarifa plana vía Stripe Checkout, exigido al publicar
 * una invitación (no al darse de alta). Sin SDK: llamadas directas a la
 * API REST de Stripe por cURL (mismo patrón que AiDesigner). El importe no
 * viene de un Price ID fijo de Stripe, sino de la tabla settings (editable
 * desde /admin/settings/) — así "cambiar el precio" es solo editar un
 * número en el panel, sin tocar nada en Stripe.
 */
class Billing
{
    public static function isConfigured(): bool
    {
        return !empty($GLOBALS['_config']['stripe_secret_key']);
    }

    /** ¿Esta cuenta ya tiene al menos un pago completado? (la tarifa plana cubre toda la cuenta, no boda a boda). */
    public static function hasPaid(App $app, int $idAccount): bool
    {
        $stmt = $app->db->prepare("SELECT 1 FROM payments WHERE id_account = ? AND status = 'paid' LIMIT 1");
        $stmt->bind_param('i', $idAccount);
        $stmt->execute();
        $found = (bool)$stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $found;
    }

    /** Crea una Checkout Session de pago único (precio ad-hoc) y devuelve el array decodificado de Stripe (incluye 'url'). */
    public static function createCheckoutSession(App $app, string $successUrl, string $cancelUrl, string $customerEmail, array $metadata): array
    {
        return self::checkout(
            $successUrl, $cancelUrl, $customerEmail, $metadata,
            Settings::flatFeeCents($app), Settings::flatFeeCurrency($app), 'Invitación de boda — tarifa plana'
        );
    }

    /** Igual que createCheckoutSession pero con el precio del idioma extra (Settings::extraLanguage*). */
    public static function createLanguageCheckoutSession(App $app, string $successUrl, string $cancelUrl, string $customerEmail, array $metadata): array
    {
        return self::checkout(
            $successUrl, $cancelUrl, $customerEmail, $metadata,
            Settings::extraLanguageCents($app), Settings::extraLanguageCurrency($app), 'Invitación de boda — idioma adicional'
        );
    }

    private static function checkout(string $successUrl, string $cancelUrl, string $customerEmail, array $metadata, int $amountCents, string $currency, string $productName): array
    {
        $fields = [
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'customer_email' => $customerEmail,
            'line_items[0][quantity]' => 1,
            'line_items[0][price_data][currency]' => $currency,
            'line_items[0][price_data][unit_amount]' => $amountCents,
            'line_items[0][price_data][product_data][name]' => $productName,
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
