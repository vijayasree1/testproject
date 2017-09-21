<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * @SWG\Definition(required={"name", "ServicesMaster"})
 */
class ServicesMaster extends DBManModel
{
    /**
     * @SWG\Property(example=1)
     * @var int
     */
    public $id;

    /**
     * @SWG\Property(example="Tier 1")
     * @var string
     */
    public $name;

    /**
     * @SWG\Property(example="GoDirect Filtering Tier-1")
     * @var string
     */
    public $description;

    protected $table = 'services_master';

    protected $primaryKey = 'Service_Idx';

    use Eloquence;
    use Mappable;

    protected $maps = [
        'id' => 'Service_Idx',
        'name' => 'Service_Name',
        'description' => 'Service_Description',
        'updatedOn' => 'Updated_On',
        'order' => 'Service_Order'
    ];

    protected $hidden = ['order'];
}
