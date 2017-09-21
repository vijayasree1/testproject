<?php
namespace App\Http\Models;

use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class TerminalNetworkMapping extends DBManModel
{
    use Auditable;
    use Eloquence;
    use Mappable;

    protected $table = 'terminal_network_mapping';

    protected $primaryKey = 'Idx';

    public $timestamps = false;

    protected $fillable = ['IP_Allocation_Type','Active_YN_Flag','PDP_Allowed_YN_Flag','Last_Updated_By','Updated_On','Created_On','Created_By'];
    
    protected $maps = [];

}
