<?php

namespace WebhookManager\Laravel\Tests\Unit;

use WebhookManager\Laravel\Services\WebhookSigner;
use WebhookManager\Laravel\Tests\TestCase;

class WebhookSignerTest extends TestCase
{
    public function test_it_builds_and_verifies_signature_header(): void
    {
        $signer = new WebhookSigner;
        $payload = '{"event":"order.created"}';
        $secret = 'super-secret';

        $header = $signer->makeHeader($payload, $secret, time());

        $this->assertTrue($signer->verify($payload, $header, $secret, 300));
    }


}

