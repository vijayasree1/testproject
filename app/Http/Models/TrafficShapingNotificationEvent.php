<?php

namespace App\Http\Models;

use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class TrafficShapingNotificationEvent extends DBManModel
{
    use Eloquence;
    use Mappable;

    protected $table = 'traffic_shapping_notifications';
    protected $primaryKey = 'Idx';

    public $timestamps = false;

    protected $maps = [];
}