<?php

namespace App\Http\Models;

use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * @SWG\Definition(required={"name", "LESO"})
 */
class Leso extends DBManModel
{
    use Eloquence;
    use Mappable;

    protected $table = 'leso';

    protected $primaryKey = 'Idx';

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

    protected $maps = [
        'id' => 'Idx',
        'name' => 'Name'
    ];
}
