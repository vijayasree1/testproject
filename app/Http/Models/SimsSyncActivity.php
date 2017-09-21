<?php
namespace App\Http\Models;

use OwenIt\Auditing\Auditable;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class SimsSyncActivity extends DBManModel
{
    //use Auditable;
    use Eloquence;
    use Mappable;

    protected $table = 'sims_sync_activity';

    protected $primaryKey = 'Idx';

    public $timestamps = false;

    protected $maps = [
        'id' => 'Idx',
        'description' => 'Description',
        'activity' => 'Activity',
        'lastSyncDate'=>'Last_Sync_Date'
    ];

    protected $fillable = ['id', 'lastSyncDate'];

    protected $appends = [];
}
