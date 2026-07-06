<?php

namespace WebhookManager\Laravel\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use WebhookManager\Laravel\WebhookManager;

class SendWebhookJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public int $deliveryId,
        protected int $maxAttempts,
    ) {}

    public function tries(): int
    {
        return $this->maxAttempts;
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return (array) config('webhook-manager.retry_backoff', [10, 30, 120]);
    }

    public function handle(WebhookManager $manager): void
    {
        $manager->deliverById($this->deliveryId, true);
    }
}

