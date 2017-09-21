<?php

namespace App\Http\Models;

use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * @SWG\Definition(required={"name", "Contacts"})
 */

class Country extends DBManModel
{

	/**
     * @SWG\Property(example="ALB")
     * @var int
     */
    protected $countryCode;

    /**
     * @SWG\Property(example="ALB")
     * @var string
     */
    protected $countryCode2Char;

    /**
     * @SWG\Property(example="Albania")
     * @var string
     */
    protected $name;

    /**
     * @SWG\Property(example="N")
     * @var string
     */
    protected $inEU;

    use Eloquence;
    use Mappable;

    protected $table = 'country_codes';
    public $timestamps = false; //stops eloquent trying to insert timestamps //causing failure.

    protected $maps = [

    ];


}