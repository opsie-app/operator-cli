<?php

namespace App\Concerns;

use Spatie\WebhookServer\WebhookCall;

trait SendsWebhooks
{
    use MonitorsHttp;

    /**
     * The list of webhooks with URLs and secrets.
     *
     * @var array
     */
    protected $webhooks = [];

    /**
     * Set the webhooks as arrays with 'url' and 'secret'
     * to send the HTTP/SSL payloads to.
     *
     * @param  array  $webhooks
     * @return $this
     */
    public function webhooks(array $webhooks)
    {
        $this->webhooks = $webhooks;

        return $this;
    }

    /**
     * Deliver the payload to the configured webhooks.
     *
     * @param  array  $payload
     * @return void
     */
    protected function deliverPayload(array $payload): void
    {
        foreach ($this->webhooks as $webhook) {
            WebhookCall::create()
                ->url($webhook['url'])
                ->payload($payload)
                ->useSecret($webhook['secret'])
                ->maximumTries(1)
                ->timeoutInSeconds($this->timeout)
                ->withHeaders(['User-Agent' => 'Opsiebot/1.0'])
                ->dispatch();
        }
    }
}
