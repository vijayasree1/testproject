<?php

namespace App\Http\Models;

use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class RadminFirewallTier extends DBManModel
{
    use Eloquence, Mappable;

    protected $table = 'radmin_firewall_filter';
    protected $primaryKey = 'Firewall_Filter_Idx';
    public $timestamps = false;

    protected $maps = [
        'id' => 'Firewall_Filter_Idx',
        'name' => 'Firewall_Filter_Name',
        'description' => 'Firewall_Description',
        'isDefault' => 'is_default',
        'updatedOn' => 'Updated_On',
        'lastUpdatedBy' => 'Last_Updated_By'
    ];

    protected $hidden = ['lastUpdatedBy'];

    public function getIsDefaultAttribute()
    {
        return $this->attributes['Default_Firewall_Filter_YN_Flag'] == 'Y';
    }
}