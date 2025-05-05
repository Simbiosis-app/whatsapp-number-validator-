<?php

namespace Simbiosis\WhatsAppNumberValidator;

class Logger
{
    /**
     * The name of the logger.
     *
     * @var string
     */
    protected string $name;

    /**
     * Create a new logger instance.
     *
     * @param string $name
     */
    public function __construct(string $name = 'WhatsApp Number Validator')
    {
        $this->name = $name;
    }

    /**
     * Log an error message.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function error(string $message, array $context = []): void
    {
        if (function_exists('error_log')) {
            error_log(sprintf(
                '[%s] %s %s',
                $this->name,
                $message,
                !empty($context) ? json_encode($context) : ''
            ));
        }
    }

    /**
     * Log an info message.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function info(string $message, array $context = []): void
    {
        if (function_exists('error_log')) {
            error_log(sprintf(
                '[%s] INFO: %s %s',
                $this->name,
                $message,
                !empty($context) ? json_encode($context) : ''
            ));
        }
    }
}