<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Models\BSSOrderNumber;
use App\Http\Models\Cabinbilling;
use App\Http\Models\Network;
use App\Http\Models\Service;
use App\Http\Models\Task;
use App\Http\Models\Terminal;
use App\Http\Models\TerminalApnMapping;
use App\Http\Models\TerminalNetworkMapping;
use App\Http\Requests\TerminalRequest;
use App\Http\Services\DBManService;
use App\Http\Services\RadminService;
use App\Http\Services\Util;
use App\Jobs\GoDirectProvision;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TerminalController extends Controller
{
    /**
     * @SWG\Get(
     *     path="/terminals",
     *     summary="Get Terminals List",
     *     tags={"terminals"},
     *     description="This resource is dedicated to querying data Terminals",
     *     operationId="listTerminals",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         name="page",
     *         in="query",
     *         description="Filter by Page Number",
     *         required=false,
     *         type="integer",
     *         @SWG\Items(type="integer"),
     *     ),
     *     @SWG\Parameter(
     *         name="page_size",
     *         in="query",
     *         description="Number of results Per Page",
     *         required=false,
     *         type="integer",
     *         @SWG\Items(type="integer"),
     *     ),
     *     @SWG\Parameter(
     *         name="lastSyncDate",
     *         in="query",
     *         description="Filter by Last Updated Date",
     *         required=false,
     *         type="string",
     *         format="date-time",
     *     ),
     *     @SWG\Parameter(
     *         name="customerId",
     *         in="query",
     *         description="Filter Locations by Customer Id",
     *         required=false,
     *         type="integer",
     *         @SWG\Items(type="integer"),
     *         collectionFormat="csv"
     *     ),
     *     @SWG\Parameter(
     *         name="hwContactId",
     *         in="query",
     *         description="Filter Locations by Honeywell Contact Id",
     *         required=false,
     *         type="integer",
     *         @SWG\Items(type="string"),
     *         collectionFormat="csv"
     *     ),
     *     @SWG\Parameter(
     *         name="category",
     *         in="query",
     *         description="Filter Locations by Category, e.g. SBB",
     *         required=false,
     *         type="string",
     *         enum={"SBB", "Iridium"},
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Success",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(ref="#/definitions/Terminal")
     *         ),
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Bad request",
     *     ),
     *     @SWG\Response(
     *         response="401",
     *         description="Unauthorized access",
     *     )
     *
     * ),
     */
    public function listTerminals(Request $request)
    {
        ini_set('memory_limit', '-1');
        
        $this->validate($request, [
                'lastSyncDate' => 'date_format:Y-m-d H:i:s|regex:/[0-9]{4}\-/',
        ]);
        
        $terminalQuery=Terminal::query();
       
        if ($request->has('lastSyncDate')) {
                $terminalQuery->where('Updated_On', '>', $request->input('lastSyncDate'));
        }
        
        return $terminalQuery->get(); 
    }

    /**
     * @SWG\Get(
     *     path="/terminals/{terminalId}",
     *     summary="Get Terminal Details",
     *     tags={"terminals"},
     *     description="Returns the Terminal Information based on the Terminal Id.",
     *     operationId="getTerminalDetails",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *
     *     @SWG\Parameter(
     *         name="terminalId",
     *         in="path",
     *         description="Terminal ID",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Success",
     *         @SWG\Schema(
     *          @SWG\Property(
     *          property="System",
     *          type="object",
     *           @SWG\Items(ref="#/definitions/Terminal"),
     *          ),
     *         ),
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Invalid tag value",
     *     ),
     *     @SWG\Response(
     *         response="401",
     *         description="Unauthorized access",
     *     )
     * ),
     */
    public function getTerminalDetails($terminalId, Request $request)
    {
        $terminal = Terminal::with(['services' => function($query){
            $query->whereRaw('service.Deactivation_Date IS NULL OR service.Deactivation_Date > NOW()');
        }, 'apn', 'networks', 'goDirectFilterService', 'serviceQos', 'lesoCompany', 'bssOrder', 'cabinbilling'])->find($terminalId);

        $terminalDetails = $terminal->toArray();

        $terminalDetails['networks'] = [];

        foreach($terminal->networks as $network) {
            array_push($terminalDetails['networks'], [
                'id' => $network->pivot->Network_Idx, 
                'ipAllocationType' => $network->pivot->IP_Allocation_Type,
                'isActive' => $network->pivot->Active_YN_Flag,
                'pdpEnabled' => $network->pivot->PDP_Allowed_YN_Flag
            ]);
        }

        $terminalDetails['services'] = [];

        foreach($terminal->services as $service) {
            array_push($terminalDetails['services'], [
                'number' => $service->number,
                'service' => $service->service,
                'activationDate' => $service->activationDate
            ]);
        }

        return $terminalDetails;
    }

    public function createTerminal(TerminalRequest $terminalRequest)
    {
        return response([
            message => 'Create terminal API is not implemented yet.'
        ], Response::HTTP_METHOD_NOT_ALLOWED );

        $terminal = new Terminal([
            'systemId' => $terminalRequest->systemId,
            'category' =>  $terminalRequest->category,
            'imsi' => $terminalRequest->imsi,
            'iccId' => $terminalRequest->iccId,
            'billingEntity' => $terminalRequest->billingEntity,
            'psa' => $terminalRequest->psa,
            'comments' => $terminalRequest->comments,
            'activatedBy' => $terminalRequest->activatedBy,
            'primaryId' => 'IMSI',
            'activationDate' => \Carbon::now(),
            'status' => 'PENDING_ACTIVATION'
        ]);

        $terminal->save();

        return response([
            'status' => 'SUCCESS',
            'message' => 'Terminal is created successfully.'
        ], 200);
    }
    
    public function updateTerminal($terminalId, TerminalRequest $terminalRequest)
    {
        $terminal = Terminal::findOrFail($terminalId);
        $terminalCategory = ucfirst(strtolower($terminal->Category));
        
        return call_user_func_array( array( $this, 'update' . $terminalCategory . 'Terminal' ),
                array($terminalId, $terminalRequest));
    }
    
    public function updateSbbTerminal($terminalId, TerminalRequest $terminalRequest)
    {
        DB::beginTransaction();

        try
        {
            $terminal = Terminal::findOrFail($terminalId);
            $terminal->ICC_ID = trim($terminalRequest->iccId);
            $terminal->Leso_Mapping_Idx = $terminalRequest->leso;
            $terminal->User_Group_Id = $terminalRequest->userGroupId;
            $terminal->QoService_Idx = empty($terminalRequest->qos)? null: $terminalRequest->qos;
            $terminal->Firewall_Filter_Idx = $terminalRequest->goDirectFilter;
            $terminal->Sim_User_Idx = $terminalRequest->simUserId;
            $terminal->Comments = $terminalRequest->comments;
            $terminal->Updated_On = \Carbon::now();
            $terminal->Last_Updated_By = $terminalRequest->activatedBy;

            if(isset($terminalRequest->goDirectAccess))
            {
                $cabinbillingResponse = Cabinbilling::where('Terminal_Idx', '=', $terminalId)->get();

                if( count($cabinbillingResponse) > 0 && strcasecmp($cabinbillingResponse[0]->Status,'enabled') === 0 &&
                    strcasecmp($terminalRequest->goDirectAccess, 'deactivate') === 0 )
                {
                    return response([
                        'message' => 'Cabin billing is enabled for this location. Please disable it.'
                    ], Response::HTTP_BAD_REQUEST);
                }
                else if( count($cabinbillingResponse) > 0 && strcasecmp($cabinbillingResponse[0]->Status, 'disabled') === 0 &&
                    strcasecmp($terminalRequest->goDirectAccess,'deactivate') === 0 )
                {
                    //Cabinbilling::where('Terminal_Idx', '=', $terminalId)->delete();
                    $cabinbilling = Cabinbilling::find($cabinbillingResponse[0]->Idx);
                    $cabinbilling ->delete();
                }
                elseif(count($cabinbillingResponse) == 0 && strcasecmp($terminalRequest->goDirectAccess, 'activate') === 0)
                {
                    $cabinbilling = new Cabinbilling;
                    $cabinbilling->Status = 'Disabled';
                    $cabinbilling->Terminal_Idx = $terminalId;
                    $cabinbilling->save();
                }
            }

            $serviceMapping = [
                'VOICE' => 'voice',
                'DATA56' => 'data56',
                'DATA64' => 'data64',
                'FAX' => 'fax',
                'GDN_IP' => 'gdnIp',
                'SD+' => 'sdPlus'
            ];

            $services = Service::where('Terminal_Idx', '=', $terminalId)
                                ->whereRaw('(Activation_Date < now() AND ( Deactivation_Date IS NULL OR Deactivation_Date > now() ))')
                                ->where('Service', '<>', 'STATIC_IP')
                                ->get()->toArray();

            $terminalServices = [];

            if( !is_null($services) ) 
            {
                foreach($services as $service)
                {
                    $terminalServices[$service['service']] = $service['number'];
                }
            }

            if( isset( $terminalRequest->services['staticIp'] ) && !empty( $terminalRequest->services['staticIp'] ) )
            {
                $ipService = Service::whereIn('Terminal_Idx', ['0', $terminalId])
                        ->where('Number', '=', $terminalRequest->services['staticIp'])
                        ->where('Service', '=', 'STATIC_IP')
                        ->first();

                if( is_null($ipService) )
                {
                    throw new \Exception('Invalid IP Address or IP Address is already allocated.');
                }

                if( $ipService->Terminal_Idx == $terminalId && $ipService->Number != $terminalRequest->services['staticIp'] )
                {
                    throw new \Exception('Satcom1 Legacy IP Address cannot be changed.');
                }

                if( is_null( $ipService->Activation_Date ) || $ipService->Terminal_Idx == 0 )
                {
                    $ipService->Activation_Date = \Carbon::today();
                }

                $ipService->Terminal_Idx = $terminalId;
                $ipService->save();
            }

            foreach($serviceMapping as $name => $paramName) 
            {
                //Service details present in request
                if( array_key_exists($paramName, $terminalRequest->services) && strlen($terminalRequest->services[$paramName]) > 0)
                {
                    if( array_key_exists( $name, $terminalServices ) )
                    {
                        if( $terminalRequest->services[$paramName] != $terminalServices[$name] )
                        {
                            Log::info("Deactivating service $name as number changed to {$terminalRequest->services[$paramName]}.");
                            $service = Service::where('Number', '=', $terminalServices[$name])
                                                ->whereRaw('(Activation_Date < now() AND ( Deactivation_Date IS NULL OR Deactivation_Date > now() ))')
                                                ->where('Service', '=', $name)->where('Terminal_Idx', '=', $terminalId)->first();
                            $service->deactivationDate = \Carbon::today();
                            $service->save();
                        }
                        else
                        {
                            Log::info("For service $name, data in database and request are same. No udpates are required.");
                            //Data in database and request are same. No udpates are required.
                            continue;
                        }
                    }

                    Log::info("Creating service $name with number {$terminalRequest->services[$paramName]}.");

                    $service = new Service;
                    $service->service = $name;
                    $service->number = $terminalRequest->services[$paramName];
                    $service->activationDate = \Carbon::today();

                    $terminal->services()->save($service);
                }
                //Service is currently enabled but not present in udpate request.
                else if( array_key_exists( $name, $terminalServices ) )
                {
                    $service = Service::where('Number', '=', $terminalServices[$name])->where('Service', '=', $name)
                                    ->whereRaw('(Activation_Date < now() AND ( Deactivation_Date IS NULL OR Deactivation_Date > now() ))')
                                    ->first();
                    $service->deactivationDate = \Carbon::today();
                    $service->save();
                }
            }

            //$terminal->apn()->sync($terminalRequest->apn);

            $apns=array();

            $apns=$terminalRequest->apn;

            $data_apns = TerminalApnMapping::where('Terminal_Idx','=',$terminalId)->lists('APN_Idx')->toArray(); //cast this to an array based on the contact ids currently in the group

            $apnsArray = $apns;
            $apnsNoChanges = array_intersect($apnsArray, $data_apns); //compare the 2 arrays, get the data which does not contain any changes.
            $apnsToAdd = array_diff($apnsArray, $apnsNoChanges); //add apns to terminal based on the new data and the data with no changes.
            $apnsToRemove = array_diff($data_apns, $apnsArray); //remove contacts from group based on the current group data and the data posted

            //loop is to remove apns
            if (!empty($apnsToRemove)) {
                foreach ($apnsToRemove as $key => $value) {
                    $terminalapn_delete = TerminalApnMapping::where('Terminal_Idx', '=', $terminalId)->where('APN_Idx', '=', $value)->first();
                    $terminalapn_delete->delete();
                }
            }
            
            //loop is to add apns
            if (!empty($apnsToAdd)) {
                foreach ($apnsToAdd as $key => $value) {
                    $terminal_apn = new TerminalApnMapping;
                    $terminal_apn->Terminal_Idx = $terminalId;
                    $terminal_apn->APN_Idx = $value;
                    $terminal_apn->save();
                }
            }

            //remove bssOrderNumber
            //BSSOrderNumber::where('Terminal_Idx','=',$terminalId)->delete();
            
            $bssorder_delete = BSSOrderNumber::where('Terminal_Idx','=',$terminalId)->first();
            
            if($bssorder_delete)
            {
                $bssorder_delete->delete();
            }

            //saving bssOrderNumber
            if(isset($terminalRequest->bssOrderNumber) && strlen($terminalRequest->bssOrderNumber) > 0)
            {
                $bssOrder = new BSSOrderNumber;
                $bssOrder->BSS_Order_Number = $terminalRequest->bssOrderNumber;
                $terminal->bssOrder()->save($bssOrder);
            }

            $networks = [];
            $goDirectNetworkIp = null;
            $availableNetworks = Network::where('Active_YN_Flag','=','Yes')->get()->toArray();

            $availableNetworksIndexedId = [];

            foreach ($availableNetworks as $network)
            {
                $availableNetworksIndexedId[$network['id']] = $network;
            }

            $goDirectJob = null;

            foreach($terminalRequest->networks as $network)
            {
                $networkId = $network['id'];

                $networks[$networkId] = [
                    'IP_Allocation_Type' => $network['ipAllocationType'],
                    'Active_YN_Flag' => 'Yes',
                    'PDP_Allowed_YN_Flag' => $network['pdpEnabled'] === true ? 'Yes': 'No',
                    'Updated_On' => \Carbon::now(),
                    'Last_Updated_By' => $terminalRequest->activatedBy
                ];

                //FIXME: Automate this step
                if(isset($availableNetworksIndexedId[$networkId]) && strcasecmp($availableNetworksIndexedId[$networkId]['name'],'S1 Legacy Network') === 0 )
                {
                    // Check if there are any new/pending/completed TASK_ADD_FIXED_IP tasks for this terminal.
                    $addTaskCount = Task::where('data1', '=', $terminalId)
                        ->where('task', '=', 'TASK_ADD_FIXED_IP')
                        ->whereIn('status', ['STATUS_DONE_OK', 'STATUS_WAIT', 'STATUS_NEW'])->count();

                    if( $addTaskCount == 0 )
                    {
                        $terminal->Activated_By = $terminalRequest->activatedBy;

                        $task = new Task([
                            'task' => 'TASK_ADD_FIXED_IP',
                            'data1' => $terminalId,
                            'data2' => $terminalRequest->services['staticIp'],
                            'data3' => $terminalRequest->activatedBy,
                            'createdOn' => \Carbon::now(),
                            'firstValidOn' => \Carbon::now(),
                            'status' => 'STATUS_NEW',
                            'message' => 'Add new IP address'
                        ]);

                        $task->save();
                    }
                }
                else if( isset($availableNetworksIndexedId[$networkId]) &&
                         strcasecmp($availableNetworksIndexedId[$networkId]['name'], 'GoDirect Network') === 0 )
                {
                    $addTaskCount = Task::where('data1', '=', $terminalId)
                                        ->where('task', '=', 'TASK_GODIRECT_ADD')->count();

                    if( $addTaskCount > 0 )
                    {
                        $taskName = 'TASK_GODIRECT_UPDATE';
                    }
                    else
                    {
                        $taskName = 'TASK_GODIRECT_ADD';
                    }

                    $task = new Task([
                        'task' => $taskName,
                        'data1' => $terminalId,
                        'data2' => $terminalRequest->services['gdnIp'],
                        'data3' => $terminalRequest->activatedBy,
                        'createdOn' => \Carbon::now(),
                        'firstValidOn' => \Carbon::now(),
                        'status' => 'STATUS_WAIT'
                    ]);

                    $task->save();

                    $goDirectJob = (new GoDirectProvision($task))->delay(2);
                }
                
                $terminalNetworkCount = TerminalNetworkMapping::where('Terminal_Idx', '=', $terminalId)->where('Network_Idx', '=', $networkId);
                $data=$terminalNetworkCount->get()->toArray();
                if( count($data) > 0 )
                {
                    if(($data[0]['IP_Allocation_Type']!=$network['ipAllocationType']) || ($data[0]['PDP_Allowed_YN_Flag']!=($network['pdpEnabled'] === true ? 'Yes': 'No')))
                    {
                        $terminal_network = TerminalNetworkMapping::where('Terminal_Idx', '=', $terminalId)->where('Network_Idx', '=', $networkId)->first();
                        $terminal_network->IP_Allocation_Type = $network['ipAllocationType'];
                        $terminal_network->Active_YN_Flag = 'Yes';
                        $terminal_network->PDP_Allowed_YN_Flag = ($network['pdpEnabled'] === true ? 'Yes': 'No');
                        $terminal_network->Updated_On = \Carbon::now();
                        $terminal_network->Last_Updated_By = $terminalRequest->activatedBy;
                        $terminal_network->save();
                    }
                }
                else
                {
                    $terminal_network = new TerminalNetworkMapping;
                    $terminal_network->IP_Allocation_Type = $network['ipAllocationType'];
                    $terminal_network->Active_YN_Flag = 'Yes';
                    $terminal_network->PDP_Allowed_YN_Flag = $network['pdpEnabled'] === true ? 'Yes': 'No';
                    $terminal_network->Created_On = \Carbon::now();
                    $terminal_network->Created_By = $terminalRequest->activatedBy;
                    $terminal_network->Terminal_Idx = $terminalId;
                    $terminal_network->Network_Idx = $networkId;
                    $terminal_network->save();
                }
            }

            //$terminal->networks()->sync($networks);
            $terminal->save();

            DB::commit();

            if( !is_null( $goDirectJob ) )
            {
                $this->dispatch( $goDirectJob );
            }

            Util::callTaskProcessor();

            $network = Network::where('Inmarsat_Preferred_Network_YN_Flag', '=', 'Y')->first();

            if( !is_null( $network ) )
            {
                $taskName = '';

                if( $network->Network_Name == 'S1 Legacy Network' )
                {
                    $taskName = 'TASK%FIXED_IP';
                }

                if( $network->Network_Name == 'GoDirect Network' )
                {
                   $taskName = 'TASK_GODIRECT%';
                }

                for( $i = 0; $i < 5; $i++ )
                {
                    $task = Task::where('Data1', '=', $terminalId)->where('Task', 'LIKE', $taskName)
                        ->orderBy('Entry_Date', 'desc')->first();

                    Log::info( $network->Network_Name . ' is active, checking ' . $task->Task . ' status.');

                    if( $task->Status == 'STATUS_DONE_OK' )
                    {
                        Log::info( $network->Network_Name . ' is active, ' . $task->Task . ' is completed successfully.');
                        return response([
                            'status' => 'SUCCESS',
                            'message' => 'Terminal details updated successfully.'
                        ], 200);
                    }
                    else if( $task->Status == 'STATUS_DONE_FAIL' )
                    {
                        Log::info( $network->Network_Name . ' is active, ' . $task->Task . ' failed.');
                        return response([
                            'status' => 'FAILED',
                            'message' => 'Error occurred during provisioning. Please check task manager for more details.'
                        ], 500);
                    }

                    sleep(2);
                }

                Log::info( $network->Network_Name . ' is active, but relevant task is pending for 10 seconds.');

                return response([
                    'status' => 'PENDING',
                    'message' => 'Provisioning is in progress. Please check task manager for more details.'
                ], 504);
            }
        }
        catch(\Exception $e)
        {
			Log::error($e);
            DB::rollback();

            return response([
                'status' => 'FAILED',
                'message' => 'Error occurred while creating terminal details. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateJxTerminal( $terminalId, TerminalRequest $terminalRequest )
    {
        DB::beginTransaction();
        
        try
        {
            $terminal = Terminal::findOrFail($terminalId);

            if ($terminalRequest->has('goDirectFilter') && !is_null($terminalRequest->goDirectFilter)) {
                $terminal->Firewall_Filter_Idx = $terminalRequest->goDirectFilter;
            }

            if ($terminalRequest->has('xiplink') && !is_null($terminalRequest->xiplink)) {
                $terminal->Xiplink = $terminalRequest->xiplink;
            }
            
            $terminal->Updated_On = \Carbon::now();
            $terminal->Last_Updated_By = $terminalRequest->activatedBy;
        
            if(isset($terminalRequest->goDirectAccess))
            {
                $cabinbillingResponse = Cabinbilling::where('Terminal_Idx', '=', $terminalId)->get();
        
                if( count($cabinbillingResponse) > 0 && strcasecmp($cabinbillingResponse[0]->Status,'enabled') === 0 &&
                        strcasecmp($terminalRequest->goDirectAccess, 'deactivate') === 0 )
                {
                    return response([
                        'message' => 'Cabin billing is enabled for this location. Please disable it.'
                    ], Response::HTTP_BAD_REQUEST);
                }
                else if( count($cabinbillingResponse) > 0 && strcasecmp($cabinbillingResponse[0]->Status, 'disabled') === 0 &&
                        strcasecmp($terminalRequest->goDirectAccess,'deactivate') === 0 )
                {
                    $cabinbilling = Cabinbilling::find($cabinbillingResponse[0]->Idx);
                    $cabinbilling ->delete();
                }
                elseif(count($cabinbillingResponse) == 0 && strcasecmp($terminalRequest->goDirectAccess, 'activate') === 0)
                {
                    
					$cabinbilling = new Cabinbilling;
                    $cabinbilling->Status = 'Disabled';
                    $cabinbilling->Terminal_Idx = $terminalId;
                    $cabinbilling->save();
                }
            }
        
            
            $terminal->save();
            
            $taskName="TASK_GODIRECT_JX_UPDATE";
            
            $task = new Task([
	        'task' => $taskName,
	        'data1' => $terminalId,
	        //'data2' => $request->input( 'packageId' ),
	        'data3' => $terminalRequest->input( 'activatedBy' ),
	        'createdOn' => \Carbon::now(),
	        'firstValidOn' => \Carbon::now(),
	        'status' => 'STATUS_WAIT'
            ]);
            
            $task->save();
            
            DB::commit();
            
            $goDirectJob = (new GoDirectProvision( $task ))->delay( 2 );
            
            if( !is_null( $goDirectJob ) )
            {
                $this->dispatch( $goDirectJob );
            }
            
            for ($i = 0; $i < 10; $i ++)
            {
                $task = Task::where( 'Data1', '=', $terminalId )->where( 'Task', 'LIKE', $taskName )
                ->orderBy( 'Entry_Date', 'desc' )
                ->first();

                Log::info( 'Checking ' . $task->Task . ' status.' );

                if ($task->Status == 'STATUS_DONE_OK')
                {
                    Log::info( $task->Task . ' is completed successfully.' );

                    return response([
                        'status' => 'SUCCESS',
                        'message' => 'Terminal details updated successfully.'
                    ], 200);
                }
                elseif ($task->Status == 'STATUS_DONE_FAIL')
                {
                    Log::info( $task->Task . ' failed.' );
					
                    $terminal->Status = 'PROVISION_FAILED';
                    $terminal->save();
					
                    return response(
                            [
                                'status' => 'FAILED',
                                'message' => 'Error occurred during provisioning. Please check task manager for more details.'
                            ], 500 );
                }
            
                sleep( 2 );
            }
			
            $terminal->Status = 'PROVISION_PENDING';
            $terminal->save();
            
            return response([
                    'status' => 'PENDING',
                    'message' => 'Provisioning is in progress. Please check task manager for more details.'
                ], 504);
        
        }
        catch(\Exception $e)
        {
            Log::error($e);
            DB::rollback();
        
            return response([
                'status' => 'FAILED',
                'message' => 'Error occurred while creating terminal details. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
	/**
     * @SWG\Delete(
     *     path="/terminal/{terminalId}",
     *     summary="Deactivate Terminal",
     *     tags={"terminals"},
     *     description="This resource is dedicated to deactivate terminal",
     *     operationId="deactivateTerminal",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         name="page",
     *         in="query",
     *         description="Filter by Page Number",
     *         required=false,
     *         type="integer",
     *         @SWG\Items(type="integer"),
     *     ),
     *     @SWG\Parameter(
     *         name="page_size",
     *         in="query",
     *         description="Number of results Per Page",
     *         required=false,
     *         type="integer",
     *         @SWG\Items(type="integer"),
     *     ),
     *     @SWG\Parameter(
     *         name="deactivatedBy",
     *         in="query",
     *         description="decativated by - name",
     *         required=true,
     *         type="string"
     *     ),
     *    @SWG\Parameter(
     *         name="terminalId",
	 *         in="path",
     *         description="Terminal Id",
     *         required=true,
     *         type="integer",
     *         @SWG\Items(type="integer"),
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Success",
     *         
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Bad request",
     *     ),
     *     @SWG\Response(
     *         response="401",
     *         description="Unauthorized access",
     *     )
     *
     * ),
     */
    public function deactivateTerminal($terminalId, TerminalRequest $terminalRequest)
    {
		try
		{
			$DBManService = new DBManService;
			$deactivatedBy = $terminalRequest->input('deactivatedBy');
			$deactivationComment = $terminalRequest->input('comment');
			$response = $DBManService->deactivateTerminal($terminalId, $deactivatedBy,$deactivationComment);
			return $response;
		}
		catch(\Exception $e)
		{
            return response([
                'message' => 'Error occurred while deactivating terminal details. Please try again.',
                'error' => $e->getMessage() 
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
		}
	
    }
    
    public function createJxTerminalTask($terminalId, Request $request)
    {
        try
        {
            if (! $request->has( 'packageId' ))
            {
                return response( 'Package Id is required', Response::HTTP_BAD_REQUEST );
            }
            
            $task = new Task( 
                    [
                        'task' => 'TASK_GODIRECT_JX_ADD',
                        'data1' => $terminalId,
                        'data2' => $request->input( 'packageId' ),
                        'data3' => $request->input( 'activatedBy' ),
                        'createdOn' => \Carbon::now(),
                        'firstValidOn' => \Carbon::now(),
                        'status' => 'STATUS_WAIT'
                    ] );
            
            $task->save();
            
            $taskStatus=$task->toArray();
            
            $goDirectJob = (new GoDirectProvision( $task ))->delay( 2 );
            
            if (! is_null( $goDirectJob ))
            {
                $this->dispatch( $goDirectJob );
            }

            $terminal = Terminal::findOrFail($terminalId);
            
            for ($i = 0; $i < 10; $i ++)
            {
                $task = Task::where( 'Data1', '=', $terminalId )->where( 'Task', 'LIKE', 'TASK_GODIRECT_JX_ADD' )
                            ->orderBy( 'Entry_Date', 'desc' )
                            ->first();

                Log::info( 'Checking ' . $task->Task . ' status.' );

                if ($task->Status == 'STATUS_DONE_OK')
                {
                    Log::info( $task->Task . ' is completed successfully.' );
                    
                    return response( [
                        'status' => 'SUCCESS',
                        'message' => 'JX Terminal details updated successfully.'
                    ], 200 );
                }
                elseif ($task->Status == 'STATUS_DONE_FAIL')
                {
                    Log::info( $task->Task . ' failed.' );
                    
        			$terminal->Status = 'PROVISION_FAILED';
                    $terminal->save();
                    
                    return response( 
                            [
                                'status' => 'FAILED',
                                'message' => 'Error occurred during provisioning. Please check task manager for more details.'
                            ], 500 );
                }

                sleep( 2 );

            }
            
            $terminal->Status = 'PROVISION_PENDING';
            $terminal->save();
            
            return response([
                'status' => 'PENDING',
                'message' => 'Provisioning is in progress. Please check task manager for more details.'
            ], 504);
        }
        catch(\Exception $e)
        {
            return response([
                'message' => 'Error occurred while adding JX service details. Please try again.',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
