<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * @SWG\Definition(required={"name", "APN"})
 */
class APN extends DBManModel
{
    use Eloquence;
    use Mappable;

    protected $table = 'apn';
    protected $primaryKey = 'APN_Idx';

    /**
     * @SWG\Property(example=329)
     * @var int
     */
    public $id;

    /**
     * @SWG\Property(example="satcom1.bgan.inmarsat.com")
     * @var string
     */
    public $name;

    /**
     * @SWG\Property(example="Satcom1 APN String")
     * @var string
     */
    public $description;

    protected $maps = [
        'id' => 'APN_Idx',
        'name' => 'APN_Name',
        'description' => 'APN_Description'
    ];
}
