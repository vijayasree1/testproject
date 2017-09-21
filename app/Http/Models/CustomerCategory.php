<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * @SWG\Definition(required={"name", "CustomerCategory"})
 */
class CustomerCategory extends DBManModel
{
    /**
     * @SWG\Property(example=1)
     * @var int
     */
    public $id;

    /**
     * @SWG\Property(example="Defence")
     * @var string
     */
    public $category;

    protected $table = 'customer_category';

    protected $primaryKey = 'Id';

    use Eloquence;
    use Mappable;

    protected $maps = [
        'id' => 'Id',
        'category' => 'Category'
    ];

    protected $hidden = [];
}
