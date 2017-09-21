<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * @SWG\Definition(required={"name", "Systems"}, @SWG\Xml(name="Systems"))
 */
class System extends Model
{
    /**
     * @SWG\Property(example=329)
     * @var int
     */
    public $id;

    /**
     * @SWG\Property(example="MCS-7200")
     * @var string
     */
    public $systemModel;

    /**
     * @SWG\Property(example="SBB")
     * @var string
     */
    public $terminalCategory;

    /**
     * @SWG\Property(example="S6BO02")
     * @var string
     */
    public $terminalModelCode;

    /**
     * @SWG\Property(example="IGA")
     * @var string
     */
    public $antenna;

    /**
     * @SWG\Property(example="6")
     * @var string
     */
    public $antennaClass;

    /**
     * @SWG\Property(example="N/A")
     * @var string
     */
    public $i4Proven;

    /**
     * @SWG\Property(example="Honeywell Aerospace")
     * @var string
     */
    public $manufacturer;

    use Eloquence, Mappable;

    protected $primaryKey = 'Idx';
	
    public $timestamps = false;

    protected $table = 'system';

    protected $maps = [
        'id' => 'Idx',
        'manufacturerSystemIdx' => 'Manufacturer_System_Idx',
        'systemModel' => 'systemManufacturer.Model_Name',
        'manufacturer' => 'systemManufacturer.manufacturer.Name',
        'antenna' => 'systemManufacturer.Antenna',
        'antennaClass' => 'systemManufacturer.Class',
        'i4Proven' => 'systemManufacturer.I4_Proven',
        'updatedOn'=>'Updated_On'
    ];
    
    protected $appends = ['id', 'systemModel', 'antenna', 'antennaClass', 'i4Proven', 'manufacturer'];

    protected $hidden = ["pivot", "systemManufacturer", "Idx", "System_Manufacturer", "System_Model", "Antenna_Manufacturer",
                        "Antenna_Model", "Router_Manufacturer", "Router_Model", "Features", "WiFi_Manufacturer", "WiFi_Model",
                        "PBX_Manufacturer", "PBX_Model", "Integrity", "Comment"];

    public function customers()
    {
        return $this->belongsToMany('App\Http\Models\Customer', 'system_customer_mapping', 'System_Idx', 'Customer_Idx')
            ->wherePivot('End_Date', '=', null);
    }

    public function locations()
    {
        return $this->belongsToMany('App\Http\Models\Location', 'system_location_mapping', 'System_Idx', 'Location_Idx')
            ->wherePivot('End_Date', '=', null);
    }

    public function systemManufacturer()
    {
        return $this->belongsTo('App\Http\Models\ManufacturerSystem', 'Manufacturer_System_Idx');
    }

    /**
    *   @SWG\Property(
    *   property="terminals",
    *   type="array",
    *   @SWG\Items(ref="#/definitions/Terminal"), 
    *   )
    */
    public function terminals()
    {
        return $this->hasMany('App\Http\Models\Terminal', 'System_Idx')
					->whereNull('terminal.Deactivation_Date');
    }
}

