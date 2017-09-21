<?php

namespace App\Http\Models;

use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * @SWG\Definition(required={"name", "SimsSync"})
 */
class SimsSync extends DBManModel
{
    use Eloquence;
    use Mappable;

    protected $table = 'sims_sync';
    protected $primaryKey = 'Idx';
    public $timestamps = false;

    /**
     * @SWG\Property(example=329)
     * @var int
     */
    public $id;

    /**
     * @SWG\Property(example="Radmin Sync")
     * @var string
     */
    public $createdBy;

    /**
     * @SWG\Property(example="2017-06-01 12:23:32")
     * @var string
     */
    public $createdOn;

    /**
     * @SWG\Property(example="2017-06-01 12:23:32")
     * @var string
     */
    public $completedOn;

    protected $maps = [
        'id' => 'Idx',
        'createdBy' => 'Created_By',
        'createdAt' => 'Created_At',
        'completedAt' => 'Completed_At'
    ];
}
