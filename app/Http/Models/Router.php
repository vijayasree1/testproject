<?php

namespace App\Http\Models;

use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class Router extends DBManModel
{
    use Eloquence, Mappable;

    protected $primaryKey = 'Idx';
    protected $table = 'location_router_mapping';

    protected $maps = [
        'manufacturer' => 'routerModel.routerManufacturer.Name',
        'model' => 'routerModel.Router_Model',
        'type' => 'routerModel.Router_Type',
        'serialNumber' => 'Router_Serial_Number'
    ];

    public function routerModel()
    {
        return $this->belongsTo('App\Http\Models\RouterModel', 'Router_Model_Data_Idx');
    }
}