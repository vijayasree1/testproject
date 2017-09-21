<?php

namespace App\Jobs;

use App\Http\Models\DBManNotification;
use App\Http\Models\RadminFirewallTier;
use App\Http\Models\RadminSimUser;
use App\Http\Models\RadminUserGroup;
use App\Http\Services\RadminService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class RadminMasterDataSync extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    private $radminService;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->radminService = new RadminService();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $radminService = new RadminService;
        $radminService->syncMasterData();
    }
}
