<?php

namespace App;

use App\Concerns\MonitorsDns;
use App\Concerns\MonitorsHttp;
use App\Concerns\MonitorsSsl;
use App\Concerns\SendsWebhooks;
use Illuminate\Support\Str;

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
     * @return static
     */
    public static function website(string $url)
    {
        return new static($url);
    }

    /**
     * Create a new Monitor instance.
     *
     * @param  string  $url
     * @return void
     */
    public function __construct(
        protected string $url,
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
        while (true) {
            $httpPayload = $this->checkHttp($this->url);
            $sslPayload = $this->checkSsl($this->url);
            $dnsPayload = $this->checkDns($this->url);

            $this->deliverPayload([
                'http' => $httpPayload,
                'ssl' => $sslPayload,
                'dns' => $dnsPayload,
                'metadata' => $this->metadata,
            ]);

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
