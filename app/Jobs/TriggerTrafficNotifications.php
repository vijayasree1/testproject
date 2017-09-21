<?php

namespace App\Jobs;

use App\Jobs\Job;
use App\Http\Services\FlowGuardService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Http\Models\TrafficShapingNotification;
use App\Http\Models\TrafficShapingNotificationEvent;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;

class TriggerTrafficNotifications extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels, DispatchesJobs;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $flowGuardService = new FlowGuardService();
        $flowGuardService->getTrafficShapingNotifications();
        $flowGuardService->sendTrafficShapingNotifications();
    }
}
