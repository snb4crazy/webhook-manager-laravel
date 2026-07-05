<?php

namespace WebhookManager\Laravel\Contracts;

use WebhookManager\Laravel\Models\WebhookDelivery;

interface WebhookManagerInterface
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $options
     */
    public function send(string $url, array $payload, array $options = []): WebhookDelivery;

    /**
     * @param  string|array<string, mixed>  $payload
     */
    public function verify(string|array $payload, string $signatureHeader, ?string $secret = null, ?int $tolerance = null): bool;

    public function retry(int|WebhookDelivery $delivery): WebhookDelivery;
}

