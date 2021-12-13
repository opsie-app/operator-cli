<?php

namespace App\Concerns;

use RemotelyLiving\PHPDNS\Entities\DNSRecord;
use RemotelyLiving\PHPDNS\Entities\Hostname;
use RemotelyLiving\PHPDNS\Resolvers\Chain;
use RemotelyLiving\PHPDNS\Resolvers\CloudFlare;
use RemotelyLiving\PHPDNS\Resolvers\Dig;
use RemotelyLiving\PHPDNS\Resolvers\GoogleDNS;
use RemotelyLiving\PHPDNS\Resolvers\Interfaces\Resolver;
use RemotelyLiving\PHPDNS\Resolvers\LocalSystem;

trait MonitorsDns
{
    /**
     * Whether the monitor should check for DNS records.
     *
     * @var bool
     */
    protected $shouldCheckDns = false;

    /**
     * Specify the DNS servers to get through
     * to retrieve the DNS records.
     *
     * @var array
     */
    protected $dnsServers = [
        'cloudflare',
    ];

    /**
     * Enable DNS checking.
     *
     * @param  bool  $shouldCheckDns
     * @return $this
     */
    public function withDnsChecking(bool $shouldCheckDns = true)
    {
        $this->shouldCheckDns = $shouldCheckDns;

        return $this;
    }

    /**
     * Specify the DNS servers to use.
     *
     * @param  array  $dnsServers
     * @return $this
     */
    public function dnsCheckingServers(array $dnsServers = [])
    {
        if ($dnsServers) {
            $this->dnsServers = $dnsServers;
        }

        return $this;
    }

    /**
     * Check the DNS records and return a payload.
     *
     * @param  string  $url
     * @return array
     */
    protected function checkDns(string $url)
    {
        if (! $this->shouldCheckDns) {
            return [];
        }

        $payload = [
            'url' => $url,
            'time' => now()->toIso8601String(),
        ];

        $records = $this->getDnsChain()
            ->withAllResults()
            ->getRecords(parse_url($url, PHP_URL_HOST));

        return array_merge($payload, [
            'records' => collect($records)
                ->map(fn (DNSRecord $record) => $record->toArray())
                ->toArray(),
        ]);
    }

    /**
     * Get the DNS servers chain according to the config.
     *
     * @return \RemotelyLiving\PHPDNS\Resolvers\Chain
     */
    protected function getDnsChain()
    {
        $resolvers = collect($this->dnsServers)->map(function ($dns) {
            if ($dns instanceof Resolver) {
                return $dns;
            }

            return match ($dns) {
                'google' => new GoogleDNS(),
                'cloudflare' => new CloudFlare(),
                'local' => new LocalSystem(),
                default => new Dig(nameserver: Hostname::createFromString($dns)),
            };
        })->all();

        return new Chain(...$resolvers);
    }
}
