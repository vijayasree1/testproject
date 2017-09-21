<?php

namespace App\Http\Models;

use OwenIt\Auditing\Auditable;
use App\Http;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;
use Validator;


/**
 * @SWG\Definition(required={"name", "Groups"})
 */

class Group extends DBManModel
{

    use Auditable;
    /**
     * @SWG\Property(example=2)
     * @var int
     */
    protected $groupId;

    /**
     * @SWG\Property(example="Group Name Here")
     * @var string
     */
    protected $groupName;

    /**
     * @SWG\Property(example=200)
     * @var int
     */
    protected $customerId;

    /**
     * @SWG\Property(example="XXX")
     * @var string
     */
    protected $startDate;

    /**
     * @SWG\Property(example="XXX")
     * @var string
     */
    protected $endDate;

    use Eloquence;
    use Mappable;

    protected $table = 'groups';
    protected $primaryKey = 'Group_Idx';
    public $timestamps = false; //stops eloquent trying to insert timestamps //causing failure.
    private $errors;
    protected $maps = [
        'groupId' => 'Group_Idx',
        'groupName' => 'Group_Name',
        'groupDescription' => 'Group_Description',
        'customerId' => 'Customer_Idx',
        'startDate' => 'Start_Date',
        'endDate' => 'End_Date',
    ];
    protected $appends = ['groupId', 'groupName', 'groupDescription',  'startDate', 'endDate', 'customerId', 'contactGroupId'];
    protected $visible = ['contacts', 'locations'];
    protected $fillable = ['groupId','groupName', 'customerId', 'groupDescription' ,'startDate', 'endDate'];

    /**
    *   @SWG\Property(
    *   property="contacts",
    *   type="array",
    *   @SWG\Items(ref="#/definitions/Contact"), 
    *   )
    */

    public function contacts()
    {   
        return $this->belongsToMany('App\Http\Models\Contact', 'contact_groups', 'Group_Idx', 'Contact_Idx')->wherePivot('End_Date', '=', null);
    }

    /**
    *   @SWG\Property(
    *   property="locations",
    *   type="array",
    *   @SWG\Items(ref="#/definitions/Location"), 
    *   )
    */

    public function locations()
    {
        return $this->belongsToMany('App\Http\Models\Location', 'group_locations', 'Group_Idx', 'Location_Idx')->wherePivot('End_Date', '=', null);
    }

    public function errors()
    {
        return $this->errors;
    }
    
    public function validate($method)
    {

    $data = $this->toArray();

    switch($method)
    {
        case 'GET':
        case 'DELETE':
        case 'POST':
        {
            $rules = array(
                'groupName' => 'required|max:100|unique:groups,Group_Name,NULL,Group_Idx,End_Date,NULL,Customer_Idx,'.$data['customerId'],
                'groupDescription' => 'required|max:255|',
                'customerId' => 'required|exists:customer,Idx,Idx,'.$data['customerId'],
                'startDate' => 'date_format:Y-m-d',
                'endDate' => 'date_format:Y-m-d',
            );
        }
        case 'PUT':
        {
            $rules = array(
                'groupName' => 'required|max:100|unique:groups,Group_Name,'.$data['groupId'].',Group_Idx,End_Date,NULL,Customer_Idx,'.$data['customerId'],
                'groupDescription' => 'required|max:255|',
                'customerId' => 'required|exists:customer,Idx,Idx,'.$data['customerId'],
                'startDate' => 'date_format:Y-m-d',
                'endDate' => 'date_format:Y-m-d',
            );
        }
        case 'PATCH':
        default:break;
    }

    // make a new validator object
    $v = Validator::make($data, $rules);

    // check for failure
    if ($v->fails())
    {
        // set errors and return false
        $this->errors = $v->errors();
        return false;
    }

    // validation pass
    return true;

}

}