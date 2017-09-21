<?php

namespace App\Http\Models;

use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * @SWG\Definition(required={"name", "DBManNotification"})
 */
class DBManNotification extends DBManModel
{
    use Eloquence, Mappable;

    /**
     * @SWG\Property(example=1)
     * @var int
     */
    protected $id;

    /**
     * @SWG\Property(example="1")
     * @var int
     */
    protected $userId;

    /**
     * @SWG\Property(example="RADMIN_SYNC")
     * @var string
     */
    protected $notificationType;

    /**
     * @SWG\Property(example="2016-08-17 12:14:20")
     * @var string
     */
    protected $startDate;

    /**
     * @SWG\Property(example="2016-08-17 12:14:20")
     * @var string
     */
    protected $endDate;

    protected $table = 'notifications';
    protected $primaryKey = 'Idx';

    protected $maps = [
        'id' => 'Idx',
        'userId' => 'User_Idx',
        'email' => 'user.email',
        'notificationType' => 'Notification_Type',
        'startDate' => 'Start_Date',
        'endDate' => 'End_Date'
    ];

    public function user()
    {
        return $this->belongsTo('App\Http\Models\User', 'User_Idx');
    }
}

