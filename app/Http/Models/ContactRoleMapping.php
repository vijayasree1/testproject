<?php
namespace App\Http\Models;

use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class ContactRoleMapping extends DBManModel
{
    use Auditable;
    use Eloquence;
    use Mappable;

    protected $table = 'contact_roles';

    protected $primaryKey = 'Idx';

    public $timestamps = false;

    protected $fillable = ['Role_Idx','Contact_Idx','Created_On','Updated_On','Created_By','Active_YN_Flag','Last_Updated_By'];
    protected $maps=[];
}
