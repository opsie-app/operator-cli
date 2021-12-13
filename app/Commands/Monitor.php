<?php

namespace App\Commands;

use App\Monitor as AppMonitor;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class Monitor extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'monitor
        {url : The URL to check for SSL and HTTP.}
        {--method=POST : The HTTP method.}
        {--body= : JSON-formatted string with the body to call.}
        {--post-as-form : Send the request as form, with the application/x-www-form-urlencoded header.}
        {--header=* : Array list of key-value strings to set as headers.}
        {--accept-header=application/json : The Accept header value.}
        {--timeout=10 : The timeout of the request, in seconds.}
        {--interval=10 : The interval between checks, in seconds.}
        {--username= : The HTTP basic auth username. Enabling this will overwrite the --token value.}
        {--password= : The HTTP basic auth password. }
        {--digest-auth : Wether to use digest auth instead of plain auth.}
        {--bearer-token= : The Bearer token to authorize the request. Gets overwritten if --username is set.}
        {--metadata=* : Array list of key=value strings to set as metadata to Prometheus and to send off to payloads.}
        {--webhook-url=* : Array list of webhook URLs.}
        {--webhook-secret=* : Array list of secrets to sign the webhook URLs with.}
        {--once : Perform only one check, without monitoring the resource.}
    ';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Monitor the given website URL.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $monitor = AppMonitor::website($this->argument('url'))
            ->method($this->option('method'))
            ->body(@json_decode($this->option('body')))
            ->postAsForm($this->option('post-as-form'))
            ->headers($this->parseOptionAsKeyValue('header'))
            ->acceptHeader($this->option('accept-header'))
            ->timeout($this->option('timeout'))
            ->interval($this->option('interval'))
            ->once($this->option('once'))
            ->username($this->option('username'))
            ->password($this->option('password'))
            ->useDigestAuth($this->option('digest-auth'))
            ->bearerToken($this->option('bearer-token'))
            ->metadata($this->parseOptionAsKeyValue('metadata'))
            ->webhooks($this->getWebhooksWithSecrets());

        $monitor->run();
    }

    /**
     * Define the command's schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }

    /**
     * Transform key=value pairs from the array option
     * into key-value array.
     *
     * @param  string  $option
     * @return array
     */
    protected function parseOptionAsKeyValue(string $option): array
    {
        return collect($this->option($option))->mapWithKeys(function ($pair) {
            [$key, $value] = explode('=', $pair);

            return [$key => $value];
        })->toArray();
    }

    /**
     * Get the webhooks with their secrets.
     *
     * @return array
     */
    protected function getWebhooksWithSecrets(): array
    {
        if (
            ($webhookUrls = $this->option('webhook-url')) &&
            ($webhookSecrets = $this->option('webhook-secret'))
        ) {
            return collect($webhookUrls)
                ->zip($webhookSecrets)
                ->mapSpread(function ($url, $secret) {
                    return compact('url', 'secret');
                })
                ->toArray();
        }

        return [];
    }
}
