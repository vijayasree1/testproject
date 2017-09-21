<?php

namespace App\Http\Models;

use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class Service extends DBManModel
{
    use Auditable;
    use Eloquence;
    use Mappable;

    protected $table = 'service';

    protected $primaryKey = 'Idx';

    public $timestamps = false;

    /**
    * The attributes that should be casted to native types.
    *
    * @var array
    */
    protected $casts = [
        'Number' => 'string',
    ];

    protected $maps = [
        'id' => 'Idx',
        'number' => 'Number',
        'service' => 'Service',
        'data1' => 'Data1',
        'data2' => 'Data2',
        'data3' => 'Data3',
        'activationDate' => 'Activation_Date',
        'deactivationDate' => 'Deactivation_Date',
        'terminalId' => 'Terminal_Idx',
        'updatedOn'=>'Updated_On'
    ];

}
