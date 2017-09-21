<?php

namespace App\Http\Controllers\V1;

use Monolog\Logger;
use App\Http\Controllers\Controller;
use App\Http\Models\Service;
use App\Http\Models\Task;
use App\Http\Models\Terminal;
use App\Http\Models\TrafficShapingTerminal;
use App\Http\Models\TrafficShapingNotification;
use App\Http\Services\FlowGuardService;
use App\Jobs\TrafficShaping;
use Monolog\Handler\StreamHandler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TrafficShapingController extends Controller
{
    /**
     * @SWG\Post(
     *     path="/traffic-terminals/{terminalId}",
     *     summary="Create/Update traffic shaping terminal",
     *     tags={"traffic-shaping"},
     *     description="This resource is for creating/update a traffic shaping terminal.",
     *     operationId="createFlowGuardTerminal",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         name="terminalId",
     *         in="path",
     *         description="Terminal Id",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="threshold",
     *         in="formData",
     *         description="Threshold(in minutes) at which rule activates.",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="streamingRate",
     *         in="formData",
     *         description="Streaming rate to impose traffic limit in kilobits/second",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="activatedBy",
     *         in="formData",
     *         description="Activated by",
     *         required=true,
     *         type="string",
     *     ),
     *    @SWG\Response(
     *         response=200,
     *         description="Success",
     *     ),
     *    @SWG\Response(
     *         response="400",
     *         description="Invalid tag value",
     *     ),
     *    @SWG\Response(
     *         response="401",
     *         description="Unauthorized access",
     *     ),
     *    @SWG\Response(
     *         response="403",
     *         description="Forbidden",
     *     ),
     *    @SWG\Response(
     *         response="404",
     *         description="Not found",
     *     ),
     *     @SWG\Response(
     *         response="405",
     *         description="Method not allowed",
     *     ),
     *    @SWG\Response(
     *          response="500",
     *         description="Internal server error",
     *     ),
     *    @SWG\Response(
     *         response="503",
     *         description="Service unavailable",
     *     ),
     *    @SWG\Response(
     *         response="504",
     *         description="Gateway timeout error",
     *     )
     *
     * ),
     */
    public function createFlowGuardTerminal ($terminalId, Request $request)
    {
        DB::beginTransaction();

        try
        {
            $terminal = Terminal::findOrFail( $terminalId );

            if (count( $terminal ) > 0)
            {
                $flowGuardTerminal = TrafficShapingTerminal::where( 'Terminal_Idx', '=', $terminalId )->whereRaw( 'Start_Date < NOW() AND IFNULL(End_Date, NOW()) >= NOW()' )
                                                            ->where( 'Status', '=', 'ACTIVE' )
                                                            ->first();

                if ($request->threshold == 0 && $request->streamingRate == 0)
                {
                    if (count( $flowGuardTerminal ) > 0)
                    {
                        try
                        {
                            $task1 = new Task( 
                                    [
                                        'task' => 'TASK_FLOWGUARD_DISABLE',
                                        'data1' => $terminalId,
                                        'data3' => $request->activatedBy,
                                        'createdOn' => \Carbon::now(),
                                        'firstValidOn' => \Carbon::now(),
                                        'status' => 'STATUS_NEW'
                                    ] );

                            $task1->save();

                            DB::commit();

                            $flowGuardService = new FlowGuardService();

                            $flowGuardService->replaceIpAddress( $terminalId, $terminal->TPK_DID, "removeIp" );

                            $flowGuardTerminal->Updated_By = $request->activatedBy;
                            $flowGuardTerminal->End_Date = \Carbon::now();
                            $flowGuardTerminal->Status = "DEACTIVATED";

                            $flowGuardTerminal->save();

                            $task1->Status = 'STATUS_DONE_OK';
                            $task1->Finish_Date = \Carbon::now();
                            $task1->Message = 'Traffic terminal disabled successfully';
                            $task1->save();

                            return response( 
                                    [
                                        'status' => 'SUCCESS',
                                        'message' => 'Traffic terminal disabled successfully.'
                                    ], 200 );
                        }
                        catch (\Exception $e)
                        {
                            $task1->Status = 'STATUS_DONE_FAIL';
                            $task1->Finish_Date = \Carbon::now();
                            $task1->Message = $e->getMessage();
                            $task1->save();

                            Log::error( $e );

                            return response( 
                                    [
                                        'status' => 'FAILED',
                                        'message' => 'Error occurred while disabling traffic terminal. Please try again.' . $e->getLine() . $e->getFile(),
                                        'error' => $e->getMessage()
                                    ], 500 );
                        }
                    }
                    return response( [
                        'status' => 'FAIL',
                        'message' => 'Traffic terminal not found.'
                    ], 404 );
                }
                else
                {
                    $trafficShapingTerminal = new TrafficShapingTerminal();
                    $trafficShapingTerminal->Terminal_Idx = $terminalId;
                    $trafficShapingTerminal->Traffic_Reset_Date = date( 'Y-m-d', strtotime( 'first day of next month' ) );
                    $trafficShapingTerminal->Threshold_Unit = 'min'; 
                    $trafficShapingTerminal->Threshold_Value = $request->threshold;
                    $trafficShapingTerminal->Streaming_Rate = $request->streamingRate;
                    $trafficShapingTerminal->Activated_By = $request->activatedBy;
                    $trafficShapingTerminal->Status = "PENDING_ACTIVATION";
                    $trafficShapingTerminal->Start_Date = \Carbon::now();

                    $trafficShapingTerminal->save();

                    $task = new Task( 
                            [
                                'task' => 'TASK_FLOWGUARD_UPDATE',
                                'data1' => $terminalId,
                                'data3' => $request->input( 'activatedBy' ),
                                'createdOn' => \Carbon::now(),
                                'firstValidOn' => \Carbon::now(),
                                'status' => 'STATUS_WAIT'
                            ] );

                    $task->save();

                    DB::commit();

                    $trafficShapingJob = (new TrafficShaping( $task ))->delay( 2 );

                    if (! is_null( $trafficShapingJob ))
                    {
                        $this->dispatch( $trafficShapingJob );
                    }

                    for ($i = 0; $i < 10; $i ++)
                    {
                        $task = Task::where( 'Data1', '=', $terminalId )->where( 'Task', 'LIKE', 'TASK_FLOWGUARD_UPDATE' )
                            ->orderBy( 'Entry_Date', 'desc' )
                            ->first();
                        
                        Log::info( 'Checking ' . $task->Task . ' status.' );

                        if ($task->Status == 'STATUS_DONE_OK')
                        {
                            if (count( $flowGuardTerminal ) > 0)
                            {
                                $flowGuardTerminal->Updated_By = $request->activatedBy;
                                $flowGuardTerminal->End_Date = \Carbon::now();
                                $flowGuardTerminal->Status = "DEACTIVATED";
                                
                                $flowGuardTerminal->save();
                            }

                            Log::info( $task->Task . ' is completed successfully.' );

                            return response( 
                                    [
                                        'status' => 'SUCCESS',
                                        'message' => 'FlowGuard terminal details updated successfully.'
                                    ], 200 );
                        }
                        elseif ($task->Status == 'STATUS_DONE_FAIL')
                        {
                            Log::info( $task->Task . ' failed.' );

                            return response( 
                                    [
                                        'status' => 'FAILED',
                                        'message' => 'Error occurred during provisioning. Please check task manager for more details.'
                                    ], 500 );
                        }

                        sleep( 2 );
                    }

                    $trafficShapingTerminal->Status = 'PROVISION_PENDING';
                    $trafficShapingTerminal->save();

                    return response( 
                            [
                                'status' => 'PENDING',
                                'message' => 'Provisioning is in progress. Please check task manager for more details.'
                            ], 504 );
                }
            }
        }
        catch (\Exception $e)
        {
            Log::error( $e );
            DB::rollback(); // FIXME:: has to check db rollback once

            return response( 
                    [
                        'status' => 'FAILED',
                        'message' => 'Error occurred while updating traffic shaping terminal. Please try again.' . $e->getLine() . $e->getFile(),
                        'error' => $e->getMessage()
                    ], 500 );
        }
    }
    /**
     * @SWG\Get(
     *     path="/traffic-terminals/{terminalId}/usage",
     *     summary="Get traffic terminal usuage information",
     *     tags={"traffic-shaping"},
     *     description="This resource is for getting traffic terminal usuage information.",
     *     operationId="trafficTerminalUsuage",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         name="terminalId",
     *         in="path",
     *         description="Terminal Id",
     *         required=true,
     *         type="integer"
     *     ),
     *    @SWG\Response(
     *         response=200,
     *         description="Success",
     *     ),
     *    @SWG\Response(
     *         response="400",
     *         description="Invalid tag value",
     *     ),
     *    @SWG\Response(
     *         response="401",
     *         description="Unauthorized access",
     *     ),
     *    @SWG\Response(
     *         response="403",
     *         description="Forbidden",
     *     ),
     *    @SWG\Response(
     *         response="404",
     *         description="Not found",
     *     ),
     *     @SWG\Response(
     *         response="405",
     *         description="Method not allowed",
     *     ),
     *    @SWG\Response(
     *          response="500",
     *         description="Internal server error",
     *     ),
     *    @SWG\Response(
     *         response="503",
     *         description="Service unavailable",
     *     ),
     *    @SWG\Response(
     *         response="504",
     *         description="Gateway timeout error",
     *     )
     *
     * ),
     */
    public function trafficTerminalUsuage ($terminalId, Request $request)
    {
        try
        {
            $terminal = Terminal::findOrFail( $terminalId );

            if (count( $terminal ) > 0)
            {
                $flowGuardService = new FlowGuardService();

                $usuageDetails = $flowGuardService->getTerminalUsageDetails( $terminal->TPK_DID );

                if (count( $usuageDetails ) > 0)
                {
                    if (count( $usuageDetails["periodic"] ) > 0 && count( $usuageDetails["periodic"][0]["counters"] ) > 0)
                    {
                        foreach ($usuageDetails["periodic"][0]["counters"] as $usage)
                        {
                            if ($usage["app"] == "video")
                            {
                                return array(
                                    "app" => $usage["app"],
                                    "min" => ($usage["millis"] / 60000),
                                    "kiloBytes" => ($usage["bytes"] / 1024)
                                );
                            }
                        }
                    }
                }

                return array();
            }
        }
        catch (\Exception $e)
        {
            return response( [
                'message' => 'Error occurred while getting terminal usage details. Please try again.',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR );
        }
    }
    /**
     * @SWG\Put(
     *     path="/traffic-terminals-reset",
     *     summary="Update DBMan traffic terminals reset date with Flowguard",
     *     tags={"traffic-shaping"},
     *     description="This resource is for updating DBMan traffic terminals reset date with Flowguard.",
     *     operationId="updateFlowGuardTerminal",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         name="resetDate",
     *         in="formData",
     *         description="Traffic reset date : Date on which traffic counters are next reset. (Format : YYYY-MM-DD)",
     *         required=true,
     *         type="string",
     *         format="date",
     *     ),
     *    @SWG\Response(
     *         response=200,
     *         description="Success",
     *     ),
     *    @SWG\Response(
     *         response="400",
     *         description="Invalid tag value",
     *     ),
     *    @SWG\Response(
     *         response="401",
     *         description="Unauthorized access",
     *     ),
     *    @SWG\Response(
     *         response="403",
     *         description="Forbidden",
     *     ),
     *    @SWG\Response(
     *         response="404",
     *         description="Not found",
     *     ),
     *     @SWG\Response(
     *         response="405",
     *         description="Method not allowed",
     *     ),
     *    @SWG\Response(
     *          response="500",
     *         description="Internal server error",
     *     ),
     *    @SWG\Response(
     *         response="503",
     *         description="Service unavailable",
     *     ),
     *    @SWG\Response(
     *         response="504",
     *         description="Gateway timeout error",
     *     )
     *
     * ),
     */
    public function updateFlowGuardTerminal (Request $request)
    {
        try
        {
            
            $terminals = $request->input( 'terminal' );

            if (! $request->has( 'resetDate' ))
            {
                return response( 'Traffic reset date is required', Response::HTTP_BAD_REQUEST );
            }

            $this->validate( $request, [
                'resetDate' => 'date_format:Y-m-d|regex:/[0-9]{4}\-/|required'
            ] );

            $terminalDetails = TrafficShapingTerminal::query();

            $terminalDetails = $terminalDetails->distinct()
                ->select( 'terminal_traffic_shaping.Terminal_Idx as terminalId', 'terminal_traffic_shaping.Idx as id' )
                ->join( 'terminal', 
                    function ($join)
                    {
                        $join->on( 'terminal_traffic_shaping.Terminal_Idx', '=', 'terminal.Idx' )
                            ->on( DB::raw( 'IFNULL(terminal.`Deactivation_Date`, NOW())' ), '>=', DB::raw( "NOW()" ) )
                            ->on( DB::raw( 'terminal.Status' ), '=', DB::raw( "'ACTIVE'" ) );
                    } )
                ->whereRaw( 'Start_Date < NOW() AND IFNULL(End_Date, NOW()) >= NOW()' )
                ->where( 'terminal_traffic_shaping.Status', '=', 'ACTIVE' )
                ->groupBy( 'terminal_traffic_shaping.Terminal_Idx' )
                ->orderBy( 'terminal_traffic_shaping.Idx', 'Desc' )
                ->get();

            $terminalDetails = $terminalDetails->toArray();

            if (count( $terminalDetails ) > 0)
            {
                foreach ($terminalDetails as $terminal)
                {
                    DB::beginTransaction();

                    $flowGuardTerminal = TrafficShapingTerminal::where( 'Idx', '=', $terminal['id'] )->first();

                    $flowGuardTerminal->Traffic_Reset_Date = $request->input( 'resetDate' );
                    $flowGuardTerminal->Updated_By = $request->activatedBy;

                    $flowGuardTerminal->save();

                    $task = new Task( 
                            [
                                'task' => 'TASK_FLOWGUARD_UPDATE',
                                'data1' => $terminal['terminalId'],
                                'data2' => $request->input( 'resetDate' ),
                                'data3' => $request->input( 'activatedBy' ),
                                'createdOn' => \Carbon::now(),
                                'firstValidOn' => \Carbon::now(),
                                'status' => 'STATUS_WAIT'
                            ] );

                    $task->save();

                    DB::commit();

                    $trafficShapingJob = (new TrafficShaping( $task ))->delay( 2 );

                    if (! is_null( $trafficShapingJob ))
                    {
                        $this->dispatch( $trafficShapingJob );
                    }

                    for ($i = 0; $i < 10; $i ++)
                    {
                        $task = Task::where( 'Data1', '=', $terminal['terminalId'] )->where( 'Task', 'LIKE', 'TASK_FLOWGUARD_UPDATE' )
                            ->orderBy( 'Entry_Date', 'desc' )
                            ->first();
                        
                        Log::info( 'Checking ' . $task->Task . ' status.' );

                        if ($task->Status == 'STATUS_DONE_OK')
                        {
                            Log::info( $task->Task . ' is completed successfully.' );
                        }
                        elseif ($task->Status == 'STATUS_DONE_FAIL')
                        {
                            DB::rollback();
                            Log::info( $task->Task . ' failed.' );
                        }

                        sleep( 2 );
                    }
                }
            }

            return response( [
                'status' => 'SUCCESS',
                'message' => 'Update done successfully.'
            ], 200 );
        }
        catch (\Exception $e)
        {
            Log::error( $e );
            DB::rollback(); // FIXME::
            
            return response( [
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR );
        }
    }
    /**
     * @SWG\Post(
     *     path="/traffic-terminals/{terminalId}/notifications",
     *     summary="Create notification when traffic threshold is exceeded",
     *     tags={"traffic-shaping"},
     *     description="This resource is to create contacts to which notification should be triggered.",
     *     operationId="trafficTerminalNotifications",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         name="terminalId",
     *         in="path",
     *         description="Terminal Id",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="messageType",
     *         in="query",
     *         description="Message type Ex:-Email",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="contacts[]",
     *         in="query",
     *         description="Contacts to whom notification has to send",
     *         required=true,
     *         items="string",
     *         type="array",
     *         collectionFormat="multi"
     *     ),
     *    @SWG\Response(
     *         response=200,
     *         description="Success",
     *     ),
     *    @SWG\Response(
     *         response="400",
     *         description="Invalid tag value",
     *     ),
     *    @SWG\Response(
     *         response="401",
     *         description="Unauthorized access",
     *     ),
     *    @SWG\Response(
     *         response="403",
     *         description="Forbidden",
     *     ),
     *    @SWG\Response(
     *         response="404",
     *         description="Not found",
     *     ),
     *     @SWG\Response(
     *         response="405",
     *         description="Method not allowed",
     *     ),
     *    @SWG\Response(
     *          response="500",
     *         description="Internal server error",
     *     ),
     *    @SWG\Response(
     *         response="503",
     *         description="Service unavailable",
     *     ),
     *    @SWG\Response(
     *         response="504",
     *         description="Gateway timeout error",
     *     )
     *
     * ),
     */
    public function trafficTerminalNotifications($terminalId , Request $request)
    {
        DB::beginTransaction();
        
        try
        {
            $contacts = $request->input( 'contacts' );
            $messageType = $request->input( 'messageType' );
            
            // FIXME :: make contacts, messageType ,percentage are required
            // validation
            
            $terminal = Terminal::find( $terminalId );
            
            if (count( $terminal ) > 0 && count( $terminal->trafficShaping ) > 0)
            {
                $terminalContacts = $terminal->notificationContacts()
                                             ->pluck( "traffic_shapping_contact_notifications.Email as email" )
                                             ->toArray();
    
                $contactsArray=$contacts;
                $contactsNoChanges = array_intersect($contactsArray, $terminalContacts); //compare the 2 arrays, get the data which does not contain any changes.
                $contactsToAdd = array_diff($contactsArray, $contactsNoChanges); //add contacts to group based on the new data and the data with no changes.
                $contactsToRemove = array_diff( $terminalContacts, $contactsArray );
    
                if (! empty( $contactsToRemove ))
                {
                    foreach ($contactsToRemove as $key => $value)
                    {
                        $traffic_shaping_contact_remove = TrafficShapingNotification::where( 'Email', '=', $value )->where( 'Terminal_Idx', '=', $terminalId )
                                                                                        ->whereRaw( 'End_Date IS NULL OR End_Date > now()' )
                                                                                        ->first();
                        $traffic_shaping_contact_remove->End_Date = \Carbon::now();
                        $traffic_shaping_contact_remove->save();
                    }
                }
                
                if (! empty( $contactsToAdd ))
                {
                    
                    foreach ($contactsToAdd as $key => $value)
                    {
                        
                        $traffic_shaping_contact_add = new TrafficShapingNotification();
                        $traffic_shaping_contact_add->Terminal_Idx = $terminalId;
                        $traffic_shaping_contact_add->Email = $value;
                        $traffic_shaping_contact_add->Message_Type = $messageType;
                        $traffic_shaping_contact_add->Percentage = 80;
                        $traffic_shaping_contact_add->Start_Date = \Carbon::now();
                        
                        $traffic_shaping_contact_add->save();
                    }
                }
                
                \DB::commit();
                
                return response( [
                    'status' => 'SUCCESS',
                    'message' => 'Update done successfully.'
                ], 200 );
            }
            else
            {
                return response( 'Traffic shaping was not set for this terminal', 500 );
            }
        }
        catch (\Exception $e)
        {
            Log::error( $e );
            DB::rollback();
    
            return response( [
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR );
        }
    }

    /**
     * @SWG\Get(
     * path="/traffic-terminals/{terminalId}/notifications",
     * summary="View notification contacts",
     * tags={"traffic-shaping"},
     * description="This resource is to view notification contacts.",
     * operationId="getTrafficTerminalNotifications",
     * consumes={"application/json"},
     * produces={"application/json"},
     * @SWG\Parameter(
     * name="terminalId",
     * in="path",
     * description="Terminal Id",
     * required=true,
     * type="integer"
     * ),
     * @SWG\Response(
     * response=200,
     * description="Success",
     * ),
     * @SWG\Response(
     * response="400",
     * description="Invalid tag value",
     * ),
     * @SWG\Response(
     * response="401",
     * description="Unauthorized access",
     * ),
     * @SWG\Response(
     * response="403",
     * description="Forbidden",
     * ),
     * @SWG\Response(
     * response="404",
     * description="Not found",
     * ),
     * @SWG\Response(
     * response="405",
     * description="Method not allowed",
     * ),
     * @SWG\Response(
     * response="500",
     * description="Internal server error",
     * ),
     * @SWG\Response(
     * response="503",
     * description="Service unavailable",
     * ),
     * @SWG\Response(
     * response="504",
     * description="Gateway timeout error",
     * )
     *
     * ),
     */
    public function getTrafficTerminalNotifications ($terminalId, Request $request)
    {
            // Fixme: Check the overall method once
        try
        {
            $trafficShapingNotifications = TrafficShapingNotification::where( 'Terminal_Idx', '=', $terminalId )->whereRaw( 'Start_Date <= NOW() AND IFNULL(End_Date, NOW()) >= NOW()' )->get();

            return $trafficShapingNotifications->toArray();
        }
        catch (\Exception $e)
        {
            Log::error( $e );

            return response( [
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR );
        }
    }
    /**
     * @SWG\Get(
     *     path="/traffic-shaping-notification",
     *     summary="get notifications from flowguard",
     *     tags={"traffic-shaping"},
     *     description="This resource is for getting the notifications from flowguard and storing in DBMan parallely confirming the same with flowguard.",
     *     operationId="trafficShapingNotificationsSync",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *    @SWG\Response(
     *         response=200,
     *         description="Success",
     *     ),
     *    @SWG\Response(
     *         response="400",
     *         description="Invalid tag value",
     *     ),
     *    @SWG\Response(
     *         response="401",
     *         description="Unauthorized access",
     *     ),
     *    @SWG\Response(
     *         response="403",
     *         description="Forbidden",
     *     ),
     *    @SWG\Response(
     *         response="404",
     *         description="Not found",
     *     ),
     *     @SWG\Response(
     *         response="405",
     *         description="Method not allowed",
     *     ),
     *    @SWG\Response(
     *          response="500",
     *         description="Internal server error",
     *     ),
     *    @SWG\Response(
     *         response="503",
     *         description="Service unavailable",
     *     ),
     *    @SWG\Response(
     *         response="504",
     *         description="Gateway timeout error",
     *     )
     *
     * ),
     */
    public function trafficShapingNotificationsSync (Request $request)
    {
        try
        {
            $flowGuardService = new FlowGuardService();
            $flowGuardService->getTrafficShapingNotifications();
            $flowGuardService->sendTrafficShapingNotifications();

            return response( [
                'status' => 'SUCCESS',
                'message' => 'Notifications updated successfully.'
            ], 200 );
        }
        catch (\Exception $e)
        {
            Log::error( $e );
        
            return response( [
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR );
        }
    }
}
