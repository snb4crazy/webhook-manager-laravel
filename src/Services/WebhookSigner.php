<?php

namespace WebhookManager\Laravel\Services;

class WebhookSigner
{
    /**
     * Build a Stripe-style signature header: t=<unix_timestamp>,v1=<hmac>.
     */
    public function makeHeader(string $payload, string $secret, ?int $timestamp = null): string
    {
        $timestamp ??= time();

        $signature = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        return sprintf('t=%s,v1=%s', $timestamp, $signature);
    }

    public function verify(string $payload, string $signatureHeader, string $secret, int $tolerance): bool
    {
        $segments = $this->parseHeader($signatureHeader);
        $timestamp = isset($segments['t']) ? (int) $segments['t'] : 0;
        $signature = $segments['v1'] ?? null;

        if ($timestamp <= 0 || ! is_string($signature) || $signature === '') {
            return false;
        }

        if (abs(time() - $timestamp) > $tolerance) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        return hash_equals($expected, $signature);
    }

    /**
     * @return array<string, string>
     */
    protected function parseHeader(string $header): array
    {
        $parts = array_filter(array_map('trim', explode(',', $header)));
        $segments = [];

        foreach ($parts as $part) {
            $entry = array_map('trim', explode('=', $part, 2));

            if (count($entry) !== 2 || $entry[0] === '' || $entry[1] === '') {
                continue;
            }

            $segments[$entry[0]] = $entry[1];
        }

        return $segments;
    }
}

