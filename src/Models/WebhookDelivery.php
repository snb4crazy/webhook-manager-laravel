<?php

namespace WebhookManager\Laravel\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookDelivery extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_FAILED = 'failed';

    protected $table = 'webhook_deliveries';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'headers' => 'array',
            'last_attempt_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }
}

