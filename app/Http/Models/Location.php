<?php

namespace App\Http\Models;

use OwenIt\Auditing\Auditable;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * @SWG\Definition(required={"name", "Locations"})
 */
class Location extends DBManModel
{
    use Auditable;
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

    protected $table = 'location';
    protected $primaryKey = 'Idx';
	
    public $timestamps = false;

    protected $maps = [
        'id' => 'Idx',
        'tailNumber' => 'Location',
        'serialNumber' => 'Aircraft_Serial_No',
        'icao' => 'aircraftIcao.ICAO',
        'make' => 'aircraftModel.make.Make',
        'model' => 'aircraftModel.Model',
        'origin' => 'Country_Of_Registration',
        'type' => 'Location_Type',
        'customerId' => 'customer.Customer_Idx',
        'customerName' => 'customer.Customer_Name',
        'modelId' => 'Model_Idx',
        'updatedOn'=>'Updated_On'
    ];

    protected $fillable = ['*'];

    protected $visible = ['systems', 'routers'];

    public function systems()
    {
        return $this->belongsToMany('App\Http\Models\System', 'system_location_mapping', 'Location_Idx', 'System_Idx')
            ->where(function($query){
                $query->whereRaw('End_Date IS NULL or End_Date > NOW()');
            });
    }

    public function routers()
    {
        return $this->hasMany('App\Http\Models\Router', 'Location_Idx');
    }

    public function aircraftModel()
    {
        return $this->belongsTo('App\Http\Models\AircraftModel', 'Model_Idx');
    }

    public function aircraftIcao()
    {
        return $this->hasOne('App\Http\Models\AircraftIcao', 'Location_Idx');
    }

    public function groups()
    {
        return $this->belongsToMany('App\Http\Models\Group', 'group_locations', 'Location_Idx', 'Group_Idx');
    }

    public function cabinBillingHistory()
    {
        return $this->hasMany('App\Http\Models\CabinBillingHistory', 'Location_Idx');
    }

    public function customer()
    {
        return $this->hasOne('App\Http\Models\CustomerLocation', 'Location_Idx');
    }
}
