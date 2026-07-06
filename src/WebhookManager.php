<?php

namespace WebhookManager\Laravel;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use JsonException;
use WebhookManager\Laravel\Contracts\WebhookManagerInterface;
use WebhookManager\Laravel\Jobs\SendWebhookJob;
use WebhookManager\Laravel\Models\WebhookDelivery;
use WebhookManager\Laravel\Services\WebhookSigner;

class WebhookManager implements WebhookManagerInterface
{
    public function __construct(
        protected readonly HttpFactory $http,
        protected readonly WebhookSigner $signer,
    ) {}

    public function send(string $url, array $payload, array $options = []): WebhookDelivery
    {
        if (! (bool) config('webhook-manager.enabled', true)) {
            return $this->newDelivery($url, $payload, $options, WebhookDelivery::STATUS_FAILED, 'Webhook manager disabled.');
        }

        $delivery = $this->newDelivery($url, $payload, $options);

        if ($this->shouldQueue($options)) {
            $job = new SendWebhookJob((int) $delivery->getKey(), (int) $delivery->max_attempts);
            $job->onConnection(config('webhook-manager.queue_connection'));
            $job->onQueue(config('webhook-manager.queue_name'));
            dispatch($job);

            return $delivery;
        }

        $this->deliver($delivery, false);

        return $delivery->fresh() ?? $delivery;
    }

    public function verify(string|array $payload, string $signatureHeader, ?string $secret = null, ?int $tolerance = null): bool
    {
        $secret ??= (string) config('webhook-manager.default_secret', '');

        if ($secret === '') {
            return false;
        }

        return $this->signer->verify(
            $this->normalizePayload($payload),
            $signatureHeader,
            $secret,
            $tolerance ?? (int) config('webhook-manager.timestamp_tolerance', 300),
        );
    }

    public function retry(int|WebhookDelivery $delivery): WebhookDelivery
    {
        $delivery = $delivery instanceof WebhookDelivery
            ? $delivery->fresh() ?? $delivery
            : WebhookDelivery::query()->findOrFail($delivery);

        $delivery->status = WebhookDelivery::STATUS_PENDING;
        $delivery->last_error = null;
        $delivery->save();

        $job = new SendWebhookJob((int) $delivery->getKey(), (int) $delivery->max_attempts);
        $job->onConnection(config('webhook-manager.queue_connection'));
        $job->onQueue(config('webhook-manager.queue_name'));
        dispatch($job);

        return $delivery;
    }

    public function deliverById(int $deliveryId, bool $throwOnFailure = true): void
    {
        $delivery = WebhookDelivery::query()->find($deliveryId);

        if (! $delivery) {
            return;
        }

        $this->deliver($delivery, $throwOnFailure);
    }

    protected function deliver(WebhookDelivery $delivery, bool $throwOnFailure): void
    {
        $attempt = (int) $delivery->attempts + 1;

        try {
            $response = $this->http
                ->connectTimeout((int) config('webhook-manager.http.connect_timeout', 5))
                ->timeout((int) config('webhook-manager.http.timeout', 10))
                ->withHeaders($delivery->headers ?? [])
                ->post($delivery->target_url, $delivery->payload ?? [])
                ->throw();

            $delivery->forceFill([
                'status' => WebhookDelivery::STATUS_DELIVERED,
                'attempts' => $attempt,
                'last_attempt_at' => now(),
                'delivered_at' => now(),
                'last_error' => null,
                'response_status' => $response->status(),
                'response_body' => (bool) config('webhook-manager.store_response_body', false) ? $response->body() : null,
            ])->save();
        } catch (\Throwable $exception) {
            $isFinalAttempt = $attempt >= (int) $delivery->max_attempts;

            $delivery->forceFill([
                'status' => $isFinalAttempt ? WebhookDelivery::STATUS_FAILED : WebhookDelivery::STATUS_PENDING,
                'attempts' => $attempt,
                'last_attempt_at' => now(),
                'last_error' => $exception->getMessage(),
                'response_status' => $exception instanceof RequestException ? $exception->response->status() : null,
                'response_body' => null,
            ])->save();

            Log::channel(config('webhook-manager.log_channel'))
                ->warning('Webhook delivery failed.', [
                    'delivery_id' => $delivery->getKey(),
                    'target_url' => $delivery->target_url,
                    'attempt' => $attempt,
                    'max_attempts' => $delivery->max_attempts,
                    'error' => $exception->getMessage(),
                ]);

            if ($throwOnFailure && ! $isFinalAttempt) {
                throw $exception;
            }
        }
    }

    protected function shouldQueue(array $options): bool
    {
        if (array_key_exists('queue', $options)) {
            return (bool) $options['queue'];
        }

        return (bool) config('webhook-manager.queue', true);
    }

    protected function newDelivery(
        string $url,
        array $payload,
        array $options,
        string $status = WebhookDelivery::STATUS_PENDING,
        ?string $error = null,
    ): WebhookDelivery {
        $secret = $options['secret'] ?? config('webhook-manager.default_secret');
        $payloadJson = $this->normalizePayload($payload);
        $signature = is_string($secret) && $secret !== ''
            ? $this->signer->makeHeader($payloadJson, $secret)
            : null;

        $headers = (array) ($options['headers'] ?? []);

        if ($signature !== null) {
            $headers[(string) config('webhook-manager.signature_header', 'X-Webhook-Signature')] = $signature;
        }

        return WebhookDelivery::query()->create([
            'event' => $options['event'] ?? null,
            'target_url' => $url,
            'payload' => $payload,
            'headers' => $headers,
            'signature' => $signature,
            'status' => $status,
            'attempts' => 0,
            'max_attempts' => (int) ($options['max_attempts'] ?? config('webhook-manager.max_attempts', 3)),
            'last_error' => $error,
        ]);
    }

    protected function normalizePayload(string|array $payload): string
    {
        if (is_string($payload)) {
            return $payload;
        }

        try {
            return json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        } catch (JsonException) {
            return '{}';
        }
    }
}

