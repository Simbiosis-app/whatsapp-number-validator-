<?php

namespace Simbiosis\WhatsAppNumberValidator;

use Illuminate\Http\Client\Factory as LaravelHttpClientFactory;

class HttpClient implements HttpClientInterface
{
    /**
     * The configuration array.
     *
     * @var array
     */
    protected array $config;

    /**
     * The Laravel HTTP client factory if available.
     *
     * @var LaravelHttpClientFactory|null
     */
    protected ?LaravelHttpClientFactory $laravelClient = null;

    /**
     * Create a new HTTP client instance.
     *
     * @param array $config
     * @param LaravelHttpClientFactory|null $laravelClient
     */
    public function __construct(array $config = [], ?LaravelHttpClientFactory $laravelClient = null)
    {
        $this->config = array_merge($this->getDefaultConfig(), $config);

        // If Laravel client is provided, use it
        if ($laravelClient !== null) {
            $this->laravelClient = $laravelClient;
        }
        // Otherwise, try to auto-detect Laravel environment
        elseif (class_exists('Illuminate\Http\Client\Factory')) {
            try {
                $this->laravelClient = new LaravelHttpClientFactory();
            } catch (\Throwable) {
                // Silently fail and use cURL as fallback
            }
        }
    }

    /**
     * Get the default configuration.
     *
     * @return array
     */
    protected function getDefaultConfig(): array
    {
        return [
            'timeout' => 30,
            'retry_attempts' => 3,
        ];
    }

    /**
     * Make an HTTP request to the API
     *
     * @param string $url
     * @param array $data
     * @param array $headers
     * @param string $method
     * @return array
     * @throws \Exception
     */
    public function makeRequest(string $url, array $data = [], array $headers = [], string $method = 'POST'): array
    {
        // If Laravel HTTP client is available, use it (for better testability)
        if ($this->laravelClient !== null) {
            return $this->makeLaravelRequest($url, $data, $headers, $method);
        }

        // Fallback to cURL for vanilla PHP environments
        return $this->makeCurlRequest($url, $data, $headers, $method);
    }

    /**
     * Make an HTTP request using Laravel's HTTP client
     *
     * @param string $url
     * @param array $data
     * @param array $headers
     * @param string $method
     * @return array
     * @throws \Exception
     */
    protected function makeLaravelRequest(string $url, array $data = [], array $headers = [], string $method = 'POST'): array
    {
        // Convert headers from string format to associative array
        $headerArray = [];
        foreach ($headers as $header) {
            $parts = explode(': ', $header);
            if (count($parts) === 2) {
                $headerArray[$parts[0]] = $parts[1];
            }
        }

        // Create a request with the configured timeout and retry attempts
        $request = $this->laravelClient
            ->timeout($this->config['timeout'])
            ->retry($this->config['retry_attempts'], 100)
            ->withHeaders($headerArray);

        // Make the request based on the method
        $response = match (strtoupper($method)) {
            'GET' => $request->get($url, $data),
            'POST' => $request->post($url, $data),
            'PUT' => $request->put($url, $data),
            'PATCH' => $request->patch($url, $data),
            'DELETE' => $request->delete($url, $data),
            default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}")
        };

        // Return the JSON response as an array
        // Let the caller handle any response validation or error interpretation
        return $response->json() ?? [];
    }

    /**
     * Make an HTTP request using cURL
     *
     * @param string $url
     * @param array $data
     * @param array $headers
     * @param string $method
     * @return array
     * @throws \Exception
     */
    protected function makeCurlRequest(string $url, array $data = [], array $headers = [], string $method = 'POST'): array
    {
        if (!function_exists('curl_init')) {
            throw new \RuntimeException('cURL extension is required for HTTP requests in non-Laravel environments');
        }

        $ch = curl_init($url);

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => array_merge([
                'Content-Type: application/json',
            ], $headers),
            CURLOPT_TIMEOUT => $this->config['timeout'],
        ];

        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = json_encode($data);
        } elseif ($method === 'GET' && !empty($data)) {
            $url .= '?' . http_build_query($data);
            curl_setopt($ch, CURLOPT_URL, $url);
        }

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error) {
            throw new \Exception('API request failed: ' . $error);
        }

        // Decode the response to array and return
        // Let the caller handle any response validation or error interpretation
        $result = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON response: ' . json_last_error_msg());
        }

        return $result;
    }

    public function getLaravelClient() : LaravelHttpClientFactory
    {
        if ($this->laravelClient === null) {
            throw new \Exception('Laravel HTTP client not available');
        }

        return $this->laravelClient;
    }
}