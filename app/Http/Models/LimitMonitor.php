<?php
namespace App\Http\Models;

use OwenIt\Auditing\Auditable;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class LimitMonitor extends DBManModel
{
    use Auditable;
    use Eloquence;
    use Mappable;

    protected $table = 'limit_monitor';

    protected $primaryKey = 'Idx';

    public $timestamps = false;

    protected $maps = [
            'id' => 'Idx'
    ];
}
