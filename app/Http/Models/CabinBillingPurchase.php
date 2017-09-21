<?php

namespace App\Http\Models;

use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * @SWG\Definition(required={"name", "CabinBillingPurchases"})
 */

class CabinBillingPurchase extends DBManModel
{
    use Eloquence, Mappable;

    /**
     * @SWG\Property(example="10000")
     * @var int
     */
    public $id;

    /**
     * @SWG\Property(example="test@honeywell.com")
     * @var string
     */
    public $userEmail;

    /**
     * @SWG\Property(example="100.00")
     * @var int
     */
    public $amount;

    /**
     * @SWG\Property(example="10.00")
     * @var int
     */
    public $volume;

    /**
     * @SWG\Property(example="2015-02-01 15:14:24")
     * @var string
     */
    public $date;

    /**
     * @SWG\Property(example="CREDIT_CARD")
     * @var string
     */
    public $type;

    protected $table = 'cb_purchase';
    protected $primaryKey = 'Idx';
    public $incrementing = false;

    protected $maps = [
        'id' => 'Idx',
        'userEmail' => 'User_Account',
        'amount' => 'Amount',
        'volume' => 'Volume_MB',
        'date' => 'Date',
        'type' => 'Type'
    ];

    public function customer()
    {
        return $this->belongsTo('App\Http\Models\Customer', 'Customer_Idx');
    }
}
