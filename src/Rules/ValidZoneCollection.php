<?php

declare(strict_types=1);

namespace Hnooz\FilamentZoneMap\Rules;

use Closure;
use Hnooz\FilamentZoneMap\Data\ZoneCollection;
use Hnooz\FilamentZoneMap\Support\GeoJsonValidator;
use Hnooz\FilamentZoneMap\Support\ValidationError;
use Hnooz\FilamentZoneMap\Support\ZoneLimits;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates user-submitted zones: structure + input limits.
 *
 * Accepts a FeatureCollection array, a JSON string or a ZoneCollection.
 * Null is left to Laravel's `required`. With `required: true`, a collection
 * without features also fails (Laravel's `required` treats it as filled).
 */
final readonly class ValidZoneCollection implements ValidationRule
{
    public function __construct(
        private ?ZoneLimits $limits = null,
        private bool $required = false,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null) {
            return;
        }

        $payload = match (true) {
            $value instanceof ZoneCollection => $value->toArray(),
            is_string($value) => json_decode($value, true, 64),
            default => $value,
        };

        $error = GeoJsonValidator::firstError($payload, $this->limits ?? ZoneLimits::fromConfig());

        if ($error instanceof ValidationError) {
            $fail($error->translationKey())->translate($error->replace);

            return;
        }

        /** @var array{features: list<mixed>} $payload */
        if ($this->required && $payload['features'] === []) {
            $fail('validation.required')->translate();
        }
    }
}
