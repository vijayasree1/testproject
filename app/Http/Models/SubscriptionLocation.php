<?php
namespace App\Http\Models;

/**
 * @SWG\Definition()
 */
class SubscriptionLocation extends DBManModel
{
    /**
     * @SWG\Property(example="Airbus Manufacturer")
     * @var int
     */
    protected $subscriptionId;

    /**
     * @SWG\Property(example="Airbus Manufacturer")
     * @var int
     */
    protected $locationId;

    /**
     * @SWG\Property(example="Airbus Manufacturer")
     * @var string
     */
    protected $startDate;

    /**
     * @SWG\Property(example="Airbus Manufacturer")
     * @var string
     */
    protected $endDate;

    protected $table = 'all_subscription_locations';
    protected $primaryKey = array( 'Subscription_Idx', 'Location_Idx' );

    protected $maps = [
        'subscriptionId' => 'Subscription_Idx',
        'locationId' => 'Location_Idx',
        'startDate' => 'Start_Date',
        'endDate' => 'End_Date'
    ];

    protected $hidden = [
        'startDate',
        'endDate',
    ];

    //protected $appends = [ 'subscriptionId', 'locationId', 'startDate', 'endDate' ];

    /*public function customer()
    {
        return $this->belongsTo('App\Http\Models\Subscription', 'Subscription_Idx');
    }*/
}