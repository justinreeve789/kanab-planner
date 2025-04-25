<?php

namespace App\Traits;

use App\Models\Scopes\DataScope;
use App\Models\Scopes\ImageScope;
use App\Models\Scopes\UsableScope;

trait ResourceScopes
{
    protected static function booted(): void {
        static::addGlobalScope(new DataScope);
        static::addGlobalScope(new ImageScope);
        static::addGlobalScope(new UsableScope);
    }
}
