<?php

namespace Tests\Feature;

use Tests\TestCase;

class CronWebhookTest extends TestCase
{
    public function test_cron_webhook_rejects_unauthorized_requests(): void
    {
        $response = $this->getJson('/cron/run-schedule');
        $response->assertStatus(403);

        $responseInvalid = $this->getJson('/cron/run-schedule?key=invalid-secret-key');
        $responseInvalid->assertStatus(403);
    }

    public function test_cron_webhook_runs_with_valid_key(): void
    {
        config(['services.cron_key' => 'super-secret-cron-token-2026']);

        // App key must no longer be accepted as cron query parameter
        $appKey = config('app.key');
        $this->getJson('/cron/run-schedule?key=' . urlencode($appKey))->assertStatus(403);

        // Correct cron key executes successfully
        $response = $this->getJson('/cron/run-schedule?key=super-secret-cron-token-2026');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'message',
            'output',
            'timestamp',
        ]);
        $this->assertEquals('success', $response->json('status'));
    }

    public function test_healthz_endpoint_returns_ok(): void
    {
        $response = $this->get('/healthz');
        $response->assertStatus(200);
        $this->assertEquals('OK', $response->getContent());
    }
}
