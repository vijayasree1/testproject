<?php

namespace App\Http\Models;

use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;
use Validator;

/**
 * @SWG\Definition(required={"name", "Contacts"})
 */

class Contact extends DBManModel
{
    use Auditable;

	/**
     * @SWG\Property(example=329)
     * @var int
     */
    protected $id;

    /**
     * @SWG\Property(example="Bob")
     * @var string
     */
    protected $firstName;

    /**
     * @SWG\Property(example="")
     * @var string
     */
    protected $middleName;

    /**
     * @SWG\Property(example="Marley")
     * @var string
     */
    protected $lastName;

    /**
     * @SWG\Property(example="Mr")
     * @var string
     */
    protected $title;

    /**
     * @SWG\Property(example="Manager")
     * @var string
     */
    protected $position;

    /**
     * @SWG\Property(example="123 Wall Street")
     * @var string
     */
    protected $address;
    
    /**
     * @SWG\Property(example="Greve")
     * @var string
     */
    protected $address2;

    /**
     * @SWG\Property(example="")
     * @var string
     */
    protected $stateProvince;

    /**
     * @SWG\Property(example="4100")
     * @var string
     */
    protected $zipCode;

    /**
     * @SWG\Property(example="COPENHAGEN")
     * @var string
     */
    protected $city;     

    /**
     * @SWG\Property(example="DKK")
     * @var string
     */
    protected $countryCode;

    /**
     * @SWG\Property(example="+123456789")
     * @var string
     */
    protected $fixedPhone;   

    /**
     * @SWG\Property(example="+123456789")
     * @var string
     */
    protected $mobilePhone;           

    /**
     * @SWG\Property(example="+123456789")
     * @var string
     */
    protected $fax;   

    /**
     * @SWG\Property(example="jar@satcom1.com")
     * @var string
     */
    protected $email;  

    /**
     * @SWG\Property(example=439)
     * @var int
     */
    protected $customerId;

    /**
     * @SWG\Property(example="test")
     * @var string
     */
    protected $honeywellId; 

    use Eloquence;
    use Mappable;

    protected $table = 'contact';
    protected $primaryKey = 'Idx';
    public $timestamps = false; //stops eloquent trying to insert timestamps //causing failure.

    protected $maps = [
        'id' => 'Idx',
        'firstName' => 'First_Name',
        'middleName' => 'Middle_Name',
        'lastName' => 'Last_Name',
        'title' => 'Title',
        'position' => 'Position',
        'address' => 'Address',
        'address2' => 'Address2',
        'stateProvince' => 'State_Province',
        'zipCode' => 'ZIP_Code',
        'city' => 'City',
        'countryCode' => 'Country_Code',
        'fixedPhone' => 'Fixed_Phone',
        'mobilePhone' => 'Mobile_Phone',
        'fax' => 'Fax',
        'email' => 'Email',
        'customerId' => 'Customer_Idx',
        'honeywellId' => 'Honeywell_Id',
        'oldCustomerIdx' => 'Old_Customer_Idx',
        'endDate' => 'End_Date',
        'updatedOn' => 'Updated_On'
   
    ];

    protected $visible = ['roles', 'receive_billing', 'groups'];
    protected $fillable = ['firstName', 'middleName', 'lastName', 'title', 'position', 'address', 'address2', 'stateProvince', 'zipCode', 'city', 'countryCode', 'fixedPhone', 'mobilePhone', 'fax', 'email', 'customerId', 'honeywellId'];

    public function roles() 
    {
        return $this->belongsToMany('App\Http\Models\ContactRoleMaster', 'contact_roles', 'Contact_Idx', 'Role_Idx');
    }

    /**
    *   @SWG\Property(
    *   property="receive_billing",
    *   type="array",
    *   @SWG\Items(ref="#/definitions/ContactReceiveBilling"), 
    *   )
    */

    public function receive_billing() 
    {
        return $this->hasMany('App\Http\Models\ContactReceiveBilling','Contact_Idx_for_E_mail', 'Idx');
    }

    public function groups()
    {
        return $this->belongsToMany('App\Http\Models\Group', 'contact_groups', 'Contact_Idx', 'Group_Idx')->wherePivot('End_Date', '=', null);
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
                'firstName' => 'required|max:100|',
                'middleName' => 'max:255|',
                'lastName' => 'required',
                'title' => '',
                'position' => 'max:255|',
                'address' => 'max:255|',
                'address2' => 'max:255|',
                'stateProvince' => 'max:255|',
                'zipCode' => 'max:255|',
                'city' => 'max:255',
                'countryCode' => 'required|max:255',
                'fixedPhone' => 'max:255|',
                'mobilePhone' => 'max:255|',
                'fax' => 'max:255|',
                'email' => 'required|unique:contact,email,'.$data['id'].',Idx',
                'customerId' => 'required|exists:customer,Idx,Idx,'.$data['customerId'],
                'HoneywellId' => 'max:100|unique:contact,Honeywell_Id,'.$data['id'].',Idx',
            );
        }
        case 'PUT':
        {
            $rules = array(
                'firstName' => 'required|max:100|',
                'middleName' => 'max:255|',
                'lastName' => 'required', 
                'title' => '',
                'position' => 'max:255|',
                'address' => 'max:255|',
                'address2' => 'max:255|',
                'stateProvince' => 'max:255|',
                'zipCode' => 'max:255|',
                'city' => 'max:255',
                'countryCode' => 'required|max:255',
                'fixedPhone' => 'max:255|',
                'mobilePhone' => 'max:255|',
                'fax' => 'max:255|',
                'email' => 'required|unique:contact,email,'.$data['id'].',Idx',
                'customerId' => 'required|exists:customer,Idx,Idx,'.$data['customerId'],
                'honeywellId' => 'max:100|unique:contact,Honeywell_Id,'.$data['id'].',Idx',
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