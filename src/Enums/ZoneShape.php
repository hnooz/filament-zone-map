<?php

declare(strict_types=1);

namespace Hnooz\FilamentZoneMap\Enums;

enum ZoneShape: string
{
    case Polygon = 'polygon';
    case Rectangle = 'rectangle';
    case Circle = 'circle';
    case Freehand = 'freehand';

    /**
     * Every shape the data contract accepts.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $shape): string => $shape->value, self::cases());
    }

    /**
     * Shapes shown in the toolbar when `shapes()` isn't set. Freehand is opt-in.
     *
     * @return list<string>
     */
    public static function defaults(): array
    {
        return [self::Polygon->value, self::Rectangle->value, self::Circle->value];
    }
}
