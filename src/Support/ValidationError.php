<?php

declare(strict_types=1);

namespace Hnooz\FilamentZoneMap\Support;

/**
 * First problem found in a zone payload.
 *
 * `key` maps to `filament-zone-map::zone-map.validation.{key}`.
 * `detail` is an English, developer-facing explanation (never shown to end users).
 */
final readonly class ValidationError
{
    /**
     * @param  array<string, int|string>  $replace
     */
    public function __construct(
        public string $key,
        public string $detail,
        public array $replace = [],
    ) {}

    public static function invalid(string $detail): self
    {
        return new self('invalid', $detail);
    }

    public function translationKey(): string
    {
        return 'filament-zone-map::zone-map.validation.'.$this->key;
    }
}
