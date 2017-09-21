<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;
/**
 * @SWG\Definition()
 */
class SubscriptionPackage extends Model
{
    use Eloquence,Mappable;
    
    /**
     * @SWG\Property(example="77")
     * @var int
     */
    protected $subscriptionId;

    /**
     * @SWG\Property(example="11277")
     * @var int
     */
    protected $packageId;

    /**
     * @SWG\Property(example="20400")
     * @var string
     */
    protected $templateId;
    
    public $table = 'subscription_package';
    
    protected $maps = [
        'subscriptionId' => 'subscription_id',
        'packageId' => 'package_id',
        'templateId' => 'template_id'
    ];
    public $timestamps = false;
}
