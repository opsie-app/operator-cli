<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Queue;
use Spatie\WebhookServer\CallWebhookJob;
use Tests\TestCase;

class HttpTest extends TestCase
{
    public function test_healthy_website()
    {
        Queue::fake();

        $this->artisan('monitor', [
            'url' => 'https://reddit.com',
            '--metadata' => ['region=us'],
            '--webhook-url' => ['https://app.test'],
            '--webhook-secret' => ['secret'],
            '--dns-checking' => true,
            '--once' => true,
            '--verbose' => true,
        ]);

        Queue::assertPushed(CallWebhookJob::class, function ($job) {
            $this->assertArrayHasKey('region', $job->payload['metadata']);

            $this->assertEquals(200, $job->payload['http']['status']);
            $this->assertEquals(true, $job->payload['http']['up']);
            $this->assertNotNull($job->payload['http']['time'] ?? null);
            $this->assertNotEquals([], $job->payload['http']['timing']);

            $this->assertEquals(true, $job->payload['ssl']['valid']);
            $this->assertEquals(false, $job->payload['ssl']['expired']);
            $this->assertNotNull($job->payload['ssl']['time'] ?? null);
            $this->assertNotNull($job->payload['ssl']['issuer'] ?? null);
            $this->assertNotNull($job->payload['ssl']['valid_from'] ?? null);
            $this->assertNotNull($job->payload['ssl']['expires_on'] ?? null);
            $this->assertNotNull($job->payload['ssl']['days_remaining'] ?? null);
            $this->assertNotNull($job->payload['ssl']['domain'] ?? null);
            $this->assertNotNull($job->payload['ssl']['algorithm'] ?? null);
            $this->assertNotNull($job->payload['ssl']['fingerprint'] ?? null);
            $this->assertNotNull($job->payload['ssl']['additional_domains'] ?? null);

            $this->assertNotNull($job->payload['dns']['time'] ?? null);
            $this->assertNotNull($job->payload['dns']['records'] ?? null);

            $this->assertEquals('post', $job->httpVerb);
            $this->assertEquals(1, $job->tries);
            $this->assertEquals('Opsiebot/1.0', $job->headers['User-Agent']);
            $this->assertEquals($job->webhookUrl, 'https://app.test');
            $this->assertEquals($job->headers['Signature'], hash_hmac('sha256', json_encode($job->payload), 'secret'));

            return true;
        });
    }

    public function test_down_website()
    {
        Queue::fake();

        $this->artisan('monitor', [
            'url' => 'https://google.test',
            '--metadata' => ['region=us'],
            '--webhook-url' => ['https://app.test'],
            '--webhook-secret' => ['secret'],
            '--dns-checking' => true,
            '--once' => true,
            '--verbose' => true,
        ]);

        Queue::assertPushed(CallWebhookJob::class, function ($job) {
            $this->assertArrayHasKey('region', $job->payload['metadata']);

            $this->assertEquals(0, $job->payload['http']['status']);
            $this->assertEquals(false, $job->payload['http']['up']);
            $this->assertNotNull($job->payload['http']['time']);
            $this->assertEquals([], $job->payload['http']['timing']);

            $this->assertEquals(false, $job->payload['ssl']['valid']);
            $this->assertNull($job->payload['ssl']['expired'] ?? null);
            $this->assertNotNull($job->payload['ssl']['time'] ?? null);
            $this->assertNull($job->payload['ssl']['issuer'] ?? null);
            $this->assertNull($job->payload['ssl']['valid_from'] ?? null);
            $this->assertNull($job->payload['ssl']['expires_on'] ?? null);
            $this->assertNull($job->payload['ssl']['days_remaining'] ?? null);
            $this->assertNull($job->payload['ssl']['domain'] ?? null);
            $this->assertNull($job->payload['ssl']['algorithm'] ?? null);
            $this->assertNull($job->payload['ssl']['fingerprint'] ?? null);
            $this->assertNull($job->payload['ssl']['additional_domains'] ?? null);
            $this->assertNotNull($job->payload['ssl']['message']);

            $this->assertNotNull($job->payload['dns']['time'] ?? null);
            $this->assertNotNull($job->payload['dns']['records'] ?? null);

            $this->assertEquals('post', $job->httpVerb);
            $this->assertEquals(1, $job->tries);
            $this->assertEquals('Opsiebot/1.0', $job->headers['User-Agent']);
            $this->assertEquals($job->webhookUrl, 'https://app.test');
            $this->assertEquals($job->headers['Signature'], hash_hmac('sha256', json_encode($job->payload), 'secret'));

            return true;
        });
    }

    public function test_ssl_broken_website()
    {
        Queue::fake();

        $this->artisan('monitor', [
            'url' => 'https://self-signed.badssl.com',
            '--method' => 'GET',
            '--metadata' => ['region=us'],
            '--webhook-url' => ['https://app.test'],
            '--webhook-secret' => ['secret'],
            '--dns-checking' => true,
            '--once' => true,
            '--verbose' => true,
        ]);

        Queue::assertPushed(CallWebhookJob::class, function ($job) {
            $this->assertArrayHasKey('region', $job->payload['metadata']);

            $this->assertEquals(200, $job->payload['http']['status']);
            $this->assertEquals(true, $job->payload['http']['up']);
            $this->assertNotNull($job->payload['http']['time']);
            $this->assertNotEquals([], $job->payload['http']['timing']);

            $this->assertEquals(false, $job->payload['ssl']['valid']);
            $this->assertNull($job->payload['ssl']['expired'] ?? null);
            $this->assertNotNull($job->payload['ssl']['time'] ?? null);
            $this->assertNull($job->payload['ssl']['issuer'] ?? null);
            $this->assertNull($job->payload['ssl']['valid_from'] ?? null);
            $this->assertNull($job->payload['ssl']['expires_on'] ?? null);
            $this->assertNull($job->payload['ssl']['days_remaining'] ?? null);
            $this->assertNull($job->payload['ssl']['domain'] ?? null);
            $this->assertNull($job->payload['ssl']['algorithm'] ?? null);
            $this->assertNull($job->payload['ssl']['fingerprint'] ?? null);
            $this->assertNull($job->payload['ssl']['additional_domains'] ?? null);
            $this->assertNotNull($job->payload['ssl']['message']);

            $this->assertNotNull($job->payload['dns']['time'] ?? null);
            $this->assertNotNull($job->payload['dns']['records'] ?? null);

            $this->assertEquals('post', $job->httpVerb);
            $this->assertEquals(1, $job->tries);
            $this->assertEquals('Opsiebot/1.0', $job->headers['User-Agent']);
            $this->assertEquals($job->webhookUrl, 'https://app.test');
            $this->assertEquals($job->headers['Signature'], hash_hmac('sha256', json_encode($job->payload), 'secret'));

            return true;
        });
    }

    public function test_app_timing()
    {
        Queue::fake();

        $this->artisan('monitor', [
            'url' => 'https://google.com',
            '--metadata' => ['region=us'],
            '--webhook-url' => ['https://app.test'],
            '--webhook-secret' => ['secret'],
            '--dns-checking' => true,
            '--once' => true,
            '--verbose' => true,
        ]);

        Queue::assertPushed(CallWebhookJob::class, function ($job) {
            $this->assertTrue($job->payload['http']['timing']['app'] > 0);

            return true;
        });
    }
}
