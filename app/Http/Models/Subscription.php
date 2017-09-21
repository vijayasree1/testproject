<?php

namespace App\Http\Models;

use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * @SWG\Definition(required={"name", "Subscriptions"})
 */

class Subscription extends DBManModel
{

    /**
     * @SWG\Property(example=100)
     * @var int
     */
    protected $id;

    /**
     * @SWG\Property(example=200)
     * @var int
     */
    protected $customerId;

    /**
     * @SWG\Property(example="Example Aviation")
     * @var string
     */
    protected $customerName;

    /**
     * @SWG\Property(example="2016-01-01 00:00:00")
     * @var string
     */
    protected $startDate;

    /**
     * @SWG\Property(example="2017-01-01 00:00:00")
     * @var string
     */
    protected $endDate;

    /**
     * @SWG\Property(example="SBB1000")
     * @var string
     */
    protected $planName;

    /**
     * @SWG\Property(example="SBB")
     * @var string
     */
    protected $planCategory;

    /**
     * @SWG\Property(example="1000")
     * @var string
     */
    protected $dataLimit;

    use Eloquence, Mappable;

    protected $table = 'all_subscriptions';
    protected $primaryKey = 'Subscription_Idx';

    protected $casts = [
        'Customer_Idx' => 'integer',
        'Plan_Limit' => 'integer'
    ];

    protected $maps = [
        'id' => 'Subscription_Idx',
        'customerId' => 'Customer_Idx',
        'customerName' => 'customer.Company',
        'startDate' => 'Subscription_Start_Date',
        'endDate' => 'Subscription_End_Date',
        'planName' => 'Plan_Name',
        'planDescription' => 'Plan_Description',
        'planCategory' => 'Plan_Category_Name',
        'recurrence' => 'Subscription_Validity',
        'dataLimit' => 'Plan_Limit',
        'planLimitType' => 'Plan_Limit_Type',
    ];

    /**
    *   @SWG\Property(
    *   property="plan",
    *   type="array",
    *   @SWG\Items(ref="#/definitions/Plan"), 
    *   )
    */
    public function plan()
    {
        return $this->belongsTo('App\Http\Models\Plan', 'Plan_Idx');
    }

    /**
    *   @SWG\Property(
    *   property="customer",
    *   type="array",
    *   @SWG\Items(ref="#/definitions/Customer"), 
    *   )
    */
    public function customer()
    {
        return $this->belongsTo('App\Http\Models\Customer', 'Customer_Idx');
    }

    /**
    *   @SWG\Property(
    *   property="locations",
    *   type="array",
    *   @SWG\Items(ref="#/definitions/Location"), 
    *   )
    */
    public function locations()
    {
        return $this->belongsToMany('App\Http\Models\Location', 'all_subscription_locations', 'Subscription_Idx', 'Location_Idx' );
    }
}
