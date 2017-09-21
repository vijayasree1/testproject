<?php

namespace App\Http\Models;

use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * @SWG\Definition(required={"name", "Network"})
 */
class Network extends DBManModel
{
    use Auditable;
    use Eloquence;
    use Mappable;

    /**
     * @SWG\Property(example=1)
     * @var int
     */
    public $id;

    /**
     * @SWG\Property(example="GoDirect Network")
     * @var string
     */
    public $name;

    /**
     * @SWG\Property(example="Honeywell GoDirect Network")
     * @var string
     */
    public $description;

    /**
     * @SWG\Property(example="STATIC")
     * @var string
     */
    public $ipAllocationType;

    /**
     * @SWG\Property(example="Y")
     * @var string
     */
    public $pdpAllowed;

    protected $table = 'network';
    protected $primaryKey = 'Network_Idx';

    protected $maps = [
        'id' => 'Network_Idx',
        'name' => 'Network_Name',
        'description' => 'Network_Description',
        'ipAllocationType' => 'IP_Allocation_Type',
        'pdpAllowed' => 'PDP_Allowed_YN_Flag',
        'isActive' => 'Active_YN_Flag',
        'isPreferredNetwork' => 'Inmarsat_Preferred_Network_YN_Flag'
    ];
}
