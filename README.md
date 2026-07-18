# webhook-manager-laravel

[![License](https://img.shields.io/github/license/snb4crazy/webhook-manager-laravel)](LICENSE)
[![Latest Release](https://img.shields.io/github/v/release/snb4crazy/webhook-manager-laravel?sort=semver)](https://github.com/snb4crazy/webhook-manager-laravel/releases)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-10%20%7C%2011%20%7C%2012%20%7C%2013-FF2D20?logo=laravel&logoColor=white)](https://laravel.com/)
[![Last Commit](https://img.shields.io/github/last-commit/snb4crazy/webhook-manager-laravel)](https://github.com/snb4crazy/webhook-manager-laravel/commits/main)

Queue-aware webhook delivery package for Laravel with HMAC signing, signature verification, retries, and delivery logs.

## Features

- `Webhook::send(...)` for outbound webhook delivery
- `Webhook::verify(...)` for inbound signature verification
- `Webhook::retry(...)` for manual replay
- Timestamped SHA-256 HMAC signature header (`t=<ts>,v1=<hmac>`)
- Queue-driven delivery with configurable retries/backoff
- `webhook_deliveries` table for observability and replay workflows

## Requirements

| Dependency | Version |
|------------|---------|
| PHP        | 8.2+    |
| Laravel    | 10-13   |

## Installation

### Option A: Before Packagist (GitHub VCS)

Use this until the package is published on Packagist.

1. Add repository source in your app `composer.json`:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/snb4crazy/webhook-manager-laravel"
    }
  ]
}
```

2. Require the package from the `master` branch:

```bash
composer require snb4crazy/webhook-manager-laravel:dev-master
```

### Option B: After Packagist (recommended)

Once `v0.1.0` is tagged and synced to Packagist:

```bash
composer require snb4crazy/webhook-manager-laravel:^0.1
```

Then run the same setup steps:

1. Publish config + migrations and migrate:

```bash
php artisan vendor:publish --tag=webhook-manager-config
php artisan vendor:publish --tag=webhook-manager-migrations
php artisan migrate
```

2. If queue delivery is enabled (default), run a queue worker:

```bash
php artisan queue:work
```

## Configuration

Set package options in `.env`:

```env
WEBHOOK_MANAGER_ENABLED=true
WEBHOOK_MANAGER_SECRET=your-shared-secret
WEBHOOK_MANAGER_SIGNATURE_HEADER=X-Webhook-Signature
WEBHOOK_MANAGER_TIMESTAMP_TOLERANCE=300

WEBHOOK_MANAGER_QUEUE=true
WEBHOOK_MANAGER_QUEUE_CONNECTION=database
WEBHOOK_MANAGER_QUEUE_NAME=webhooks
WEBHOOK_MANAGER_MAX_ATTEMPTS=3
WEBHOOK_MANAGER_CONNECT_TIMEOUT=5
WEBHOOK_MANAGER_TIMEOUT=10
WEBHOOK_MANAGER_LOG_CHANNEL=
WEBHOOK_MANAGER_STORE_RESPONSE_BODY=false
```

## Quick start

### Send a webhook

```php
use WebhookManager\Laravel\Facades\Webhook;

Webhook::send(
    url: 'https://receiver.example.com/hooks/orders',
    payload: [
        'event' => 'order.created',
        'order_id' => $order->id,
        'total' => $order->total,
    ],
    options: [
        'event' => 'order.created',
        'secret' => config('services.partner.webhook_secret'),
        'headers' => ['X-Webhook-Source' => config('app.name')],
        'max_attempts' => 5,
        'queue' => true, // set false to send synchronously
    ],
);
```

### Verify an incoming webhook

```php
use Illuminate\Http\Request;
use WebhookManager\Laravel\Facades\Webhook;

public function __invoke(Request $request)
{
    $signature = (string) $request->header('X-Webhook-Signature');

    abort_unless(
        Webhook::verify($request->getContent(), $signature, config('services.partner.webhook_secret')),
        401,
        'Invalid webhook signature.'
    );

    // Process verified payload...
}
```

### Retry a failed delivery

```php
use WebhookManager\Laravel\Facades\Webhook;

Webhook::retry($deliveryId);
// or Webhook::retry($deliveryModel);
```

## Delivery logs

Each call to `Webhook::send()` creates/updates a row in `webhook_deliveries`:

- `status`: `pending`, `delivered`, `failed`
- `attempts`, `max_attempts`, `last_attempt_at`, `delivered_at`
- `response_status`, `last_error`, optional `response_body`
- stored `payload`, `headers`, and generated `signature`

This table is useful for admin screens, incident investigation, and manual replay tools.

## User stories

| Story | How to do it with this package |
|------|------|
| As a SaaS provider, I need to notify customers when an event happens. | Call `Webhook::send($url, $payload, ['event' => '...'])` from your domain event listener/job. |
| As a receiver, I need to trust only authentic webhook calls. | Validate `X-Webhook-Signature` with `Webhook::verify($rawBody, $signature, $secret)`. |
| As an operator, I need to recover from transient outages. | Keep queue mode enabled, tune `WEBHOOK_MANAGER_MAX_ATTEMPTS`, and use `Webhook::retry($deliveryId)` for manual replay. |
| As a support engineer, I need auditability for deliveries. | Query the `webhook_deliveries` table for failures, response codes, and attempt history. |

## Recommended badges

Already included above:

- License
- Latest release
- PHP support
- Laravel support
- Last commit

Useful additions once available:

- **CI status** (`github/actions/workflow/status/...`) after adding GitHub Actions
- **Code coverage** (Codecov/Coveralls) after publishing coverage reports
- **Packagist version/downloads** after package publication on Packagist

## Testing

```bash
composer test
```

## License

MIT
