<?php

namespace App\Http\Models;

use OwenIt\Auditing\Auditable;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;


/**
 * @SWG\Definition(required={"name", "trafficShapingNotification"})
 */

class TrafficShapingNotification extends DBManModel
{
    use Auditable;
    use Eloquence;
    use Mappable;

    protected $table = 'traffic_shapping_contact_notifications';
    protected $primaryKey = 'Idx';
    
    public $timestamps = false;

    protected $maps = [
        'id' => 'Idx',
        'email' => 'Email'
    ];
    
    protected $hidden = ['Terminal_Idx','Message_Type','Percentage','Updated_On'];
}