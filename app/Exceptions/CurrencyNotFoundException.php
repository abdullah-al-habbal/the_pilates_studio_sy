<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class CurrencyNotFoundException extends RuntimeException
{
    public static function forCode(string $code): self
    {
        return new self("Active currency with code [{$code}] not found.");
    }

    public static function forId(int $id): self
    {
        return new self("Currency with id [{$id}] not found.");
    }
}
