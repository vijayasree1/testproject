<?php

namespace App\Http\Models;

use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * @SWG\Definition()
 */
class AircraftMake extends DBManModel
{
    use Eloquence, Mappable;

    /**
     * @SWG\Property(example=1)
     * @var int
     */
    protected $id;

    /**
     * @SWG\Property(example="Airbus")
     * @var string
     */
    protected $make;

    /**
     * @SWG\Property(example="Airbus Manufacturer")
     * @var string
     */
    protected $description;

    protected $table = 'aircraft_make';
    protected $primaryKey = 'Make_Idx';

    protected $maps = [
        'id' => 'Make_Idx',
        'make' => 'Make',
        'description' => 'Make_Description',
         'updatedOn'=>'Updated_On'
    ];
}
