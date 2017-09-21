<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class GoDirectActivation extends DBManModel
{
    use Auditable;
    use Eloquence;
    use Mappable;

    protected $table = 'cb_activation_logging';

    protected $primaryKey = 'Idx';

    public $timestamps = false;

    protected $maps = [
            'id' => 'Idx',
            'terminalId' => 'Terminal_Idx',
            'locationId' => 'Location_Idx',
            'activationDate' => 'Activation_Date',
            'deactivationDate' => 'Deactivation_Date',
            'comments' => 'Comments',
            'updatedOn'=>'Updated_On'
    ];
}
