<?php

declare(strict_types=1);

namespace Hnooz\FilamentZoneMap\Exceptions;

use Hnooz\FilamentZoneMap\Support\ValidationError;
use InvalidArgumentException;

final class InvalidZoneData extends InvalidArgumentException
{
    public static function fromError(ValidationError $error): self
    {
        return new self("Invalid zone data [{$error->key}]: {$error->detail}");
    }

    public static function invalidJson(string $reason): self
    {
        return new self("Invalid zone data [invalid]: malformed JSON ({$reason}).");
    }
}
