<?php

namespace App\Http\Models;

use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class RadminSimUser extends DBManModel
{
    use Eloquence, Mappable;

    protected $table = 'radmin_sim_user';
    protected $primaryKey = 'SIM_User_Idx';
    public $timestamps = false;

    protected $maps = [
        'id' => 'SIM_User_Idx',
        'name' => 'SIM_User',
        'password' => 'SIM_Password',
        'isDefault' => 'is_default',
        'updatedOn' => 'Updated_On',
        'lastUpdatedBy' => 'Last_Updated_By',
        'userGroupId' => 'User_Group_Idx'
    ];

    protected $hidden = ['updatedOn', 'lastUpdatedBy', 'userGroupId'];

    public function userGroup()
    {
        return $this->belongsTo('App\Http\Models\RadminUserGroup', 'User_Group_Idx');
    }

    public function getIsDefaultAttribute()
    {
        return $this->attributes['Default_Sim_User_YN_Flag'] == 'Y';
    }
}