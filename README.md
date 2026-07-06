# webhook-manager-laravel

Queue-aware webhook delivery package for Laravel.

It gives you a clean API:

- `Webhook::send(...)`
- `Webhook::verify(...)`
- `Webhook::retry(...)`

And built-in portfolio-ready features:

- HMAC signatures (timestamped)
- Async delivery with queue jobs
- Retry with backoff
- Delivery log table (`webhook_deliveries`)

## Requirements

| Dependency | Version |
|------------|---------|
| PHP        | 8.2+    |
| Laravel    | 10-13   |

## Installation

```bash
composer require snb4crazy/webhook-manager-laravel
php artisan vendor:publish --tag=webhook-manager-config
php artisan vendor:publish --tag=webhook-manager-migrations
php artisan migrate
```

## Configuration

Add to `.env`:

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
```


## License

MIT

