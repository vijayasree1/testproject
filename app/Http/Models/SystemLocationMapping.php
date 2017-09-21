<?php
namespace App\Http\Models;

use OwenIt\Auditing\Auditable;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class SystemLocationMapping extends DBManModel
{
    use Auditable;
    use Eloquence;
    use Mappable;

    protected $table = 'system_location_mapping';

    protected $primaryKey = 'Idxx';

    public $timestamps = false;

    protected $maps = [
            'id' => 'Idxx',
            'systemId' => 'System_Idx',
            'locationId' => 'Location_Idx',
            'startDate' => 'Start_Date',
            'endDate' => 'End_Date',
            'updatedOn'=>'Updated_On'
    ];
}
