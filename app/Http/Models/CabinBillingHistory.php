<?php

namespace App\Http\Models;

use OwenIt\Auditing\Auditable;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * @SWG\Definition(required={"name", "CabinBillingHistory"})
 */

class CabinBillingHistory extends DBManModel
{
    use Auditable;
    use Eloquence, Mappable;

    /**
     * @SWG\Property(example="10000")
     * @var int
     */
    public $id;

    /**
     * @SWG\Property(example="123")
     * @var int
     */
    public $locationId;

    /**
     * @SWG\Property(example="Enable")
     * @var string
     */
    public $status;

    /**
     * @SWG\Property(example="admin")
     * @var string
     */
    public $user;

    /**
     * @SWG\Property(example="2015-02-01 15:14:24")
     * @var string
     */
    public $date;

    protected $table = 'cb_logging';
    protected $primaryKey = 'Idx';
    public $incrementing = false;

    protected $maps = [
        'id' => 'Idx',
        'locationId' => 'Location_Idx',
        'status' => 'Status',
        'user' => 'User',
        'date' => 'Date',
    ];

    public function location()
    {
        return $this->belongsTo('App\Http\Models\Location', 'Location_Idx');
    }
}
