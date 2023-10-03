<?php

namespace App\Traits;

use App\Models\Scopes\UsableScope;

trait Usable
{
    protected static function booted(): void {
        static::addGlobalScope(new UsableScope);
    }
}
