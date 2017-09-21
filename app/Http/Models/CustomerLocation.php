<?php

namespace App\Http\Models;

use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * @SWG\Definition(required={"name", "Locations"})
 */
class CustomerLocation extends DBManModel
{
    /**
     * @SWG\Property(example=329)
     * @var int
     */
    public $id;

    /**
     * @SWG\Property(example="ABC-12")
     * @var string
     */
    public $tailNumber;

    /**
     * @SWG\Property(example="1234")
     * @var string
     */
    public $serialNumber;

    /**
     * @SWG\Property(example="12345671")
     * @var string
     */
    public $icao;

    /**
     * @SWG\Property(example="BOEING")
     * @var string
     */
    public $make;

    /**
     * @SWG\Property(example="767-300")
     * @var string
     */
    public $model;

    /**
     * @SWG\Property(example="Denmark")
     * @var string
     */
    public $origin;

    /**
     * @SWG\Property(example="AIRCRAFT")
     * @var string
     */
    public $type;

    use Eloquence;
    use Mappable;

    protected $table = 'customer_locations';
    protected $primaryKey = 'Idx';

    protected $maps = [
        'id' => 'Idx',
        'locationId' => 'Location_Idx',
        'customerId' => 'Customer_Idx',
        'customerName' => 'Customer_Name'
    ];
}
