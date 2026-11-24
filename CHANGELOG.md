# Changelog

All notable changes to `filament-zone-map` will be documented in this file.

## v1.1.0 - Unreleased

- Freehand drawing: add `freehand` to `shapes()` to trace zones by hand (`ZoneShape::Freehand`)
- Freehand zones can be selected, moved, named, recolored and deleted
- The default toolbar is unchanged (polygon, rectangle, circle)

## v1.0.0 - 2026-11-24

First release.

- `ZoneMap` form field: draw polygons, rectangles and circles with Terra Draw on Google Maps; select, drag, reshape, delete and clear zones
- Zone editor panel: name and color per zone
- `ZoneMapEntry` infolist entry: read-only map with zone info windows
- `ZonesMapWidget`: zones of many records on one map with record links
- `AsZoneCollection` cast, `ZoneCollection` / `Zone` value objects, `ValidZoneCollection` rule
- Map styling via Map ID (light/dark color scheme) or JSON styles; follows Filament's theme
- Limits: max zones, max vertices, max name length
- English translations, dark mode, PHP 8.3+, Laravel 12/13, Filament 5
