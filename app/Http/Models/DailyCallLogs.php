<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * @SWG\Definition(required={"name", "DailyCallLogs"})
 */
class DailyCallLogs extends DBManModel
{
    use Eloquence;
    use Mappable;

    protected $table = 'daily_call_logs';
    protected $primaryKey = 'Idx';

    /**
     * @SWG\Property(example=329)
     * @var int
     */
    public $id;

    /**
     * @SWG\Property(example="satcom1.bgan.inmarsat.com")
     * @var string
     */
    public $name;

    /**
     * @SWG\Property(example="Satcom1 APN String")
     * @var string
     */
    public $description;
    
    public $timestamps = false;

    protected $maps = [
        'id'=> 'Idx',
        'customerId'=> 'Customer_Idx',
        'systemId'=> 'System_Idx',
        'terminalId'=> 'Terminal_Idx',
        'locationId'=> 'Location_Idx',
        'serviceNumber'=> 'Service_Number',
        'terminalIdentity'=> 'Terminal_Identity',
        'terminalIdentityType'=> 'Terminal_Identity_Type',
        'leso'=> 'Leso',
        'currency'=>'Currency',
        'billedDate'=>'Billed_Date',
        'billingType'=> 'Billing_Type',
        'date'=> 'Date',
        'origID'=> 'Orig_ID',
        'destID'=> 'Dest_ID',
        'refNo'=> 'Ref_No',
        'duration'=> 'Duration',
        'noOfBits'=> 'No_Of_Bits',
        'unitType'=> 'Unit_Type',
        'origTech'=> 'Orig_Tech',
        'destTech'=> 'Dest_Tech',
        'origService'=> 'Orig_Service',
        'origIP'=> 'Orig_IP',
        'custVolume'=> 'Cust_Volume',
        'custDuration'=> 'Cust_Duration',
        'custUnit'=> 'Cust_Unit',
        'rate'=> 'Rate',
        'price'=> 'Price',
        'surchargeRate'=> 'Surcharge_Rate',
        'surchargePrice'=> 'Surcharge_Price',
        'matchingPricelistKey'=> 'Matching_Pricelist_Key',
        'matchingScPricelistKey'=> 'Matching_Sc_Pricelist_Key',
        'updatedOn' => 'Updated_On'
    ];
}
