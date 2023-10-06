<?php

namespace App\Traits;

use App\Models\Scopes\UsableScope;

trait ResourceScopes
{
    protected static function booted(): void {
        static::addGlobalScope(new UsableScope);
    }
}
