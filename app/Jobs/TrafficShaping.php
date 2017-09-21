<?php

namespace App\Jobs;

use App\Http\Models\Task;
use App\Http\Models\Terminal;
use App\Http\Models\TrafficShapingTerminal;
use App\Http\Services\FlowGuardService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TrafficShaping extends Job implements ShouldQueue
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
        
        $terminalResetDate = $this->task->Data2;

        $terminal = Terminal::where('Idx','=',$terminalId)->first();
        
        $terminalTpkid = $terminal->TPK_DID;
        
        $flowGuardService = new FlowGuardService();
        

        $trafficShapingTerminal = TrafficShapingTerminal::where("Terminal_Idx","=",$terminalId)->whereRaw('Start_Date <= NOW() AND IFNULL(End_Date, NOW()) >= NOW()')->orderBy( 'Start_Date', 'desc' )->first();

        try
        {
            $simId = $this->createTrafficTerminal( $terminalId, $terminalTpkid , $flowGuardService , $terminalResetDate);

            $this->task->Status = 'STATUS_DONE_OK';
            $this->task->Finish_Date = \Carbon::now();
            $this->task->Message = 'Task was successfully completed.';
            $this->task->save();

            $trafficShapingTerminal->Status = 'ACTIVE';
            $trafficShapingTerminal->save();

        }
        catch (\Exception $e)
        {
            $this->task->Message = $e->getMessage();
            $this->task->Status = 'STATUS_DONE_FAIL';
            $this->task->Finish_Date = \Carbon::now();
            $this->task->save();
            
            if( $terminalResetDate == NULL )
            {
                $trafficShapingTerminal->Status = 'PROVISION_FAILED';
                $trafficShapingTerminal->save();
            }
        }
    }

    private function createTrafficTerminal( $terminalId, $terminalTpkid , $flowGuardService , $terminalResetDate)
    {
        $trafficTerminal = 0;

        try
        {
            $trafficTerminal = $flowGuardService->getTrafficTerminalDetails($terminalTpkid);
        }
        catch(\Exception $e)
        {
            Log::error($e);
        }

        if( $trafficTerminal == NULL )
        {
            $flowGuardService->createFlowguardTerminal( $terminalId );
            $flowGuardService->replaceIpAddress( $terminalId , $terminalTpkid ,"replaceIp");
            $flowGuardService->trafficRuleset( $terminalId , $terminalTpkid);
        }
        else
        {
            
            $flowGuardService->updateFlowguardTerminal( $terminalId );
            
            if( $terminalResetDate == NULL )
            {
                $flowGuardService->replaceIpAddress( $terminalId , $terminalTpkid ,"replaceIp");
                $flowGuardService->trafficRuleset( $terminalId , $terminalTpkid );
            }
        }
    }
}

