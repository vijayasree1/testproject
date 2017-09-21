<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Models\Permission;
use App\Http\Models\Role;
use Illuminate\Support\Facades\Input;

class RolePermissionsController extends Controller
{
    public function getPermissions()
    {
        return Permission::select('id', 'name', 'label')->get();
    }

    public function viewPermissions()
    {
        $roles = Role::all();
        $permissions = Permission::all();

        return view('permissions');
    }

    public function getRoles()
    {
        return Role::with(['permissions' => function($query) {
            $query->select('id', 'name', 'label');
        }])->get();
    }

    public function updateRoles()
    {
        $roles = Role::all();
        foreach( $roles as $role ) {
            $role->permissions()->sync([]);
        }

        $roles = Input::all();

        foreach( $roles as $roleId => $permissions ) {
            $role = Role::find($roleId);
            $role->permissions()->sync($permissions);
        }

        return response()->json([
            'status' => 'success'
        ]);
    }
}