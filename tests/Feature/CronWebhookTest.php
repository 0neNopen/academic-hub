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
        $key = config('app.key');
        $response = $this->getJson('/cron/run-schedule?key=' . urlencode($key));
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'message',
            'output',
            'timestamp',
        ]);
        $this->assertEquals('success', $response->json('status'));
    }
}
