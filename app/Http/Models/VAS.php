<?php

namespace App\Http\Models;

use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;
use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\Model;

/**
 * @SWG\Definition(required={"name", "VAS"})
 */
class VAS extends DBManModel
{

    use Eloquence,
        Mappable,
        Auditable;
    /**
     * @SWG\Property(example=1)
     * @var int
     */
    protected $id;

    /**
     * @SWG\Property(example="ATT")
     * @var string
     */
    protected $name;

    /**
     * @SWG\Property(example="ACTIVE")
     * @var string
     */
    protected $status;
    
    protected $table = 'vas_master';
    protected $primaryKey = 'Idx';
    public $timestamps = false; //stops eloquent trying to insert timestamps //causing failure.
    
    protected $maps = [
        'id' => 'Idx',
        'name' => 'Service_Name',
        'status' => 'Status'
    ];
    //protected $visible = ['id', 'name', 'status'];
}