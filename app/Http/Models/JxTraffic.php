<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class JxTraffic extends Model
{
    use Eloquence, Mappable;
    
    public $primaryKey = 'id';
    
    public $id;
    public $operatorId;
    public $operatorCustomerName;
    public $siteId;
    public $siteCustomerName;
    public $packageId;
    public $trafficUp;
    public $trafficDown;
    public $trafficStart;
    public $trafficUpUnit;
    public $trafficDownUnit;

    public $table = 'jx_traffic_raw';
}
