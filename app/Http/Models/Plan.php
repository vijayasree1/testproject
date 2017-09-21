<?php

namespace App\Http\Models;

use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * @SWG\Definition(required={"name", "Plan"})
 */

class Plan extends DBManModel
{

    use Eloquence, Mappable;
    
    public $timestamps = false;
    
    protected $planName;

    protected $table = 'plan';
    protected $primaryKey = 'Plan_Idx';

    protected $maps = [
        'planId' => 'Plan_Idx',
        'planName' => 'Plan_Name',
        'planDescription' => 'Plan_Description',
        'planCategory' => 'Plan_Category_Idx',
        'createdOn' => 'Created_On',
        'createdBy' => 'Created_By',
        'updatedOn' => 'Updated_On',
        'updatedBy' => 'Last_Updated_By',
        'planLimit' => 'Plan_Limit'
    ];
    
    protected $fillable = ['planId','planName', 'planDescription', 'planCategory','createdOn','createdBy', 'updatedOn','updatedBy','planLimit'];
}

