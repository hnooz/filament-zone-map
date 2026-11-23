<?php

declare(strict_types=1);

namespace Hnooz\FilamentZoneMap\Enums;

enum ZoneShape: string
{
    case Polygon = 'polygon';
    case Rectangle = 'rectangle';
    case Circle = 'circle';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $shape): string => $shape->value, self::cases());
    }
}
