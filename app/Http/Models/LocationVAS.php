<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;
/**
 * @SWG\Definition(required={"name", "LocationVAS"}, @SWG\Xml(name="LocationVAS"))
 */
class LocationVAS extends DBManModel
{
    use Auditable;
    use Eloquence, Mappable;
    /**
     * @SWG\Property(example=1329)
     * @var int
     */
    public $id;
    
    /**
     * @SWG\Property(example=4)
     * @var int
     */
    public $serviceId;
    
    /**
     * @SWG\Property(example=43)
     * @var int
     */
    public $locationId;

    /**
     * @SWG\Property(example="2010-11-17 00:00:00")
     * @var datetime
     */
    public $startDate;
    
    /**
     * @SWG\Property(example="2010-11-17 00:00:00")
     * @var datetime
     */
    public $endDate;
    
    /**
     * @SWG\Property(example=200)
     * @var int
     */
    public $customerId;
    
    /**
     * @SWG\Property(example="added service")
     * @var string
     */
    public $comments;
    
    /**
     * @SWG\Property(example="Admin")
     * @var int
     */
    public $updatedBy;
    
    /**
     * @SWG\Property(example="Admin")
     * @var string
     */
    public $createdBy;
    
    protected $table = 'vas_location_mapping';
    protected $primaryKey = 'Idx';
    
    public $timestamps = false;
    
    protected $maps = [
        'id' => 'Idx',
        'serviceId' => 'Vas_Idx',
        'locationId' => 'Location_Idx',
        'customerId' => 'Customer_Idx',
        'startDate' => 'Start_Date',
        'endDate' => 'End_Date',
        'comments' => 'Comments',
        'createdBy' => 'Created_By',
        'updatedBy' => 'Updated_By'
    ];
    
    protected $fillable = ['serviceId','locationId','customerId','startDate','endDate','comments','createdBy','updatedBy'];
}
