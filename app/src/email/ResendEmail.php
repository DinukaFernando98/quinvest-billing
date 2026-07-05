<?php

namespace App\Email;

use SilverStripe\Core\Environment;

class ResendEmail
{
    private const FROM = 'Quinvest Website <noreply@zerakicreative.com>';

    /**
     * Send an email via the Resend API.
     *
     * @param string|string[] $to
     */
    public static function send(array|string $to, string $subject, string $html): bool
    {
        $apiKey = Environment::getEnv('RESEND_API_KEY');
        if (!$apiKey) {
            error_log('ResendEmail: RESEND_API_KEY not set');
            return false;
        }

        $recipients = is_array($to) ? array_values($to) : [$to];

        $payload = json_encode([
            'from'    => self::FROM,
            'to'      => $recipients,
            'subject' => $subject,
            'html'    => $html,
        ]);

        $ch = curl_init('https://api.resend.com/emails');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            error_log("ResendEmail cURL error: {$curlErr}");
            return false;
        }

        if ($httpCode !== 200) {
            error_log("ResendEmail API error [{$httpCode}]: {$response}");
            return false;
        }

        return true;
    }
}
