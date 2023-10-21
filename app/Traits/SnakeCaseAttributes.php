<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait SnakeCaseAttributes
{
    public function __get($key)
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->getAttribute($key);
        }

        $snakeKey = Str::snake($key);

        if (array_key_exists($snakeKey, $this->attributes)) {
            return $this->getAttribute($snakeKey);
        }

        return parent::__get($key);
    }

    public function __set($key, $value)
    {
        $snakeKey = Str::snake($key);

        if (array_key_exists($snakeKey, $this->attributes)) {
            $this->setAttribute($snakeKey, $value);
        } else {
            parent::__set($key, $value);
        }
    }
}