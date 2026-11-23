<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityType extends Model
{
    const BICYCLING = 'bicycling';
    const CHECKING_IN = 'checking-in';
    const CHECKING_OUT = 'checking-out';
    const COOKING = 'cooking';
    const DINING = 'dining';
    const EVENT = 'event';
    const HIKING = 'hiking';
    const SIGHTSEEING = 'sightseeing';

    use HasFactory;

    protected $guarded = ['id'];
    protected $table = 'activity_types';
    public $timestamps = false;

    private static array $ids = [];

    /**
     * id for a type name, memoized per request (itinerary generation looks these up for every activity).
     */
    public static function idFor(?string $name): ?int
    {
        if ($name === null) {
            return null;
        }
        if (!array_key_exists($name, self::$ids)) {
            self::$ids[$name] = self::where('name', $name)->value('id');
        }
        return self::$ids[$name];
    }

    public static function flushIdCache(): void
    {
        self::$ids = [];
    }
}
