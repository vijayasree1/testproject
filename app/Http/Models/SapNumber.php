<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;


class SapNumber extends DBManModel
{
    use Eloquence;
    use Mappable;

    protected $table = 'sap_customer';
    protected $primaryKey = 'Customer_Idx';

    public $customerId;
    public $sapNumber;
    public $sapStatus;

    protected $maps = [
        'customerId' => 'Customer_Idx',
        'sapNumber' => 'SAP_Customer_Idx',
        'sapStatus' => 'SAP_Status'
    ];
}
