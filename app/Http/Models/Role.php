<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use Larapacks\Authorization\Traits\RolePermissionsTrait;

/**
 * @SWG\Definition(required={"name", "Role"})
 */

class Role extends Model
{

	/**
     * @SWG\Property(example=1)
     * @var int
     */
    protected $id;

    /**
     * @SWG\Property(example="Role Name")
     * @var string
     */
    protected $name;

    /**
     * @SWG\Property(example="Test Role")
     * @var string
     */
    protected $label;

    /**
     * @SWG\Property(example="2016-08-17 12:14:20")
     * @var string
     */
    protected $created_at;

    /**
     * @SWG\Property(example="2016-08-17 12:14:20")
     * @var string
     */
    protected $updated_at;

    protected $hidden = ['pivot'];
    use RolePermissionsTrait;
}