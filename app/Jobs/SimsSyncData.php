<?php
namespace App\Jobs;

use App\Http\Services\SimsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Http\Models\DailyCallLogs;

class SimsSyncData extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    /**
     * Execute the job.
     *
     * @return void
     */
    public $actionName;

    public function __construct ()
    {
        $this->simsService = new SimsService();
    }
    
    /**
     * Execute the job.
     *
     * @return void
     */
    
    public function handle ()
    {
        $simsSync = new SimsSync();
        $simsSync->Created_By = 'JOB REQUEST';
        $simsSync->Created_At = \Carbon::now();
        $simsSync->save();
        
        $simsService = new SimsService();
        
        $action=array("syncStatus","syncCustomerMapping","syncPackagePlanTypes","syncPlanNew","syncSubscriptions","syncSubscriptionLocations","updateDailyCallLogs","updateMonthlyCallLogs");
        
        //$action=array("syncStatus","syncCustomerMapping","syncPackagePlanTypes","syncPlan","syncSubscriptions","syncSubscriptionLocations");
        
        for ($i=0;$i<count($action);$i++)
        {
            call_user_func_array(array( $simsService, $action[$i]),array());
        }
        
        $simsSync->Completed_At = \Carbon::now();
        $simsSync->save();
    }

    
}
