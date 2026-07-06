<?php

namespace WebhookManager\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \WebhookManager\Laravel\Models\WebhookDelivery send(string $url, array $payload, array $options = [])
 * @method static bool verify(string|array $payload, string $signatureHeader, ?string $secret = null, ?int $tolerance = null)
 * @method static \WebhookManager\Laravel\Models\WebhookDelivery retry(int|\WebhookManager\Laravel\Models\WebhookDelivery $delivery)
 */
class Webhook extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \WebhookManager\Laravel\Contracts\WebhookManagerInterface::class;
    }
}

