<?php

namespace App\Http\Models;

use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * @SWG\Definition(required={"name", "RadminSyncDetails"})
 */
class RadminSyncDetails extends DBManModel
{
    use Eloquence;
    use Mappable;

    protected $table = 'radmin_sync_details';
    protected $primaryKey = 'Idx';
    public $timestamps = false;

    /**
     * @SWG\Property(example=1)
     * @var int
     */
    public $id;

    /**
     * @SWG\Property(example="22")
     * @var string
     */
    public $radminSyncId;

    /**
     * @SWG\Property(example="1867")
     * @var string
     */
    public $terminalId;

    /**
     * @SWG\Property(example="150003")
     * @var string
     */
    public $radminSimId;

    /**
     * @SWG\Property(example="2506")
     * @var string
     */
    public $taskId;

    /**
     * @SWG\Property(example="IMSI 901142536984564 not present in Radmin.")
     * @var string
     */
    public $errorMessage;

    protected $maps = [
        'id' => 'Idx',
        'radminSyncId' => 'Radmin_Sync_Idx',
        'terminalId' => 'Terminal_Idx',
        'radminSimId' => 'Radmin_Sim_Id',
        'errorMessage' => 'Error_Message',
        'taskId' => 'Task_Idx',
        'imsi' => 'IMSI'
    ];
}
