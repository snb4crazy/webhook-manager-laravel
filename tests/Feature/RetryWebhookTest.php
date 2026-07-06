<?php

namespace WebhookManager\Laravel\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use WebhookManager\Laravel\Facades\Webhook;
use WebhookManager\Laravel\Jobs\SendWebhookJob;
use WebhookManager\Laravel\Models\WebhookDelivery;
use WebhookManager\Laravel\Tests\TestCase;

class RetryWebhookTest extends TestCase
{
    public function test_it_queues_manual_retry_for_failed_delivery(): void
    {
        config()->set('webhook-manager.queue', false);

        Http::fake([
            '*' => Http::response(['message' => 'server_error'], 500),
        ]);

        $delivery = Webhook::send('https://receiver.test/hooks/orders', ['order_id' => 201], [
            'max_attempts' => 1,
        ]);

        $this->assertSame(WebhookDelivery::STATUS_FAILED, $delivery->fresh()?->status);

        Queue::fake();

        $retried = Webhook::retry($delivery);

        $this->assertSame(WebhookDelivery::STATUS_PENDING, $retried->fresh()?->status);

        Queue::assertPushed(SendWebhookJob::class);
    }
}

