<?php

declare(strict_types=1);

arch('no debugging functions are left in the code')
    ->expect(['dd', 'dump', 'ray', 'var_dump', 'die'])
    ->not->toBeUsed();

arch('all source files use strict types')
    ->expect('Hnooz\FilamentZoneMap')
    ->toUseStrictTypes();

arch('classes are final unless designed for extension')
    ->expect('Hnooz\FilamentZoneMap')
    ->classes()
    ->toBeFinal()
    ->ignoring(['Hnooz\FilamentZoneMap\Concerns', 'Hnooz\FilamentZoneMap\Tests', 'Hnooz\FilamentZoneMap\Widgets\ZonesMapWidget']);
