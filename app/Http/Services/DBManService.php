<?php

namespace App\Http\Services;
use App\Http\Models\Cabinbilling;
use App\Http\Models\LimitMonitor;
use App\Http\Models\Network;
use App\Http\Models\Service;
use App\Http\Models\SystemAirtimePackageMapping;
use App\Http\Models\SystemCustomerMapping;
use App\Http\Models\SystemLocationMapping;
use App\Http\Models\Task;
use App\Http\Models\Terminal;
use App\Http\Models\System;
use App\Jobs\GoDirectDeleteProvision;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class DBManService
{
    use DispatchesJobs;

    protected $requestOptions = [];

    public function __construct()
    {

    }

    public function deactivateTerminal($terminalId, $deactivatedBy,$deactivationComment='')
    {
        try {
            //Chcek whether terminal is ACTIVE or not
            $terminal = DB::table('terminal')
                ->where('Idx', '=', $terminalId)
                ->where('Status', '=', 'ACTIVE')
                ->whereRaw('( Deactivation_Date IS NULL OR Deactivation_Date > now() )')
                ->first();


            if (count($terminal) == 0) {
                return response([
                    'message' => 'Terminal is not ACTIVE or not found'
                ], Response::HTTP_NOT_FOUND);
            }

            $task1 = new Task([
                'task' => 'TASK_DEACTIVATE_TERMINAL',
                'data1' => $terminalId,
                'data2' => '',
                'data3' => $deactivatedBy,
                'createdOn' => \Carbon::now(),
                'firstValidOn' => \Carbon::now(),
                'status' => 'STATUS_NEW'
            ]);

            $task1->save();

            $cabinbillingResponse = Cabinbilling::where('Terminal_Idx', '=', $terminalId)->get();
            
            if (count($cabinbillingResponse) > 0 && strcasecmp($cabinbillingResponse[0]->Status, 'enabled') === 0) {
                throw new \Exception('Cabin billing is enabled. Please disable the cabin billing before deactivating Terminal.');
            
            } else if (count($cabinbillingResponse) > 0 && strcasecmp($cabinbillingResponse[0]->Status, 'disabled') === 0) {
                // Cabinbilling::where('Terminal_Idx', '=', $terminalId)->delete();
                $cabinbilling = Cabinbilling::find( $cabinbillingResponse[0]->Idx );
                $cabinbilling->delete();
            
            }
            
            if ($terminal->Category === 'SBB' || $terminal->Category === 'BGAN') {
                
                $terminalNetworkDetails = DB::table('terminal_network_mapping')
                    ->select(
                        'terminal_network_mapping.Network_Idx',
                        'terminal_network_mapping.Active_YN_Flag'
                    )
                    ->where('terminal_network_mapping.Active_YN_Flag', '=', DB::raw("'Yes'"))
                    ->where('Terminal_Idx', '=', $terminalId);

                $terminalNetworks = $terminalNetworkDetails->get();
                $availableNetworks = Network::where('Active_YN_Flag', '=', 'Yes')->get()->toArray();

                $availableNetworksIndexedId = [];

                foreach ($availableNetworks as $network) {
                    $availableNetworksIndexedId[$network['id']] = $network;
                }

                $serviceIPs = DB::table('service')
                    ->select(DB::raw('MAX(CASE WHEN Service = "STATIC_IP" THEN `Number` END) AS STATIC_IP'),
                        DB::raw('MAX(CASE WHEN Service = "GDN_IP" THEN `Number` END) AS GDN_IP'))
                    ->whereRaw('(Activation_Date < now() AND ( Deactivation_Date IS NULL OR Deactivation_Date > now() ))')
                    ->where('Terminal_Idx', '=', $terminalId)
                    ->first();

                foreach ($terminalNetworks as $row) {
                    $networkId = $row->Network_Idx;

                    if ((isset($availableNetworksIndexedId[$networkId]) && strcasecmp($availableNetworksIndexedId[$networkId]['name'], 'S1 Legacy Network') === 0) && ($serviceIPs->STATIC_IP !== "")) {
                        $task2 = new Task([
                            'task' => 'TASK_REMOVE_FIXED_IP',
                            'data1' => $terminalId,
                            'data2' => $serviceIPs->STATIC_IP,
                            'data3' => $deactivatedBy,
                            'createdOn' => \Carbon::now(),
                            'firstValidOn' => \Carbon::now(),
                            'status' => 'STATUS_NEW'
                        ]);

                        $task2->save();
                    } else if (isset($availableNetworksIndexedId[$networkId]) &&
                        strcasecmp($availableNetworksIndexedId[$networkId]['name'], 'GoDirect Network') === 0
                    ) {
                        $task3 = new Task([
                            'task' => 'TASK_GODIRECT_DISABLE',
                            'data1' => $terminalId,
                            'data2' => $serviceIPs->GDN_IP,
                            'data3' => $deactivatedBy,
                            'createdOn' => \Carbon::now(),
                            'firstValidOn' => \Carbon::now(),
                            'status' => 'STATUS_NEW'
                        ]);

                        $task3->save();
                        $goDirectDeleteSIMJob = new GoDirectDeleteProvision($task3);
                    }
                }

                if (isset($goDirectDeleteSIMJob) && !is_null($goDirectDeleteSIMJob)) {

                    $this->dispatch($goDirectDeleteSIMJob);
                }

                Util::callTaskProcessor();

                $network = Network::where('Inmarsat_Preferred_Network_YN_Flag', '=', 'Y')->first();

                if (!is_null($network)) {
                    if ($network->Network_Name == 'S1 Legacy Network') {
                        $taskName = 'TASK_REMOVE_FIXED_IP';
                    }

                    if ($network->Network_Name == 'GoDirect Network') {
                        $taskName = 'TASK_GODIRECT_DISABLE';
                    }


                    for ($i = 0; $i < 5; $i++) {
                        $task = Task::where('Data1', '=', $terminalId)->where('Task', '=', $taskName)
                            ->orderBy('Entry_Date', 'desc')->first();


                        if ($task->Status == 'STATUS_DONE_OK') {
                            DB::beginTransaction();
                            /*Service::where('Terminal_Idx', '=', $terminalId)
                                ->where('Service', '!=', 'STATIC_IP')
                                ->update(['Deactivation_Date' => \Carbon::now()]);

                            Terminal::where('Idx', '=', $terminalId)
                                ->update(['Status' => 'DEACTIVATED',
                                    'Deactivation_Date' => \Carbon::now(),
                                    'Updated_On' => \Carbon::now(),
                                    'Last_Updated_By' => $deactivatedBy]);*/
                            
                            $services_data = Service::where( 'Terminal_Idx', '=', $terminalId )->where( 'Service', '!=', 'STATIC_IP' )
                            ->whereRaw( '( Deactivation_Date IS NULL OR Deactivation_Date > now() )' )
                            ->get()
                            ->toArray();
                            
                            foreach ($services_data as $service)
                            {
                                $service_delete = Service::where( 'Idx', '=', $service['id'] )->first();
                                $service_delete->Deactivation_Date = \Carbon::today();
                                $service_delete->save();
                            }
                            
                            $terminal_delete = Terminal::where( 'Idx', '=', $terminalId )->first();
                            $terminal_delete->Status = 'DEACTIVATED';
                            $terminal_delete->Deactivation_Date = \Carbon::today();
                            $terminal_delete->Updated_On = \Carbon::now();
                            $terminal_delete->Last_Updated_By = $deactivatedBy;
							$terminal_delete->Deactivation_Comments =  $deactivationComment;
                            $terminal_delete->save();

                            DB::commit();
                            $task1->Status = 'STATUS_DONE_OK';
                            $task1->Finish_Date = \Carbon::now();
                            $task1->Message = 'Terminal deactivated successfully';
                            $task1->save();

                            return response([
                                'status' => 'SUCCESS',
                                'message' => 'Terminal deactivated successfully.'
                            ], Response::HTTP_OK);
                        } else if ($task->Status == 'STATUS_DONE_FAIL') {

                            $task1->Status = 'STATUS_DONE_FAIL';
                            $task1->Finish_Date = \Carbon::now();
                            $task1->save();

                            throw new \Exception('Error occurred while deactivating. Please check task manager for more details.');

                        }

                        sleep(5);
                    }
                    throw new \Exception('Provisioning is in progress. Please check the task manager for more details.');

                }
            } 
            elseif ($terminal->Category == 'JX')
            {
                $serviceIPs = DB::table('service')
                        ->whereRaw('(Activation_Date < now() AND ( Deactivation_Date IS NULL OR Deactivation_Date > now() ))')
                        ->where('Terminal_Idx', '=', $terminalId)
                        ->where('Service', '=', 'IP_RANGE')
                        ->first();
                
                $task3 = new Task([
                    'task' => 'TASK_GODIRECT_JX_DISABLE',
                    'data1' => $terminalId,
                    'data2' => $serviceIPs->Number,
                    'data3' => $deactivatedBy,
                    'createdOn' => \Carbon::now(),
                    'firstValidOn' => \Carbon::now(),
                    'status' => 'STATUS_NEW'
                ]);
                
                $task3->save();
                
                $goDirectDeleteSIMJob = new GoDirectDeleteProvision($task3);
                
                if (isset($goDirectDeleteSIMJob) && !is_null($goDirectDeleteSIMJob)) {
                
                    $this->dispatch($goDirectDeleteSIMJob);
                }
                
                for ($i = 0; $i < 5; $i++) {
                  
                    $task = Task::where('Data1', '=', $terminalId)->where('Task', '=', 'TASK_GODIRECT_JX_DISABLE')
                    ->orderBy('Entry_Date', 'desc')->first();
                   
                    
                    if ($task->Status == 'STATUS_DONE_OK') {
                       
                        DB::beginTransaction();
                       
                        $services_data = Service::where( 'Terminal_Idx', '=', $terminalId )->where( 'Service', '!=', 'STATIC_IP' )
                        ->whereRaw( '( Deactivation_Date IS NULL OR Deactivation_Date > now() )' )
                        ->get()
                        ->toArray();
                        
                        foreach ($services_data as $service)
                        {
                            $service_delete = Service::where( 'Idx', '=', $service['id'] )->first();
                            $service_delete->Deactivation_Date = \Carbon::today();
                            $service_delete->save();
                        }
                       
                        
                        $terminal_delete = Terminal::where( 'Idx', '=', $terminalId )->first();
                        $terminal_delete->Status = 'DEACTIVATED';
                        $terminal_delete->Deactivation_Date = \Carbon::today();
                        $terminal_delete->Updated_On = \Carbon::now();
                        $terminal_delete->Last_Updated_By = $deactivatedBy;
                        $terminal_delete->Deactivation_Comments =  $deactivationComment;
                        $terminal_delete->save();
                
                        DB::commit();
                        
                        $task1->Status = 'STATUS_DONE_OK';
                        $task1->Finish_Date = \Carbon::now();
                        $task1->Message = 'Terminal deactivated successfully';
                        $task1->save();
                
                        return response([
                            'status' => 'SUCCESS',
                            'message' => 'Terminal deactivated successfully.'
                        ], Response::HTTP_OK);
                        
                    } else if ($task->Status == 'STATUS_DONE_FAIL') {
                
                        $task1->Status = 'STATUS_DONE_FAIL';
                        $task1->Finish_Date = \Carbon::now();
                        $task1->save();
                
                        throw new \Exception('Error occurred while deactivating. Please check task manager for more details.');
                
                    }
                
                    sleep(5);
                }
                throw new \Exception('Provisioning is in progress. Please check the task manager for more details.');
            }
            else {
                
                DB::beginTransaction();
                
               /* Service::where('Terminal_Idx', '=', $terminalId)
                    ->where('Service', '!=', 'STATIC_IP')
                    ->update(['Deactivation_Date' => \Carbon::now()]);

                Terminal::where('Idx', '=', $terminalId)
                    ->update(['Status' => 'DEACTIVATED',
                        'Deactivation_Date' => \Carbon::now(),
                        'Updated_On' => \Carbon::now(),
                        'Last_Updated_By' => $deactivatedBy]);*/
                
                $services_data = Service::where( 'Terminal_Idx', '=', $terminalId )->where( 'Service', '!=', 'STATIC_IP' )
                ->whereRaw( '( Deactivation_Date IS NULL OR Deactivation_Date > now() )' )
                ->get()
                ->toArray();
                foreach ($services_data as $service)
                {
                    $service_delete = Service::where( 'Idx', '=', $service['id'] )->first();
                    $service_delete->Deactivation_Date = \Carbon::today();
                    $service_delete->save();
                }
                
                $terminal_delete = Terminal::where( 'Idx', '=', $terminalId )->first();
                $terminal_delete->Status = 'DEACTIVATED';
                $terminal_delete->Deactivation_Date = \Carbon::today();
                $terminal_delete->Updated_On = \Carbon::now();
                $terminal_delete->Last_Updated_By = $deactivatedBy;
				$terminal_delete->Deactivation_Comments =  $deactivationComment;
                $terminal_delete->save();

                DB::commit();
                $task1->Status = 'STATUS_DONE_OK';
                $task1->Finish_Date = \Carbon::now();
                $task1->Message = 'Terminal deactivated successfully';
                $task1->save();

                return response([
                    'status' => 'SUCCESS',
                    'message' => 'Terminal deactivated successfully.'
                ], Response::HTTP_OK);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            $task1->Status = 'STATUS_DONE_FAIL';
            $task1->Finish_Date = \Carbon::now();
            $task1->Message = $e->getMessage();
            $task1->save();
            throw $e;

        }
    }

    public function deactivateSystem($systemId, $deactivatedBy,$deactivationComment='')
    {
        try {
            $task1 = new Task([
                'task' => 'TASK_DEACTIVATE_SYSTEM',
                'data1' => $systemId,
                'data2' => '',
                'data3' => $deactivatedBy,
                'createdOn' => \Carbon::now(),
                'firstValidOn' => \Carbon::now(),
                'status' => 'STATUS_NEW'
            ]);

            $task1->save();

            $terminals = DB::table('terminal')
                ->select('terminal.Idx', 'terminal.Category')
                ->where('terminal.System_Idx', '=', $systemId)
                ->where('terminal.System_Idx', '!=', 0)
                ->whereRaw('(terminal.Deactivation_Date IS NULL OR terminal.Deactivation_Date > NOW())')
                ->groupBy('terminal.Idx')
                ->orderByRaw('case when terminal.Category in ("SBB", "BGAN") then -1 else terminal.Category end')
                ->get();


            if (count($terminals) > 0) {
                foreach ($terminals as $terminal) {
                   $this->deactivateTerminal($terminal->Idx, $deactivatedBy);
                }

                DB::beginTransaction();
                $airTimePackages = DB::table('who_has_airtime_package_dbman')
                    ->where('System_Idx', '=', $systemId)
                    ->whereRaw('( Status = CONVERT( "ACTIVE" USING utf8) OR Status = CONVERT("PENDING-EXPIRE" USING utf8) )')
                    ->get();

                if (count($airTimePackages) > 0) {
                    foreach ($airTimePackages as $airTimePacakge) {
                       /* DB::table('system_airtime_package_account_mapping')
                            ->where('System_Idx', '=', $airTimePacakge->System_Idx)
                            ->whereRaw('End_Date IS NULL OR End_Date > now()')
                            ->update(['End_Date' => \Carbon::now()]);*/
                        
                        $system_airtime_mapping = SystemAirtimePackageMapping::where( 'System_Idx', '=', $airTimePacakge->System_Idx )->whereRaw( 'End_Date IS NULL OR End_Date > now()' )
                        ->get()
                        ->toArray();
                        
                        foreach ($system_airtime_mapping as $system_airtime)
                        {
                            $system_airtime_delete = SystemAirtimePackageMapping::where( 'Idx', '=', $system_airtime['id'] )->first();
                            $system_airtime_delete->End_Date = \Carbon::today();
                            $system_airtime_delete->save();
                        }
                    }
                }

                $airTimeMonitors = DB::table('limit_monitor')
                    ->select('system_location_mapping.System_Idx', 'limit_monitor.Idx')
                    ->join('system_location_mapping', function ($join) {
                        $join->on('system_location_mapping.System_Idx', '=', 'limit_monitor.System_Idx');
                    })
                    ->join('location', function ($join) {
                        $join->on('system_location_mapping.System_Idx', '=', 'location.Idx');
                    })
                    ->leftJoin('limit_monitor_contact_mapping', function ($join) {
                        $join->on('limit_monitor.Idx', '=', 'limit_monitor_contact_mapping.Limit_Monitor_Idx');
                    })
                    ->where('system_location_mapping.System_Idx', '=', $systemId)
                    ->whereRaw('(limit_monitor.End_Date is null or now() < limit_monitor.End_Date)')
                    ->whereRaw('(system_location_mapping.End_Date > now() OR system_location_mapping.End_Date is NULL)')
                    ->groupBy('system_location_mapping.System_Idx')
                    ->get();

                if (count($airTimeMonitors) > 0) {
                    foreach ($airTimeMonitors as $airTimeMonitor) {
                        /*DB::table('limit_monitor')
                            ->where('System_Idx', '=', $airTimeMonitor->System_Idx)
                            ->whereRaw('End_Date IS NULL OR End_Date > now()')
                            ->update(['End_Date' => \Carbon::now()]);*/
                        
                        $system_airtime_monitor = LimitMonitor::where( 'System_Idx', '=', $airTimeMonitor->System_Idx )->whereRaw( 'End_Date IS NULL OR End_Date > now()' )
                        ->get()
                        ->toArray();
                        
                        foreach ($system_airtime_monitor as $system_airtime_limit_monitor)
                        {
                            $system_airtime_limit_monitor_delete = LimitMonitor::where( 'Idx', '=', $system_airtime_limit_monitor['id'] )->first();
                            $system_airtime_limit_monitor_delete->End_Date = \Carbon::today();
                            $system_airtime_limit_monitor_delete->save();
                        }
                    }

                }
                /*DB::table('system_location_mapping')
                    ->where('System_Idx', '=', $systemId)
                    ->whereRaw('End_Date IS NULL')
                    ->update(['End_Date' => \Carbon::now()]);

                DB::table('system_customer_mapping')
                    ->where('System_Idx', '=', $systemId)
                    ->whereRaw('End_Date IS NULL')
                    ->update(['End_Date' => \Carbon::now()]);*/
                
                $system_location_mappings = SystemLocationMapping::where( 'System_Idx', '=', $systemId )->whereRaw( 'End_Date IS NULL' )
                ->get()
                ->toArray();
                
                foreach ($system_location_mappings as $system_location_mapping)
                {
                    $system_location_mapping_delete = SystemLocationMapping::where( 'Idxx', '=', $system_location_mapping['id'] )->first();
                    $system_location_mapping_delete->End_Date = \Carbon::today();
                    $system_location_mapping_delete->save();
                }
                
                $system_customer_mappings = SystemCustomerMapping::where( 'System_Idx', '=', $systemId )->whereRaw( 'End_Date IS NULL' )
                ->get()
                ->toArray();
                
                foreach ($system_customer_mappings as $system_customer_mapping)
                {
                    $system_customer_mapping_delete = SystemCustomerMapping::where( 'Idxx', '=', $system_customer_mapping['id'] )->first();
                    $system_customer_mapping_delete->End_Date = \Carbon::today();
                    $system_customer_mapping_delete->save();
                }
				
				$system_delete = System::where( 'Idx', '=', $systemId )->first();
               	$system_delete->Deactivation_Comments =  $deactivationComment;
                $system_delete->Updated_On = \Carbon::now();
				$system_delete->save();

                $task1->Status = 'STATUS_DONE_OK';
                $task1->Finish_Date = \Carbon::now();
                $task1->Message = 'System deactivated successfully';
                $task1->save();
                DB::commit();

                return response([
                    'status' => 'SUCCESS',
                    'message' => 'System deactivated successfully.'
                ], Response::HTTP_OK);

            } else {
				/*
                return response([
                    'message' => 'System is not ACTIVE or not found'
                ], Response::HTTP_NOT_FOUND);
				*/
				
				throw new \Exception('System is not ACTIVE or not found');
            }

        } catch (\Exception $e) {
            //DB::rollBack();
            $task1->Status = 'STATUS_DONE_FAIL';
            $task1->Finish_Date = \Carbon::now();
            $task1->Message = $e->getMessage();
            $task1->save();

            throw $e;
        }
    }
}
