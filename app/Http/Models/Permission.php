<?php
namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use Larapacks\Authorization\Traits\PermissionRolesTrait;

class Permission extends Model
{
    use PermissionRolesTrait;
}