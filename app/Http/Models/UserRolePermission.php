<?php

namespace App\Http\Models;

use App\Http;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class UserRolePermission extends DBManModel
{
    use Eloquence;
    use Mappable;

    protected $table = 'viewpermissions';
    protected $primaryKey = 'id';

    protected $maps = [
        'usersId' => 'Users_ID',
        'usersName' => 'Users_Name',
        'usersEmail' => 'Users_Email',
        'usersToken' => 'Users_Token',
        'rolesName'  => 'Roles_Name',
        'rolesLabel' => 'Roles_Label',
        'permissionsId' => 'Permissions_Id',
        'permissionsName' => 'Permissions_Name',
        'permissionsLabel' => 'Permissions_Label',
        'permissionsUserId' => 'Permissions_User_Id',
    ];


 
}
