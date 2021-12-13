<?php

namespace App\Concerns;

use Exception;
use Spatie\SslCertificate\SslCertificate;

trait MonitorsSsl
{
    /**
     * Whether the monitor should check for SSL.
     *
     * @var bool
     */
    protected $shouldCheckSsl = false;

    /**
     * Check the SSL certificate and return a payload.
     *
     * @param  string  $url
     * @return array
     */
    protected function checkSsl(string $url)
    {
        if (! $this->shouldCheckSsl) {
            return [];
        }

        $payload = [
            'url' => $url,
            'time' => now()->toIso8601String(),
            'valid' => false,
        ];

        try {
            $certificate = SslCertificate::createForHostName(
                parse_url($url, PHP_URL_HOST),
            );

            $payload = array_merge($payload, [
                'issuer' => $certificate->getIssuer(),
                'expired' => $certificate->isExpired(),
                'valid' => $certificate->isValid(),
                'valid_from' => $certificate->validFromDate()->toIso8601String(),
                'expires_on' => $certificate->expirationDate()->toIso8601String(),
                'days_remaining' => $certificate->lifespanInDays(),
                'domain' => $certificate->getDomain(),
                'algorithm' => $certificate->getSignatureAlgorithm(),
                'fingerprint' => $certificate->getFingerprint(),
                'additional_domains' => $certificate->getAdditionalDomains(),
            ]);
        } catch (Exception $e) {
            $payload = array_merge($payload, [
                'message' => $e->getMessage(),
            ]);
        }

        return $payload;
    }
}
