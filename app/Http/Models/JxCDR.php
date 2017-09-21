<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * @SWG\Definition(required={"name", "JxCDR"})
 */
class JxCDR extends Model
{
    /**
     * @SWG\Property(example=100)
     * @var int
     */
    protected $id;

    /**
     * @SWG\Property(example=1054)
     * @var int
     */
    protected $metricId;
    
    /**
     * @SWG\Property(example=12061)
     * @var int
     */
    protected $packageId;
    
    /**
     * @SWG\Property(example="3.63546")
     * @var string
     */
    protected $value;

    use Eloquence, Mappable;

    protected $table = 'jx_cdr_raw';
}
