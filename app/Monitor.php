<?php

namespace App;

use App\Concerns\MonitorsDns;
use App\Concerns\MonitorsHttp;
use App\Concerns\MonitorsSsl;
use App\Concerns\SendsWebhooks;
use Illuminate\Support\Str;
use LaravelZero\Framework\Commands\Command;

class Monitor
{
    use MonitorsDns;
    use MonitorsHttp;
    use MonitorsSsl;
    use SendsWebhooks;

    /**
     * The interval between checks.
     *
     * @var int
     */
    protected $interval = 10;

    /**
     * Metadata for the monitoring.
     *
     * @var array
     */
    protected $metadata = [];

    /**
     * Specify if should run only one check then exit.
     *
     * @var bool
     */
    protected $once = false;

    /**
     * Create a new Monitor instance.
     *
     * @param  string  $url
     * @param  Command  $cli
     * @return static
     */
    public static function website(string $url, Command $cli)
    {
        return new static($url, $cli);
    }

    /**
     * Create a new Monitor instance.
     *
     * @param  string  $url
     * @param  Command  $cli
     * @return void
     */
    public function __construct(
        protected string $url,
        protected Command $cli,
    ) {
        $this->shouldCheckSsl = Str::startsWith($url, 'https://');
    }

    /**
     * Run the monitor.
     *
     * @return void
     */
    public function run(): void
    {
        if ($this->shouldCheckSsl) {
            $this->cli->line('The SSL checks are enabled.');
        }

        if ($this->shouldCheckDns) {
            $this->cli->line('The DNS checks and records retrievals are enabled.');
        }

        $this->cli->line("The checks will run at an interval of {$this->interval} seconds.");

        while (true) {
            $this->cli->line('['.now()->toIso8601String().'] Performing a check...', verbosity: 'v');

            $httpPayload = $this->checkHttp($this->url);
            $sslPayload = $this->checkSsl($this->url);
            $dnsPayload = $this->checkDns($this->url);

            $this->deliverPayload([
                'http' => $httpPayload,
                'ssl' => $sslPayload,
                'dns' => $dnsPayload,
                'metadata' => $this->metadata,
            ]);

            $this->cli->line('Check done.', verbosity: 'v');

            if ($this->once) {
                break;
            }

            sleep($this->interval);
        }
    }

    /**
     * Set the interval between checks.
     *
     * @param  int  $interval
     * @return $this
     */
    public function interval(int $interval)
    {
        $this->interval = $interval;

        return $this;
    }

    /**
     * Set metadata for the monitoring.
     *
     * @param  array  $metadata
     * @return $this
     */
    public function metadata(array $metadata)
    {
        $this->metadata = $metadata;

        return $this;
    }

    /**
     * Specify if should run only one check then exit.
     *
     * @param  bool  $once
     * @return $this
     */
    public function once(bool $once = true)
    {
        $this->once = $once;

        return $this;
    }
}
