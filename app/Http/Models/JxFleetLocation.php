<?php

namespace App\Http\Models;

use OwenIt\Auditing\Auditable;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * @SWG\Definition(required={"name", "Terminal"}, @SWG\Xml(name="Terminal"))
 */
class JxFleetLocation extends DBManModel
{

    use Auditable;
    /**
     * @SWG\Property(example=1329)
     * @var int
     */
    public $id;

    /**
     * @SWG\Property(example=865)
     * @var int
     */
    public $locationId;

    /**
     * @SWG\Property(example=100)
     * @var int
     */
    public $customerId;

    /**
     * @SWG\Property(example=1009)
     * @var int
     */
    public $subscriptionId;

    /**
     * @SWG\Property(example="37.8030014038086")
     * @var string
     */
    public $latitude;

    /**
     * @SWG\Property(example="23.0137004852295")
     * @var string
     */
    public $longitude;

    /**
     * @SWG\Property(example="2017-07-02 14:07:00")
     * @var datetime
     */
    public $lastTimestamp;

    /**
     * @SWG\Property(example="2017-07-02 14:07:00")
     * @var datetime
     */
    public $updatedOn;

    /**
     * @SWG\Property(example="2017-07-02 14:07:00")
     * @var datetime
     */
    public $createdOn;

    use Eloquence,
        Mappable;
    protected $primaryKey = 'Id';
    protected $table = 'jx_fleet_location';
    public $timestamps = false;
    protected $maps = [
        'id'             => 'Id',
        'locationId'     => 'Location_Id',
        'customerId'     => 'Customer_Id',
        'subscriptionId' => 'Subscription_Id',
        'latitude'       => 'Latitude',
        'longitude'      => 'Longitude',
        'lastTimestamp'  => 'Last_Timestamp',
        'createdOn'      => 'Created_On',
        'updatedOn'      => 'Updated_On'
    ];
    protected $hidden = ['id', 'createdOn', 'updatedOn'];
    protected $visible = ['locationId', 'customerId', 'subscriptionId', 'latitude', 'longitude', 'lastTimestamp'];
    protected $fillable = ['locationId', 'customerId', 'subscriptionId', 'latitude', 'longitude', 'lastTimestamp', 'updatedOn'];
    protected $appends = [];

}