<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class JxSubscription extends Model
{
    public $primaryId;

    use Eloquence, Mappable;
    
    public $timestamps = false;
    
    protected $table = 'jx_subscription_raw';
    
    
}
