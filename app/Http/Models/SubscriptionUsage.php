<?php

namespace App\Http\Models;

use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * @SWG\Definition()
 */
class SubscriptionUsage extends DBManModel
{
    use Eloquence, Mappable;

    /**
     * @SWG\Property(example=390)
     * @var integer
     */
    protected $locationId;

    /**
     * @SWG\Property(example=201609)
     * @var integer
     */
    protected $month;

    /**
     * @SWG\Property(example=3596)
     * @var integer
     */
    protected $dataUsed;

    /**
     * @SWG\Property(example=253)
     * @var integer
     */
    protected $voiceUsed;

    /**
     * @SWG\Property(example=1563)
     * @var integer
     */
    protected $streamingUsed;

    protected $table = 'subscription_usages';
    protected $primaryKey = 'Idx';

    protected $maps = [
        'locationId' => 'Location_Idx',
        'month' => 'Year_Month',
        'dataUsed' => 'Data_Usage',
        'voiceUsed' => 'Voice_Usage',
        'streamingUsed' => 'Streaming_Usage',
    ];
}
