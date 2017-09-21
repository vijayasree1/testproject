<?php

namespace App\Http\Models;

use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * @SWG\Definition(required={"name", "Invoices"})
 */

class Invoice extends DBManModel
{
    use Eloquence, Mappable;

    /**
     * @SWG\Property(example="S1-005-1013678C")
     * @var string
     */
    public $invoiceNumber;

    /**
     * @SWG\Property(example="016-06-15")
     * @var string
     */
    public $invoiceDate;

    /**
     * @SWG\Property(example="2016-06-15")
     * @var string
     */
    public $dueDate;
    
    /**
     * @SWG\Property(example="PAID")
     * @var string
     */
    public $paidStatus;

    /**
     * @SWG\Property(example="2016-06-15")
     * @var string
     */
    public $paidDate;    

    protected $table = 'invoice_overview';
    protected $primaryKey = 'Invoice_No';
    public $incrementing = false;

    protected $maps = [
        'invoiceNumber' => 'Invoice_No',
        'invoiceDate' => 'Invoice_Date',
        'dueDate' => 'Due_Date',
        'paidStatus' => 'Paid_Status',
        'paidDate' => 'Paid_Date',
    ];

    public function customer()
    {
        return $this->belongsTo('App\Http\Models\Customer', 'Customer_Idx');
    }
}
