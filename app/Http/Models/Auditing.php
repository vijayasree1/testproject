<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;
class Auditing extends Model
{
    use Eloquence;
    use Mappable;
    
    public $timestamps = false;
    
    protected $table = 'audits';
    
    protected $primaryKey = 'id';
  
  //  protected $fillable = ['id','old','new','route','ip_address','type','auditable_type','uniqu','user_id','auditable_id','created_at'];
  
    protected $appends = [];
    
    protected $maps = [];
    
    protected $hidden = ['Idx','id'];
   
}
