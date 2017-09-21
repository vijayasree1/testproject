<?php
namespace App\Http\Models;

use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class TerminalApnMapping extends DBManModel
{
    use Auditable;
    use Eloquence;
    use Mappable;

    protected $table = 'terminal_apn_mapping';

    protected $primaryKey = 'Idx';

    public $timestamps = false;

    protected $fillable = ['Terminal_Idx','APN_Idx'];
    protected $maps=[];
}
