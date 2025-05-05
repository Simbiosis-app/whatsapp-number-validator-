<?php

namespace Simbiosis\WhatsAppNumberValidator;

interface HttpClientInterface
{
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
    public function makeRequest(string $url, array $data = [], array $headers = [], string $method = 'POST'): array;
}