<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * @SWG\Definition(required={"name", "ContactRoleMaster"})
 */

class ContactRoleMaster extends DBManModel
{

    /**
     * @SWG\Property(example="test@test.com")
     * @var string
     */
    protected $email;

    /**
     * @SWG\Property(example="200")
     * @var int
     */
    protected $customerId;

    use Eloquence, Mappable;

    protected $table = 'contact_roles_master';
    protected $primaryKey = 'Role_Idx';

    protected $maps = [
        'id' => 'Role_Idx',
        'name' => 'Role_Name',
        'description' => 'Role_Description',
        'updated_at' => 'Updated_On',
    ];

 	protected $fillable = ['id', 'name', 'description', 'updated_at'];


    /**
    *   @SWG\Property(
    *   property="roles",
    *   type="array",
    *   @SWG\Items(ref="#/definitions/Role"), 
    *   )
    */


 	public function contacts() 
    {
        return $this->belongsToMany('App\Http\Models\Contact', 'contact_roles', 'Role_Idx', 'Contact_Idx');
    }

}
