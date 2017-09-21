<?php
namespace App\Http\Models;

use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class ContactGroupMapping extends DBManModel
{
    use Auditable;
    use Eloquence;
    use Mappable;

    protected $table = 'contact_groups';

    protected $primaryKey = 'Contact_Group_Id';

    public $timestamps = false;

    protected $fillable = ['Group_Idx','Contact_Idx','Start_Date','End_Date'];
    protected $maps=[];
}
