<?php
namespace App\Http\Models;

use OwenIt\Auditing\Auditable;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class SubscriptionLocationMapping extends DBManModel
{
    //use Auditable;
    use Eloquence;
    use Mappable;

    protected $table = 'subscription_locations';

    protected $primaryKey = 'Subscription_Location_Idx';

    public $timestamps = false;

    protected $maps = [
        'id' => 'Subscription_Location_Idx',
        'subscriptionId' => 'Subscription_Idx',
        'locationId' => 'Location_Idx',
        'startDate' => 'Subscription_Start_Date',
        'endDate' => 'Subscription_End_Date',
        'createdOn' => 'Created_On',
        'updatedOn'=>'Updated_On'
    ];
    
    protected $fillable = ['id', 'subscriptionId', 'locationId', 'startDate', 'endDate', 'createdOn', 'updatedOn'];
    
    protected $appends = [];
}
