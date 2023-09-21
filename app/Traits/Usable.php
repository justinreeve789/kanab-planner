<?php

namespace App\Traits;

use App\Scopes\UsableScope;

trait Usable
{
    protected static function booted()
    {
        static::addGlobalScope(new UsableScope);
    }
}
