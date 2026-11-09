<?php

namespace Rasel673\EcommerceAI\Exceptions;

use RuntimeException;
use Throwable;

class AIProviderException extends RuntimeException
{
    /**
     * Create a new exception instance.
     *
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

