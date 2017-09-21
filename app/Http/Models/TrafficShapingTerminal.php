<?php

namespace App\Http\Models;

use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class TrafficShapingTerminal extends DBManModel
{
    use Auditable;
    use Eloquence, Mappable;

    protected $table = 'terminal_traffic_shaping';
    protected $primaryKey = 'Idx';
    public $timestamps = false; //stops eloquent trying to insert timestamps //causing failure.
    
   /*  protected $maps = [
        'id' => 'Idx',
        'trafficResetDate' => 'Traffic_Reset_Date',
        'thresholdUnit' => 'Threshold_Unit',
        'thresholdValue' => 'Threshold_Value',
        'streamingRate' => 'Streaming_Rate',
        'startDate' => 'Start_Date',
        'endDate' => 'End_Date',
        'status' => 'Status',
    ]; */
    
    protected $hidden = ['Terminal_Idx','Updated_By','Updated_On'];
    
    protected $appends = [];
    
    protected $maps = [];

}