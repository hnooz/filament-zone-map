<?php

declare(strict_types=1);

namespace Hnooz\FilamentZoneMap\Tests\Fixtures;

use Hnooz\FilamentZoneMap\Casts\AsZoneCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

final class ZoneRecord extends Model
{
    public $timestamps = false;

    protected $table = 'zone_records';

    protected $guarded = [];

    public static function createTable(): void
    {
        Schema::create('zone_records', static function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->json('coverage')->nullable();
        });
    }

    protected function casts(): array
    {
        return ['coverage' => AsZoneCollection::class];
    }
}
