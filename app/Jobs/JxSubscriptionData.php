<?php

namespace App\Jobs;

use App\Http\Services\JxSubscriptionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class JxSubscriptionData extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;
    
    private $jxSubscriptionService;
    /**
     * Execute the job.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->jxSubscriptionService = new JxSubscriptionService("");
    }
    
    public function handle()
    {
        $jxSubscriptionService = new JxSubscriptionService("");
    }
}
