<?php

namespace App\Jobs;

use App\Jobs\JxSubscriptionDetailsInfo;
use App\Http\Models\Network;
use App\Http\Models\Task;
use App\Http\Models\Terminal;
use App\Http\Models\Service;
use App\Http\Models\JxSubscription;
use App\Http\Services\RadminService;
use App\Http\Services\JxSubscriptionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GoDirectProvision extends Job implements ShouldQueue
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

        $terminal = Terminal::where('Idx','=',$terminalId)->first();

        $isProvisioningSuccessful = false;

        try
        {
            if($terminal->Category=="SBB")
            {
                $simId = $this->createSim($terminal->IMSI, $terminalId, $radminService);
                $this->createSimProvisioning($simId, $terminalId, $radminService);
                
                $this->task->Status = 'STATUS_DONE_OK';
                $this->task->Finish_Date = \Carbon::now();
                $this->task->Message = 'Task was successfully completed.';
                $this->task->save();
                
                $terminal->Status = 'ACTIVE';
                $terminal->save();
    
            }
            elseif ($terminal->Category=="JX")
            {
               $packageId = $this->task->Data2;
                
               if(empty($packageId) || is_null($packageId))
               {
                   $fieldName='package-id';
                    
                   $jxSubscriptionData=JxSubscription::where('msisdn','=',$terminal->TPK_DID)
                                                       ->where('status','=','ACTIVE')->first();
                   if(!is_null($jxSubscriptionData))
                        $packageId=$jxSubscriptionData->$fieldName;
                    
               }
               
               if($packageId>0)
               {
                   $simId = $this->createJxSim($terminalId,$packageId,$radminService);
                   $this->createJxSimProfile($simId, $terminalId, $radminService);
                   
                   $this->task->Status = 'STATUS_DONE_OK';
                   $this->task->Finish_Date = \Carbon::now();
                   $this->task->Message = 'Task was successfully completed.';
                   $this->task->save();
                   
                   $terminal->Status = 'ACTIVE';
                   $terminal->save();
               }
               else 
               {
                   $this->task->Message = 'TPK ID is not there in Mobileware';
                   $this->task->Status = 'STATUS_DONE_FAIL';
                   $this->task->Finish_Date = \Carbon::now();
                   $this->task->save();
                   
                   $terminal->Status = 'PROVISION_FAILED';
                   $terminal->save();
               }
            }
            
            $isProvisioningSuccessful = true;
        }
        catch (\Exception $e)
        {
            $this->task->Message = $e->getMessage();
            $this->task->Status = 'STATUS_DONE_FAIL';
            $this->task->Finish_Date = \Carbon::now();
            $this->task->save();
            
            $terminal->Status = 'PROVISION_FAILED';
            $terminal->save();
        }

        if($terminal->Category=="SBB")
        {
            $activeGoDirectNetwork = Network::where('name', '=', 'GoDirect Network')
                ->where('Active_YN_Flag', '=', 'Yes')
                ->where('Inmarsat_Preferred_Network_YN_Flag', '=', 'Y')->first();
    
            if( !is_null($activeGoDirectNetwork) && $isProvisioningSuccessful )
            {
                Log::info( 'GoDirect Network is preferred network. Provisioning is successful and marking the terminal as active.' );
                $terminal->Status = 'ACTIVE';
    
                if( $terminal->Activation_Date == '0000-00-00 00:00:00' )
                {
                    $terminal->Activated_By = $this->task->Data3;
                    $terminal->Activation_Date = \Carbon::today();
                }
    
                $terminal->save();
            }
    
            if( is_null($activeGoDirectNetwork) )
            {
                Log::info('GoDirect Network is not default network. Not updating terminal status.');
            }
        }
    }

    private function createSim( $imsi, $terminalId, RadminService $radminService )
    {
        $simId = 0;

        try
        {
            $simId = $radminService->getSimId($imsi);
        }
        catch(\Exception $e)
        {
            Log::error($e);
        }

        if($simId == 0)
        {
            $simDetails = $radminService->createSim($terminalId);
            $simId = $simDetails['data']['id'];
        }
        else
        {
            $radminService->updateSim($simId,$terminalId);
        }

        return $simId; 
    }

    private function createSimProvisioning( $simId, $terminalId, RadminService $radminService )
    {
        $simProvisioningId = 0;

        try
        {
            $simProvisioningDetails = $radminService->listSIMProvisioning($simId);
            $simProvisioningId = $simProvisioningDetails[0]['id'];
        }
        catch(\Exception $e)
        {
            Log::error($e);
        }

        if( $simProvisioningId != 0 )
        {
            $radminService->deleteSimProvisioning($simId, $simProvisioningId);
        }

        $radminService->createSimProvisioning($terminalId, $simId);
    }
    
    private function createJxSim($terminalId,$packageId, RadminService $radminService )
    {
        $radminTerminalId = 0;
    
        try
        {
            new JxSubscriptionService($packageId);
            
            $jxSubscriptionData=JxSubscription::where('package-id','=',$packageId)->get()->toArray();
            
            if(count($jxSubscriptionData)>0)
            {
                $terminal = Terminal::findOrFail($terminalId);
                $terminal->Activation_Date = date('Y-m-d H:i:s',strtotime($jxSubscriptionData[0]['activated-at']));
            
                $terminal->save();
            
                $iplong = ip2long($jxSubscriptionData[0]['dhcp-range-start-address-ipv4']);
                $masklong = ip2long($jxSubscriptionData[0]['dhcp-server-netmask-v4']);
                $base = ip2long('255.255.255.255');
            
                $ipmaskand = $iplong & $masklong;
                $network_address = long2ip($ipmaskand );
                $cidr = 32-log(($masklong ^ $base)+1,2);
            
                $network_address_range = $network_address . '/' .$cidr;
            
                if(isset($jxSubscriptionData[0]["dhcp-range-start-address-ipv4"] ))
                {
                    $service = Service::where('Terminal_Idx','=',$terminalId)->where('Service','=','IP_RANGE')->whereRaw('(Activation_Date < now() AND ( Deactivation_Date IS NULL OR Deactivation_Date > now() ))')->first();
                    
                    if( is_null($service))
                    {
                        $service = new Service;
                        
                        $service->service = 'IP_RANGE';
                        $service->Terminal_Idx = $terminalId;
                        $service->number = $network_address_range;
                        $service->Data1 = $jxSubscriptionData[0]['dhcp-server-netmask-v4'];
                        $service->activationDate = $jxSubscriptionData[0]['activated-at'];
                    }
                    else 
                    {
                        $service->number = $network_address_range;
                        $service->Data1 = $jxSubscriptionData[0]['dhcp-server-netmask-v4'];
                        $service->activationDate = $jxSubscriptionData[0]['activated-at'];
                    }
                    
                    $service->save();
                }
                
            }
            
            $radminTerminalId= $radminService->getRadminTerminalId($terminalId);
            
            if($radminTerminalId == 0)
            {
                $response=$radminService->createJxSim($terminalId);
                $radminTerminalId=$response["data"]["id"];
            }
            else
            {
                $radminService->updateJxSim($terminalId,$radminTerminalId);
            }
            
            return $radminTerminalId;
        }
        catch(\Exception $e)
        {
            Log::error($e);
			throw $e;
        }
    
        
    }
    
    private function createJxSimProfile( $radminTerminalId, $terminalId, $radminService )
    {
        $simProvisioningId = 0;
    
        try
        {
            $simProvisioningDetails= $radminService->listSvnProfile($radminTerminalId);
            
            if(isset($simProvisioningDetails[0]['id']))
            {
               $simProvisioningId = $simProvisioningDetails[0]['id'];
            }
            
            if( $simProvisioningId == 0)
            {
                $response=$radminService->createJxSvnProfile($terminalId,$radminTerminalId);
            }
            else
            {
                $response=$radminService->updateJxSvnProfile($terminalId,$radminTerminalId, $simProvisioningId);
            }
        }
        catch(\Exception $e)
        {
            Log::error($e);
			throw $e;
        }
    
       
    }
}

