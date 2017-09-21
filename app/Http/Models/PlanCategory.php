<?php

namespace App\Http\Models;
//namespace App;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;
class PlanCategory extends DBManModel
{
    use Eloquence, Mappable;

    protected $table = 'plan_category';
    public $timestamps = false;
    protected $maps = [
        'id' => 'Plan_Category_Idx',
        'planCategory' => 'Plan_Category_Name',
        'planDescription' => 'Plan_Category_Description',
        'createdBy' => 'Created_By',
        'statusId' => 'Status_Id',
        'updatedBy' => 'Last_Updated_By',
        'createdOn' => 'Created_On',
        'updatedOn'=>'Updated_On'
    ];
    protected $fillable = ['id', 'planCategory', 'planDescription', 'createdBy', 'statusId', 'updatedBy', 'createdOn', 'updatedOn'];
    protected $appends = [];
}
