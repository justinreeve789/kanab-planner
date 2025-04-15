<?php

namespace App\Traits;

trait InsertWithValues
{
    /**
     * Example usage: Trail::insertWithValues(['id' => $newId], $trailData)
     *
     * @param array $defaults
     * @param array $items
     * @return bool
     */
    public static function insertWithValues(array $items, array $defaults): bool
    {
        $merged = array_merge($defaults, $items);
        return self::insert([$merged]);
    }
}
