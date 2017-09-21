<?php
namespace App\Http\Models;

use OwenIt\Auditing\Auditable;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class SystemAirtimePackageMapping extends DBManModel
{
    use Auditable;
    use Eloquence;
    use Mappable;

    protected $table = 'system_airtime_package_account_mapping';

    protected $primaryKey = 'Idx';

    public $timestamps = false;

    protected $maps = [
            'id' => 'Idx'
    ];
}
