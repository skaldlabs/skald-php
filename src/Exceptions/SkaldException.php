<?php

declare(strict_types=1);

namespace Skald\Exceptions;

use Exception;

/**
 * Exception thrown when Skald API operations fail.
 */
class SkaldException extends Exception
{
    /**
     * Create a new Skald API exception.
     *
     * @param int $httpStatus HTTP status code
     * @param string $responseBody Response body text
     * @return self
     */
    public static function fromApiError(int $httpStatus, string $responseBody): self
    {
        $message = sprintf('Skald API error (%d): %s', $httpStatus, $responseBody);
        return new self($message, $httpStatus);
    }
}
