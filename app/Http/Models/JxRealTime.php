<?php

namespace App\Http\Models;

use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;
use Illuminate\Database\Eloquent\Model;

class JxRealTime extends Model
{
    use Eloquence, Mappable;
    public $timestamps = true;
    public $table = 'jx_real_time_raw';
}
