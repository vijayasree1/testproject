<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * @SWG\Definition(required={"name", "JxMetric"})
 */
class JxMetric extends Model
{
    /**
     * @SWG\Property(example=1)
     * @var int
     */
    protected $id;

    /**
     * @SWG\Property(example=1054)
     * @var int
     */
    protected $metricId;
    
    /**
     * @SWG\Property(example="Latitude")
     * @var string
     */
    protected $metricName;
    
    /**
     * @SWG\Property(example="Active")
     * @var string
     */
    protected $status;

    /**
     * @SWG\Property(example="latitude")
     * @var string
     */
    protected $defaultName;

    use Eloquence, Mappable;

    protected $table = 'jx_metric_list';
    protected $primaryKey = 'id';
    public $timestamps = false; //stops eloquent trying to insert timestamps //causing failure.
    
}