<?php
namespace App\Http\Models;

use OwenIt\Auditing\Auditable;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

class SimsCustomerMapping extends DBManModel
{
    //use Auditable;
    use Eloquence;
    use Mappable;

    protected $table = 'customer_mapping';

    protected $primaryKey = 'Idx';

    public $timestamps = false;

    protected $maps = [
        'id' => 'Idx',
        'customerId' => 'Customer_Idx',
        'account' => 'Account_Id',
        'createdOn' => 'Created_Date',
        'updatedOn'=>'Updated_On'
    ];

    protected $fillable = ['id', 'customerId','account', 'createdOn', 'updatedOn'];

    protected $appends = [];
}
