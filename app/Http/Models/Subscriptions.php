<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * @SWG\Definition(required={"name", "subscriptions"})
 */
class Subscriptions extends DBManModel
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
     * @SWG\Property(example=200)
     * @var int
     */
    protected $planId;
    
    /**
     * @SWG\Property(example=200)
     * @var int
     */
    protected $status;
    
    /**
     * @SWG\Property(example=200)
     * @var int
     */
    protected $billingStatus;
    
    /**
     * @SWG\Property(example="1000")
     * @var string
     */
    protected $dataLimit;
    
    /**
     * @SWG\Property(example="MB")
     * @var string
     */
    protected $planLimitType;

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
     * @SWG\Property(example="Annual")
     * @var string
     */
    protected $recurrence;
    
    /**
     * @SWG\Property(example="2017-01-01 23:00:00")
     * @var string
     */
    protected $createdOn;
    
    /**
     * @SWG\Property(example="2017-01-01 23:00:00")
     * @var string
     */
    protected $updatedOn;
    
    /**
     * @SWG\Property(example="DBMan Admin")
     * @var string
     */
    protected $createdBy;
    
    /**
     * @SWG\Property(example="DBMan Admin")
     * @var string
     */
    protected $updatedBy;
    
    protected $customerPlanDiscount;
    protected $customerPlanLimitOnHand;

    use Eloquence, Mappable;

    protected $table = 'subscriptions';
    protected $primaryKey = 'Subscription_Idx';

    protected $casts = [
        'Customer_Idx' => 'integer',
        'Plan_Limit' => 'integer'
    ];
    public $timestamps= false;
    protected $maps = [
        'id' => 'Subscription_Idx',
        'customerId' => 'Customer_Idx',
        'customerName' => 'customer.customerName',
        'planId' => 'Plan_Idx',
        'planName' => 'plan.planName',
        'planDescription' => 'plan.planDescription',
        'planCategory' => 'plan.planCategory',
        'status' => 'Subscription_Status_Idx',
        'billingStatus' => 'Subscription_Billing_Status_Idx',
        'dataLimit' => 'Plan_Limit',
        'planLimitType' => 'Plan_Limit_Type',
        'customerPlanPrice' => 'Customer_Plan_Price',
        'customerPlanDiscount' => 'Customer_Plan_Discount',
        'customerPlanLimitOnHand' => 'Customer_Plan_Limit_On_Hand',
        'startDate' => 'Subscription_Start_Date',
        'endDate' => 'Subscription_End_Date',
        'recurrence' => 'Subscription_Validity',
        'contractPeriodInMonths' => 'contract_period_in_months',
        'subscriptionType' => 'subscription_type',
        'createdOn' => 'Created_On',
        'updatedOn' => 'Updated_On',
        'createdBy' => 'Created_By',
        'updatedBy' => 'Last_Updated_By'
    ];
    protected $fillable = ['id', 'customerId', 'planId', 'status', 'billingStatus', 'dataLimit', 'planLimitType','customerPlanPrice','customerPlanDiscount','customerPlanLimitOnHand', 'startDate', 'endDate','recurrence','contractPeriodInMonths','subscriptionType','createdOn','updatedOn','createdBy','updatedBy'];
    protected $hidden = ['planId','status','billingStatus','customerPlanPrice','customerPlanDiscount','customerPlanLimitOnHand','contractPeriodInMonths','subscriptionType','createdOn','createdBy','updatedBy'];
    protected $appends = [];
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
        return $this->belongsToMany('App\Http\Models\Location', 'subscription_locations', 'Subscription_Idx', 'Location_Idx' );
    }
}
