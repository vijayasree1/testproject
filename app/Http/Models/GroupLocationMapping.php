<?php
namespace App\Http\Models;

use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class GroupLocationMapping extends DBManModel
{
    use Auditable;
    use Eloquence;
    use Mappable;

    protected $table = 'group_locations';

    protected $primaryKey = 'Group_Location_Idx';

    public $timestamps = false;

    protected $fillable = ['Group_Idx','Location_Idx','Start_Date','End_Date','Updated_On','Last_Updated_By'];
    protected $maps=[];
}
