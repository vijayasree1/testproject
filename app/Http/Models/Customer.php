<?php

namespace App\Http\Models;

use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;


/**
 * @SWG\Definition(required={"name", "Customers"})
 */

class Customer extends DBManModel
{

	/**
     * @SWG\Property(example=329)
     * @var int
     */
    public $id;

    /**
     * @SWG\Property(example="Satcom1 APS")
     * @var string
     */
    public $customerName;

    /**
     * @SWG\Property(example="123 City Road")
     * @var string
     */
    public $address;

    /**
     * @SWG\Property(example="Building 28")
     * @var string
     */
    public $address2;

    /**
     * @SWG\Property(example="Copenhagen")
     * @var string
     */
    public $stateProvince;

    /**
     * @SWG\Property(example="2100")
     * @var string
     */
    public $zipCode;

    /**
     * @SWG\Property(example="Copenhagen")
     * @var string
     */
    public $city;
    
    /**
     * @SWG\Property(example="DKK")
     * @var string
     */
    public $countryCode;

    /**
     * @SWG\Property(example="123456789")
     * @var string
     */
    public $RegNo;

    /**
     * @SWG\Property(example="123456789")
     * @var string
     */
    public $VatNo;

    /**
     * @SWG\Property(example="2015-01-01 00:00:00")
     * @var string
     */
    public $startDate;     

    /**
     * @SWG\Property(example="null")
     * @var string
     */
    public $endDate;        

    use Eloquence;
    use Mappable;

    protected $table = 'customer';
    protected $primaryKey = 'Idx';

    protected $maps = [
        'id' => 'Idx',
        'customerName' => 'Company',
        'address' => 'Billing_Address',
        'address2' => 'Billing_Address2',
        'stateProvince' => 'Billing_State_Province',
        'zipCode' => 'Billing_Zip_Code',
        'city' => 'Billing_City',
        'countryCode' => 'Billing_Country_Code',
        'RegNo' => 'Company_Reg_No',
        'VatNo' => 'Company_VAT_No',
        'chargeVAT' =>'Charge_VAT',
        'startDate' => 'Customer_Starts',
        'endDate' => 'Customer_Ends',
        'invoiceCurrency' => 'Invoice_Currency',
        'domainName' => 'Domain_Name',
        'comments' => 'Comments',
        'newBilling' => 'New_Billing',
        'primaryContactId' => 'Billing_Contact_Idx',
        'salesRepId' => 'Sales_Rep_Idx',
        'sapNumber' => 'sapNumber_customer.SAP_Customer_Idx',
        'isManagementCompany' => 'Is_Management_Company',
        'billingMonths' => 'Billing_Months',
        'category' => 'customerCategory.Category',
        'updatedOn'=>'Updated_On',
    ];

    public $visible = ['sales_rep','receive_billing','email_recipients'];

    public function sales_rep() {
        return $this->hasOne('App\Http\Models\Contact', 'Idx', 'Sales_Rep_Idx');
    }

    public function cabinBillingPurchases() {
        return $this->hasMany('App\Http\Models\CabinBillingPurchase', 'Customer_Idx');
    }
    
    public function email_recipients()
    {
        return $this->hasMany('App\Http\Models\ContactReceiveBilling', 'Customer_Idx');
    }
    
    public function customerCategory()
    {
        return $this->belongsTo('App\Http\Models\CustomerCategory', 'Category');
    }
    
    public function sapNumber_customer()
    {
        return $this->hasOne('App\Http\Models\SapNumber','Customer_Idx', 'Idx');
    }
}