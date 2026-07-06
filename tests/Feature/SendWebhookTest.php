<?php

namespace WebhookManager\Laravel\Tests\Feature;

use Illuminate\Support\Facades\Http;
use WebhookManager\Laravel\Facades\Webhook;
use WebhookManager\Laravel\Models\WebhookDelivery;
use WebhookManager\Laravel\Tests\TestCase;

class SendWebhookTest extends TestCase
{
    public function test_it_sends_webhook_and_marks_delivery_as_delivered_when_sync(): void
    {
        config()->set('webhook-manager.queue', false);

        Http::fake([
            'https://receiver.test/hooks/orders' => Http::response(['ok' => true], 200),
        ]);

        $delivery = Webhook::send('https://receiver.test/hooks/orders', [
            'event' => 'order.created',
            'order_id' => 101,
        ], [
            'secret' => 'portfolio-secret',
            'event' => 'order.created',
        ]);

        $delivery = $delivery->fresh();

        $this->assertNotNull($delivery);
        $this->assertSame(WebhookDelivery::STATUS_DELIVERED, $delivery->status);
        $this->assertSame(1, $delivery->attempts);
        $this->assertSame(200, $delivery->response_status);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://receiver.test/hooks/orders'
                && $request->hasHeader('X-Webhook-Signature');
        });
    }
}

