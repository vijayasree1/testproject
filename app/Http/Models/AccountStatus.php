<?php

namespace App\Http\Models;

class AccountStatus extends DBManModel
{

    protected $table = 'acsm_customer_account_info';
    protected $primaryKey = 'Idx';

    protected $maps = [
        'accountType' => 'Account_Type',
        'amountDue' => 'Amount_Due',
        'daysOver' => 'Days_Over'
    ];

}
