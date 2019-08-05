<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait;

/**
 * Description of IpBlock
 *
 * @author asok1
 */
class Map extends Model
{

    use SpatialTrait;

    protected $table = "maps";
    protected $casts = [
        'updated_at' => 'datetime:c',
        'created_at' => 'datetime:c',
    ];
    protected $with = ['features'];
    protected $fillable = [
        'name',
        'slug',
        'center',
        'min_zoom',
        'max_zoom',
        'zoom',
        'label_zoom',
        'features'
    ];
    protected $spatialFields = [
        'center'
    ];

    public function features() {
        return $this->hasMany('App\Models\Feature');
    }

    protected static function boot() {
        parent::boot();

        static::addGlobalScope('order', function (Builder $builder) {
            $builder->orderBy('updated_at', 'DESC');
        });
    }

}
