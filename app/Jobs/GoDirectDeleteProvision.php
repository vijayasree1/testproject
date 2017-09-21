<?php

namespace App\Jobs;

use App\Http\Models\Task;
use App\Http\Models\Terminal;
use App\Http\Models\Service;
use App\Http\Services\RadminService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GoDirectDeleteProvision extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    protected $task;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $terminalId = $this->task->Data1;

        $radminService = new RadminService;

        $terminalResponse = Terminal::where('Idx','=',$terminalId)->first();
       
        if($terminalResponse->Category=="SBB")
        {
            try
            {
                $simId = $radminService->getSimId($terminalResponse->IMSI);
                $simProvisioningDetails = $radminService->listSIMProvisioning($simId);
                $simProvisioningId = $simProvisioningDetails[0]['id'];
                $radminService->deleteSIMProvisioning($simId, $simProvisioningId);
                $radminService->disableSIM($simId);
    			       			
                $this->task->Status = 'STATUS_DONE_OK';
                $this->task->Finish_Date = \Carbon::now();
                $this->task->Message = 'Task was successfully completed.';
                $this->task->save();
            }
            catch (\Exception $e)
            {
                $this->task->Message = $e->getMessage();
                $this->task->Status = 'STATUS_DONE_FAIL';
                $this->task->Finish_Date = \Carbon::now();
                $this->task->save();	
            }
        }
        elseif( $terminalResponse->Category == "JX" )
        {
            try
            {
                $radminTerminalId = $radminService->getRadminTerminalId($terminalId);
                $jxSvnProfiles = $radminService->listSvnProfile($radminTerminalId);

        		if(isset($jxSvnProfiles[0]['id']) && $jxSvnProfiles[0]['id'] > 0)
        		{
                    $jxsvnProfileId = $jxSvnProfiles[0]['id'];
                    $response = $radminService->deleteJxSvnProfile($radminTerminalId, $jxsvnProfileId);
        		}
                        
        		$response=$radminService->disableJxSim($radminTerminalId);
            
                $this->task->Status = 'STATUS_DONE_OK';
                $this->task->Finish_Date = \Carbon::now();
                $this->task->Message = 'Task was successfully completed.';
                $this->task->save();
            }
            catch( \Exception $e )
            {
                $this->task->Message = $e->getMessage();
                $this->task->Status = 'STATUS_DONE_FAIL';
                $this->task->Finish_Date = \Carbon::now();
                $this->task->save();
            }
        } 
    }
}

