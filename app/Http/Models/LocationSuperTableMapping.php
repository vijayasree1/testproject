<?php
namespace App\Http\Models;

use OwenIt\Auditing\Auditable;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class LocationSuperTableMapping extends DBManModel
{
    use Auditable;
    use Eloquence;
    use Mappable;

    protected $table = 'location_superTable_mapping';

    protected $primaryKey = 'Idx';

    public $timestamps = false;

    protected $maps = [
            'id' => 'Idx'
    ];
}
