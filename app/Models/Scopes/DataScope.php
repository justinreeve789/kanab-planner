<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class DataScope implements Scope
{
    /**
     * @param  \Illuminate\Database\Eloquent\Builder $builder
     * @param  \Illuminate\Database\Eloquent\Model $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        $builder->with('data');
    }

//    public function extend(Builder $builder)
//    {
//        $builder->macro('applyKeyedData', function () use ($builder) {
//            $builder->get()->each(function ($model) {
//                if ($model->relationLoaded('data')) {
//                    $model->setRelation('data', $model->data->keyBy('activity_type_id'));
//                }
//            });
//            return $builder;
//        });
//    }
}
