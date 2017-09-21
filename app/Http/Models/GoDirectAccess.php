<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class GoDirectAccess extends DBManModel
{
    use Auditable;
    use Eloquence;
    use Mappable;
    
    protected $table = 'cb_radmin';
    
    protected $primaryKey = 'Terminal_Idx';
    
    public $timestamps = false;
    
    protected $maps = [
            'terminalId' => 'Terminal_Idx',
            'imsi' => 'IMSI',
            'onDate' => 'On_Date',
            'offDate' => 'Off_Date',
            'toUpdate'=>'to_update',
            'updatedOn'=>'Updated_On'
    ];
}
