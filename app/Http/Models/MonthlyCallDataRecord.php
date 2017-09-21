<?php

namespace App\Http\Models;

use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class MonthlyCallDataRecord extends DBManModel
{
    
    use Eloquence, Mappable;

    protected $table = 'call_logs';
    
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
            'fileDate'=> 'File_Date',
            'fileName'=> 'File_Name',
            'entryDate'=>'Entry_Date',
            'billingType'=> 'Billing_Type',
            'airtimePackageAccountIdx'=> 'Airtime_Package_Account_Idx',
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
            'destService'=> 'Dest_Service',
            'destCountryCode'=> 'Dest_Country_Code',
            'origCountryCode'=> 'Orig_Country_Code',
            'origZone'=> 'Orig_Zone',
            'origIP'=> 'Orig_IP',
            'sessionID'=> 'SessionID',
            'custVolume'=> 'Cust_Volume',
            'custDuration'=> 'Cust_Duration',
            'custUnit'=> 'Cust_Unit',
            'rate'=> 'Rate',
            'price'=> 'Price',
            'surchargeRate'=> 'Surcharge_Rate',
            'surchargePrice'=> 'Surcharge_Price',
            'destNumberRate'=> 'Dest_Number_Rate',
            'destNumberPrice'=> 'Dest_Number_Price',
            'billedStatus'=> 'Billed_Status',
            'matchingPricelistKey'=> 'Matching_Pricelist_Key',
            'matchingScPricelistKey'=> 'Matching_Sc_Pricelist_Key',
            'updatedOn' => 'Updated_On'
    ];
    

}