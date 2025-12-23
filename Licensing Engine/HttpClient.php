<?php
declare(strict_types=1);

final class HttpClient
{
    public static function postJson(string $url, array $payload, int $timeoutSeconds = 8, array $headers = []): array
    {
        $ch = curl_init($url);
        if ($ch === false) {
            throw new RuntimeException('Failed to init curl.');
        }

        $h = array_merge([
            'Content-Type: application/json',
        ], $headers);

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $h,
            CURLOPT_TIMEOUT => $timeoutSeconds,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_SLASHES),
        ]);

        $resp = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);

        curl_close($ch);

        if ($resp === false) {
            throw new RuntimeException('HTTP request failed: ' . $err);
        }

        $json = json_decode($resp, true);
        if (!is_array($json)) {
            throw new RuntimeException('Invalid server response.');
        }

        return ['http_code' => $code, 'json' => $json];
    }
}
