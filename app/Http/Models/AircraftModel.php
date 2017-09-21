<?php

namespace App\Http\Models;

use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * @SWG\Definition()
 */
class AircraftModel extends DBManModel
{
    use Eloquence, Mappable;

    protected $table = 'aircraft_model';
    protected $primaryKey = 'Model_Idx';

    /**
     * @SWG\Property(example=1)
     * @var int
     */
    protected $id;

    /**
     * @SWG\Property(example="A320")
     * @var string
     */
    protected $model;

    /**
     * @SWG\Property(example="Airbus A320 Model")
     * @var int
     */
    protected $description;

    protected $maps = [
        'id' => 'Model_Idx',
        'model' => 'Model',
        'description' => 'Model_Description',
        'makeId'=>'Make_Idx',
        'updatedOn'=>'Updated_On'
    ];

    public function make()
    {
        return $this->belongsTo('App\Http\Models\AircraftMake', 'Make_Idx');
    }
}
