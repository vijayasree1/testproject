<?php
namespace App\Http\Models;

use OwenIt\Auditing\Auditable;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class OndTollFreeMapping extends DBManModel
{
    use Auditable;
    use Eloquence;
    use Mappable;

    protected $table = 'ond_toll_free_mapping';

    protected $primaryKey = 'Idx';

    public $timestamps = false;

    protected $maps = [
            'id' => 'Idx'
    ];
}
