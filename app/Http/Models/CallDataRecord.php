<?php

namespace App\Http\Models;

use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * @SWG\Definition(required={"name", "Logs"})
 */
class CallDataRecord extends DBManModel
{
    /**
     * @SWG\Property(example="2016-06-20 23:45:41")
     * @var string
     */
    public $date;

    /**
     * @SWG\Property(example="ABC-123")
     * @var string
     */
    public $tailNumber;

    /**
     * @SWG\Property(example="901112115108495")
     * @var string
     */
    public $terminalIdentity;

    /**
     * @SWG\Property(example="870774900183")
     * @var string
     */
    public $origId;

    /**
     * @SWG\Property(example="INM-SBB")
     * @var string
     */
    public $origTech;

    /**
     * @SWG\Property(example="DATA_CLASS6")
     * @var string
     */
    public $origService;

    /**
     * @SWG\Property(example="internet")
     * @var string
     */
    public $destinationId;

    /**
     * @SWG\Property(example="LAND")
     * @var string
     */
    public $destimationTech;

    /**
     * @SWG\Property(example="UNDEF")
     * @var string
     */
    public $destinationService;

    /**
     * @SWG\Property(example="SATCOM1")
     * @var string
     */
    public $leso;

    /**
     * @SWG\Property(example="Megabyte")
     * @var string
     */
    public $unit;

    /**
     * @SWG\Property(example="1.000")
     * @var string
     */
    public $volume;

    /**
     * @SWG\Property(example="NOT_BILLED")
     * @var string
     */
    public $status;     

    use Eloquence, Mappable;

    protected $table = 'call_logs_all';

    protected $maps = [
        'date' => 'Date',
        'customerId' => 'Customer_Idx',
        'locationId' => 'Location_Idx',
        'tailNumber' => 'Location',
        'terminalIdentity' => 'Terminal_Identity',
        'origId' => 'Orig_ID',
        'origTech' => 'Orig_Tech',
        'origService' => 'Orig_Service',
        'destCountryCode'=>'Dest_Country_Code',
        'destinationId' => 'Dest_ID',
        'destinationTech' => 'Dest_Tech',
        'destinationService' => 'Dest_Service',
        'leso' => 'Leso',
        'unit' => 'Unit',
        'volume' => 'Volume',
        'billingType'=> 'Billing_Type',
        'status' => 'Billed_Status',
        'updatedOn'=> 'Updated_On'
    ];

}