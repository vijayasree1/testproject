<?php

namespace App\Http\Models;

use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class ManagementLocations extends DBManModel
{

    protected $primaryKey = 'Idx';

    use Eloquence;
    use Mappable;

    protected $table = 'managed_locations';
    public $timestamps = false; //stops eloquent trying to insert timestamps //causing failure.

    protected $maps = [
        'id' => 'Idx',
        'managementCustomerId' => 'Management_Customer_Idx',
        'locationId' => 'Location_Idx',
        'customerId' => 'Customer_Idx',
        'updatedOn'=>'Updated_On'
    ];


}