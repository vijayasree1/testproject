<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class JxLatitudeLongitude extends Model
{
    use Eloquence, Mappable;
    protected $table = 'jx_latitude_longitude';
}
