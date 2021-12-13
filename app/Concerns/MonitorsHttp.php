<?php

namespace App\Concerns;

use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

trait MonitorsHttp
{
    /**
     * The HTTP method.
     *
     * @var string
     */
    protected $method = 'GET';

    /**
     * The HTTP POST body to send.
     *
     * @var array
     */
    protected $body = [];

    /**
     * Whether to make the HTTP POST request
     * with the application/x-www-form-urlencoded
     * header instead of application/json.
     *
     * @var bool
     */
    protected $postAsForm = false;

    /**
     * The list of headers to attach
     * to the request.
     *
     * @var array
     */
    protected $headers = [];

    /**
     * The Accept header value.
     *
     * @var string
     */
    protected $acceptHeader = 'application/json';

    /**
     * The timeout (in seconds) for
     * the request to finish.
     *
     * @var int
     */
    protected $timeout = 3;

    /**
     * The HTTP Basic Auth username.
     *
     * @var string|null
     */
    protected $username;

    /**
     * The HTTP Basic Auth password.
     *
     * @var string|null
     */
    protected $password;

    /**
     * Whether should use the digest auth instead
     * of the plain auth.
     *
     * @var bool
     */
    protected $useDigestAuth = false;

    /**
     * The bearer token to authorize the request.
     * Works only if $username is not set.
     *
     * @var string|null
     */
    protected $bearerToken;

    /**
     * Set the HTTP method for the request.
     *
     * @param  string  $method
     * @return $this
     */
    public function method(string $method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * The POST body to send.
     *
     * @param  array|null  $body
     * @return $this
     */
    public function body(?array $body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Whether to make the HTTP POST request
     * with the application/x-www-form-urlencoded
     * header instead of application/json.
     *
     * @param  bool  $postAsForm
     * @return $this
     */
    public function postAsForm(bool $postAsForm)
    {
        $this->postAsForm = $postAsForm;

        return $this;
    }

    /**
     * Set the headers to send.
     *
     * @param  array  $headers
     * @return $this
     */
    public function headers(array $headers)
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * Set the value of the Accept header.
     *
     * @param  string  $header
     * @return $this
     */
    public function acceptHeader(string $header)
    {
        $this->acceptHeader = $header;

        return $this;
    }

    /**
     * Set the request timeout.
     *
     * @param  int  $timeout
     * @return $this
     */
    public function timeout(int $timeout)
    {
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * Set the HTTP Basic Auth username.
     *
     * @param  string|null  $username
     * @return $this
     */
    public function username(?string $username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Set the HTTP Basic Auth password.
     *
     * @param  string|null  $password
     * @return $this
     */
    public function password(?string $password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Whether to use the Digest Auth instead of
     * the Plain Auth.
     *
     * @param  bool  $useDigestAuth
     * @return $this
     */
    public function useDigestAuth(bool $useDigestAuth = true)
    {
        $this->useDigestAuth = $useDigestAuth;

        return $this;
    }

    /**
     * Specify the bearer token for the request.
     * Gets overwritten in $username is set.
     *
     * @param  string|null  $bearerToken
     * @return $this
     */
    public function bearerToken($bearerToken)
    {
        $this->bearerToken = $bearerToken;

        return $this;
    }

    /**
     * Make HTTP checks and return a payload.
     *
     * @param  string  $url
     * @return array
     */
    protected function checkHttp(string $url): array
    {
        $payload = [
            'url' => $url,
            'time' => now()->toIso8601String(),
            'timing' => [],
            'up' => false,
            'status' => 0,
        ];

        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = $this->client()->{$this->method}($url, $this->body ?: []);

            $payload = array_merge($payload, [
                'status' => $response->status(),
                'up' => $response->successful(),
                'timing' => [
                    'total' => $this->getResponseStat($response, 'total_time'),
                    'dns_resolving' => $this->getResponseStat($response, 'namelookup_time'),
                    'ssl' => $this->getResponseStat($response, 'appconnect_time'),
                    'pre_transfer' => $this->getResponseStat($response, 'pretransfer_time'),
                    'start_transfer' => $this->getResponseStat($response, 'starttransfer_time'),
                ],
            ]);
        } catch (Exception $e) {
            $payload = array_merge($payload, [
                'message' => $e->getMessage(),
            ]);
        }

        return $payload;
    }

    /**
     * Create a new HTTP Client that acts
     * as a Guzzle intermediate.
     *
     * @return \Illuminate\Http\Client\PendingRequest
     */
    protected function client()
    {
        $client = $this->postAsForm
            ? Http::asForm()
            : Http::asJson();

        $client->accept($this->acceptHeader)
            ->timeout($this->timeout)
            ->withoutVerifying();

        if ($this->headers) {
            $client->withHeaders($this->headers);
        }

        if ($username = $this->username) {
            $password = $this->password;

            if ($this->useDigestAuth) {
                $client->withDigestAuth($username, $password);
            } else {
                $client->withBasicAuth($username, $password);
            }
        } elseif ($token = $this->bearerToken) {
            $client->withToken($token);
        }

        return $client;
    }

    /**
     * Pull the HTTP request statistic from the response.
     *
     * @param  \Illuminate\Http\Client\Response  $response
     * @param  string  $stat
     * @return int|float
     */
    protected function getResponseStat(Response $response, string $stat)
    {
        $stat = $response->transferStats->getHandlerStat($stat);

        return $stat ? $stat * 1000 : 0;
    }
}
