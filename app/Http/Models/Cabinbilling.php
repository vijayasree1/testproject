<?php

namespace App\Http\Models;

use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;
/**
 * @SWG\Definition(required={"name", "Cabinbilling"})
 */
class Cabinbilling extends Model
{
    use Auditable;
    use Eloquence;
    use Mappable;
    
    protected $table = 'cabinbilling';
    protected $primaryKey = 'Idx';
    
    /**
     * @SWG\Property(example=329)
     * @var int
     */
    public $id;
    
    /**
     * @SWG\Property(example="2250")
     * @var int
     */
    public $terminalId;
    
    /**
     * @SWG\Property(example="Enabled")
     * @var string
     */
    public $status;
    
    protected $maps = [
            'id' => 'Idx',
            'terminalId' => 'Terminal_Idx',
            'status' => 'Status'
    ];
    
    public $timestamps = false;
}
