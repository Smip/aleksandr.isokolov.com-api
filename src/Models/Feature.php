<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Description of IpBlock
 *
 * @author asok1
 */
class Feature extends Model
{

    use SpatialTrait;
    use SoftDeletes;

    protected $table = "features";
    protected $casts = [
        'updated_at' => 'datetime:c',
        'created_at' => 'datetime:c',
    ];
    
    
    
    protected $fillable = [
        'name',
        'feature'
    ];
    protected $spatialFields = [
        'feature'
    ];

    public function map() {
        return $this->belongsTo('App\Models\Map');
    }

}
