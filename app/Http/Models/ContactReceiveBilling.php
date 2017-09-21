<?php

namespace App\Http\Models;

use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;
use Validator;

/**
 * @SWG\Definition(required={"name", "ContactReceiveBilling"})
 */

class ContactReceiveBilling extends DBManModel
{
    use Auditable;
    /**
     * @SWG\Property(example="TO")
     * @var string
     */
    protected $attentionType;  

    /**
     * @SWG\Property(example="AIRTIME,CONSULTANCY")
     * @var string
     */
    protected $receiveBillingFor;  

    use Eloquence, Mappable;

    protected $table = 'contacts_to_receive_billing';
    protected $primaryKey = 'Contact_Idx_for_E_mail';
    public $timestamps = false;

    protected $maps = [
        'customerId' => 'Customer_Idx',
        'contactId' => 'Contact_Idx_for_E_mail',
        'attentionType' => 'Attention_Type',
        'receiveBillingFor' => 'Receive_Billing_For',
    ];

 	protected $fillable = ['customerId', 'contactId', 'attentionType', 'receiveBillingFor'];
 	protected $hidden = ['customerId'];

 	public function contacts() 
    {
        return $this->belongsTo('App\Http\Models\Contact', 'contact', 'Contact_Idx_for_E_mail');
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
           
        }
        case 'PUT':
        {
            $rules = array(
                'receiveBillingFor' => 'required',
                'attentionType' => 'required',
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
