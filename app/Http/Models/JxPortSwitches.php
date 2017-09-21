<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class JxPortSwitches extends Model
{
    public $primaryId;

    use Eloquence, Mappable;

    protected $table = 'jx_port_switches';
    
    public $timestamps = false;
    
    protected $fillable = ['id','package-id', 'port-number','auto-negotiation','eth-mode','eth-speed','tag-enable','svn-id','created-at'];
}
