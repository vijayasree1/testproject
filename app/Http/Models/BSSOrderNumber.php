<?php
namespace App\Http\Models;

use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * @SWG\Definition(required={"name", "BSSOrderNumber"})
 */
class BSSOrderNumber extends DBManModel
{
    use Auditable;
    use Eloquence;
    use Mappable;

    protected $table = 'bss_order_number';

    protected $primaryKey = 'Idx';

    /**
     * @SWG\Property(example=329)
     * 
     * @var int
     */
    public $id;

    /**
     * @SWG\Property(example="2250")
     * 
     * @var int
     */
    public $terminalId;

    /**
     * @SWG\Property(example="BSS623007")
     * 
     * @var string
     */
    public $bssOrderNumber;
    
    public $timestamps = false;

    protected $maps = [
            'id' => 'Idx',
            'terminalId' => 'Terminal_Idx',
            'bssOrderNumber' => 'BSS_Order_Number'
    ];

}
