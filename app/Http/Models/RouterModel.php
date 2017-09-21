<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class RouterModel extends Model
{
    use Eloquence, Mappable;

    protected $primaryKey = 'Idx';
    protected $table = 'router_model_data';

    public function routerManufacturer()
    {
        return $this->belongsTo('App\Http\Models\RouterManufacturer', 'Manufacturer_Router_Idx');
    }
}