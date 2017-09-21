<?php
namespace App\Http\Models;

use OwenIt\Auditing\Auditable;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class SubscriptionStatus extends DBManModel
{
    //use Auditable;
    use Eloquence;
    use Mappable;

    protected $table = 'subscription_status';

    protected $primaryKey = 'Subscription_Status_Idx';

    public $timestamps = false;

    protected $maps = [
        'id' => 'Subscription_Status_Idx',
        'status' => 'Subscription_Status',
        'createdOn' => 'Created_On',
        'updatedOn'=>'Updated_On'
    ];

    protected $fillable = ['id', 'status', 'createdOn', 'updatedOn'];

    protected $appends = [];
}
