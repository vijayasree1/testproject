<?php

namespace App\Http\Models;

use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class RadminUserGroup extends DBManModel
{
    use Eloquence, Mappable;

    protected $table = 'radmin_user_group';
    protected $primaryKey = 'User_Group_Idx';
    public $timestamps = false;

    protected $maps = [
        'id' => 'User_Group_Idx',
        'name' => 'User_Group',
        'isDefault' => 'is_default',
        'updatedOn' => 'Updated_On',
        'lastUpdatedBy' => 'Last_Updated_By'
    ];

    protected $hidden = ['updatedOn', 'lastUpdatedBy', 'default'];

    public function getIsDefaultAttribute()
    {
        return $this->attributes['Default_User_Group_YN_Flag'] == 'Y';
    }
}