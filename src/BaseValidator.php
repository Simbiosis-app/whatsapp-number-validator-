<?php

namespace Simbiosis\WhatsAppNumberValidator;

abstract class BaseValidator implements WhatsAppValidatorInterface
{
    /**
     * The configuration array.
     *
     * @var array
     */
    protected array $config;

    /**
     * Create a new validator instance.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->getDefaultConfig(), $config);
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
     * Format a phone number for validation.
     *
     * @param string $number
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function formatNumber(string $number): string
    {
        $number = trim($number);

        if (!preg_match('/^\+[1-9]\d+$/', $number)) {
            throw new \InvalidArgumentException('Invalid phone number format. Must be in international format (e.g., +1234567890)');
        }

        return $number;
    }

    /**
     * Format multiple phone numbers for validation.
     *
     * @param array $numbers
     * @return array
     * @throws \InvalidArgumentException
     */
    protected function formatNumbers(array $numbers): array
    {
        $formatted = [];
        $errors = [];

        foreach ($numbers as $number) {
            try {
                $formatted[$number] = $this->formatNumber($number);
            } catch (\InvalidArgumentException $e) {
                $errors[] = $e->getMessage();
            }
        }

        if (!empty($errors)) {
            throw new \InvalidArgumentException('Invalid phone numbers: ' . implode(', ', $errors));
        }

        return $formatted;
    }

    /**
     * Log an error message.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function logError(string $message, array $context = []): void
    {
        if (function_exists('error_log')) {
            error_log(sprintf(
                '[WhatsApp Number Validator] %s %s',
                $message,
                !empty($context) ? json_encode($context) : ''
            ));
        }
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
    protected function makeRequest(string $url, array $data = [], array $headers = [], string $method = 'POST'): array
    {
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
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error) {
            throw new \Exception('API request failed: ' . $error);
        }

        if ($httpCode !== 200) {
            throw new \Exception('API request failed with status code: ' . $httpCode);
        }

        $result = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid API response: ' . json_last_error_msg());
        }

        return $result;
    }

    /**
     * Format a phone number for API submission
     *
     * @param string $number
     * @return string
     */
    protected function formatNumberForApi(string $number): string
    {
        return preg_replace('/[^0-9]/', '', $number);
    }
}