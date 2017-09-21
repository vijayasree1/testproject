<?php
namespace App\Http\Services;

use Monolog\Handler\StreamHandler;
use App\Http\Models\Terminal;
use App\Http\Models\Service;
use App\Http\Models\Task;
use App\Http\Models\TrafficShapingNotification;
use App\Http\Models\TrafficShapingNotificationEvent;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Monolog\Logger;

use GuzzleHttp\json_decode;


ini_set('memory_limit', '-1');
set_time_limit(10000);

Class FlowGuardService
{
    var $client = null;

    var $requestOptions = [];
    public function __construct()
    {
        $this->requestOptions = config('flowguard.requestDefaultOptions');
        $clientConfig = [
            'base_uri' => config('flowguard.baseUri')
        ];

        if( config('app.debug') )
        {
            $stack = HandlerStack::create();

            $logger = with(new \Monolog\Logger('api-consumer'))->pushHandler(
                    new \Monolog\Handler\StreamHandler(storage_path('/logs/flowguard-call-log.log'), Logger::DEBUG, true)
                    );

            $stack->push(
                    Middleware::log( $logger, new MessageFormatter('{req_headers} {req_body} - {res_headers} {res_body}') )
                    );

            $clientConfig['handler'] = $stack;
        }

        $this->client = new Client($clientConfig);
    }

    public function listTrafficTerminals ()
    {
        try
        {
            $listTerminals = $this->client->request( 'GET', 'terminals', $this->requestOptions );
            
            if ($listTerminals->getStatusCode() == Response::HTTP_OK)
            {
                return json_decode( $listTerminals->getBody(), true );
            }
            
            throw new \Exception( $listTerminals->getBody()->getContents() );
        }
        catch (\Exception $e)
        {
            throw new \Exception( $e );
        }
    }

    public function getTrafficTerminalDetails ($TPK_ID)
    {
        try
        {
            $flowGuardTerminals = $this->listTrafficTerminals();
            
            if (count( $flowGuardTerminals ) > 0)
            {
                foreach ($flowGuardTerminals["items"] as $flowGuardTerminal)
                {
                    if ($flowGuardTerminal["uid"] == $TPK_ID)
                    {
                        return $flowGuardTerminal["uid"];
                    }
                }
            }
        }
        catch (\Exception $e)
        {
            throw new \Exception( $e );
        }
    }

    public function getTerminalDetails ($terminalId)
    {
        try
        {
            $listTerminals = $this->client->request( 'GET', 'terminals/' . $terminalId, $this->requestOptions );
            
            if ($listTerminals->getStatusCode() == Response::HTTP_OK)
            {
                // var_dump($listTerminals);
                return json_decode( $listTerminals->getBody(), true );
            }
            
            throw new \Exception( $listTerminals->getBody()->getContents() );
        }
        catch (\Exception $e)
        {
            throw new \Exception( $e );
        }
    }

    public function getTerminalUsageDetails ($terminalTpkId)
    {
        try
        {
            $response = $this->client->request( 'GET', 'terminals/' . $terminalTpkId . "/trafficUsage", $this->requestOptions );
            $response->getBody()->rewind();
            return json_decode( $response->getBody()->getContents(), true );
        }
        catch (ClientException $exception)
        {
            $response = json_decode( $exception->getResponse()->getBody(), true );
            $errors = [];
            
            foreach ($response as $attribute => $attribErrors)
            {
                if (isset( $attribErrors['errors'] ))
                    $errors[] = $attribErrors['message'] . ' - ' . implode( $attribErrors['errors'] );
                else
                    $errors[] = $attribErrors['message'];
            }
            
            throw new \Exception( join( '; ', $errors ) );
        }
        catch (\Exception $e)
        {
            Log::error( $e );
            throw $e;
        }
    }

    public function createFlowguardTerminal ($terminalId)
    {
        try
        {
            $requestOptions = $this->prepareFlowguardTerminal( $terminalId, false, "createOrUpdate" );
            $response = $this->client->request( 'POST', 'terminals/', $requestOptions );
            $response->getBody()->rewind();
            return json_decode( $response->getBody()->getContents(), true );
        }
        catch (ClientException $exception)
        {
            $response = json_decode( $exception->getResponse()->getBody(), true );
            $errors = [];
            
            foreach ($response as $attribute => $attribErrors)
            {
                if (isset( $attribErrors['errors'] ))
                    $errors[] = $attribErrors['message'] . ' - ' . implode( $attribErrors['errors'] );
                else
                    $errors[] = $attribErrors['message'];
            }
            
            throw new \Exception( join( '; ', $errors ) );
        }
        catch (\Exception $e)
        {
            Log::error( $e );
            throw $e;
        }
    }

    public function updateFlowguardTerminal ($terminalId)
    {
        try
        {
            $requestOptions = $this->prepareFlowguardTerminal( $terminalId, true, "createOrUpdate" );
            $response = $this->client->request( 'PATCH', 'terminals/' . $this->getTerminalTPKId( $terminalId ), $requestOptions ); // FIXME::
                                                                                                                                // UID
            $response->getBody()->rewind();
            return json_decode( $response->getBody()->getContents(), true );
        }
        catch (ClientException $exception)
        {
            $response = json_decode( $exception->getResponse()->getBody(), true );
            $errors = [];
            
            if (count( $response ) > 0)
            {
                foreach ($response as $attribute => $attribErrors)
                {
                    if (isset( $attribErrors['errors'] ))
                        $errors[] = $attribErrors['message'] . ' - ' . implode( $attribErrors['errors'] );
                    else
                        $errors[] = $attribErrors['message'];
                }
                
                throw new \Exception( join( '; ', $errors ) );
            }
            throw new \Exception( $response );
        }
        catch (\Exception $e)
        {
            Log::error( $e );
            throw $e;
        }
    }

    public function replaceIpAddress ($terminalId, $terminalTpkid, $action)
    {
        try
        {
            $requestOptions = $this->prepareFlowguardTerminal( $terminalId, true, $action );
            
            $response = $this->client->request( 'PUT', 'terminals/' . $terminalTpkid . '/ipAddressSet', $requestOptions ); // FIXME::
                                                                                                                           // UID
            $response->getBody()->rewind();
            return json_decode( $response->getBody()->getContents(), true );
        }
        catch (ClientException $exception)
        {
            $response = json_decode( $exception->getResponse()->getBody(), true );
            $errors = [];
            
            foreach ($response as $attribute => $attribErrors)
            {
                if (isset( $attribErrors['errors'] ))
                    $errors[] = $attribErrors['message'] . ' - ' . implode( $attribErrors['errors'] );
                else
                    $errors[] = $attribErrors['message'];
            }
            
            throw new \Exception( join( '; ', $errors ) );
        }
        catch (\Exception $e)
        {
            Log::error( $e );
            throw $e;
        }
    }

    public function trafficRuleset ($terminalId, $terminalTpkid)
    {
        try
        {
            $requestOptions = $this->prepareFlowguardTerminal( $terminalId, true, "trafficRuleset" );
            
            $response = $this->client->request( 'PUT', 'terminals/' . $terminalTpkid . '/trafficRuleSet', $requestOptions ); // FIXME::
                                                                                                                          // UID
            $response->getBody()->rewind();
            return json_decode( $response->getBody()->getContents(), true );
        }
        catch (ClientException $exception)
        {
            $response = json_decode( $exception->getResponse()->getBody(), true );
            $errors = [];
            
            foreach ($response as $attribute => $attribErrors)
            {
                if (isset( $attribErrors['errors'] ))
                    $errors[] = implode( $attribErrors['errors'] ) . ' ' . $attribErrors['message'];
                else
                    $errors[] = $attribErrors['message'];
            }
            
            throw new \Exception( join( '; ', $errors ) );
        }
        catch (\Exception $e)
        {
            Log::error( $e );
            throw $e;
        }
    }

    private function getTerminalTPKId ($terminalId)
    {
        try
        {
            $terminal = Terminal::findOrFail( $terminalId );
            
            if (count( $terminal ) > 0)
            {
                return $terminal->TPK_DID;
            }
        }
        catch (\Exception $e)
        {
            Log::error( $e );
            throw $e;
        }
    }

    private function prepareFlowguardTerminal ($terminalId, $update, $action)
    {
        $terminalDetailsQuery = Terminal::select( 
                DB::raw( 
                        'terminal.TPK_DID as tpkId, flowGuard.Traffic_Reset_Date as trafficResetDate, flowGuard.Threshold_Unit as thresholdUnit, flowGuard.Threshold_Value as thresholdValue, flowGuard.Streaming_Rate as streamingRate, s.Number as ipRange,subscription.`dhcp-range-start-address-ipv4` as start_ip,subscription.`dhcp-range-end-address-ipv4` as end_ip' ) )->join( 
                'system_customer_mapping as scm', 'scm.System_Idx', '=', 'terminal.System_Idx' )
                ->join( 'system_location_mapping as slm', 'slm.System_Idx', '=', 'terminal.System_Idx' )
                ->join( 'location as l', 'l.Idx', '=', 'slm.Location_Idx' )
                ->join( 'terminal_traffic_shaping as flowGuard', 
                    function ($join)
                    {
                        $join->on( 'flowGuard.Terminal_Idx', '=', 'terminal.Idx' )
                            ->on( DB::raw( 'IFNULL(flowGuard.`End_Date`, NOW())' ), '>=', DB::raw( "NOW()" ) );
                    } )
                ->leftjoin( 'service as s', 
                    function ($join)
                    {
                        $join->on( 's.Terminal_Idx', '=', 'terminal.Idx' )
                            ->on( 's.Service', '=', DB::raw( '"IP_RANGE"' ) )
                            ->on( DB::raw( 'IFNULL(s.Deactivation_Date, NOW())' ), '>=', DB::raw( 'now()' ) );
                    } )
                ->leftjoin( 'jx_subscription_raw as subscription', 
                    function ($join)
                    {
                        $join->on( 'subscription.msisdn', '=', 'terminal.TPK_DID' )
                            ->on( 'subscription.Status', '=', DB::raw( '"ACTIVE"' ) )
                            ->on( DB::raw( 'IFNULL(subscription.`disconnected-at`, NOW())' ), '>=', DB::raw( "NOW()" ) );
                    } )
                ->where( 'terminal.Idx', '=', $terminalId )
                ->orderBy( 'flowGuard.Start_Date', 'Desc' );
        
        $terminalDetails = $terminalDetailsQuery->get();
        
        if (count( $terminalDetails ) > 0)
        {
            if ($action == "createOrUpdate")
            {
                $flowGuardRequest = [
                    'trafficResetDate' => $terminalDetails[0]->trafficResetDate
                ];
                
                if ($update == false)
                {
                    $flowGuardRequest['uid'] = $terminalDetails[0]->tpkId;
                }
            }
            elseif ($action == "replaceIp" || $action == "removeIp")
            {
                // $flowGuardRequest["items"]=array($terminalDetails[0]->ipRange);
                
                $ips = array();
                
                if ($action == "replaceIp")
                {
                    
                    for ($ip = ip2long( $terminalDetails[0]->start_ip ); $ip <= ip2long( $terminalDetails[0]->end_ip ); $ip ++)
                    {
                        $ips[] = long2ip( $ip );
                    }
                }
                
                $flowGuardRequest["items"] = $ips;
            }
            elseif ($action == "trafficRuleset")
            {
                $flowGuardRequest["unit"] = $terminalDetails[0]->thresholdUnit;

                /* $usuageDetails = $this->getTerminalUsageDetails( $terminalDetails[0]->tpkId ); */

                /* if(count($usuageDetails)>0)
                {
                    if (count($usuageDetails["periodic"])>0 && count($usuageDetails["periodic"][0]["counters"])>0)
                    {
                        foreach ($usuageDetails["periodic"][0]["counters"] as $usage)
                        {
                            if($usage["app"]=="video")
                            {
                                $minutes_consumed = ($usage["millis"]/60000);

                                if($terminalDetails[0]->thresholdValue <= $minutes_consumed)
                                {
                                    throw new \Exception('Threshold should be greater than consumed minutes');
                                }
                            }
                        }
                    } */

                $env = App::environment();
                $env = (($env == "local") ? "da17udev05.satcom1.org" : "dbman.satcom1.org");
                
                $flowGuardRequest["rules"] = [
                    array(
                        "threshold" => 0,
                        "rateLimit" => (int) $terminalDetails[0]->streamingRate
                    ),
                    array(
                        "threshold" => ((int) (($terminalDetails[0]->thresholdValue * 80) / 100)),
                        "rateLimit" => (int) $terminalDetails[0]->streamingRate,
                        "webhooks" => [
                            $env
                        ]
                    ),
                    array(
                        "threshold" => (int) $terminalDetails[0]->thresholdValue,
                        "rateLimit" => 0
                    )
                ];
            }
            $requestOptions = array_merge_recursive( $this->requestOptions, 
                    [
                        'headers' => [
                            'Content-Type' => 'application/json'
                        ],
                        'body' => json_encode( $flowGuardRequest )
                    ] );
            
            return $requestOptions;
        }
        
        throw new \Exception( 'Terminal id ' . $terminalId . ' data not found' );
    }

    public function getTerminalNotifications ()
    {
        try
        {
            $response = $this->client->request( 'GET', 'notifications/fetch/da17udev05.satcom1.org', $this->requestOptions );
            $response->getBody()->rewind();
            return json_decode( $response->getBody()->getContents(), true );
        }
        catch (ClientException $exception)
        {
            $response = json_decode( $exception->getResponse()->getBody(), true );
            $errors = [];
            
            foreach ($response as $attribute => $attribErrors)
            {
                if (isset( $attribErrors['errors'] ))
                    $errors[] = $attribErrors['message'] . ' - ' . implode( $attribErrors['errors'] );
                else
                    $errors[] = $attribErrors['message'];
            }
            
            throw new \Exception( join( '; ', $errors ) );
        }
        catch (\Exception $e)
        {
            Log::error( $e );
            throw $e;
        }
    }

    public function sendConfirmNotification ($notificationId)
    {
        try
        {
            $requestOptions = array_merge_recursive( $this->requestOptions, 
                    [
                        'headers' => [
                            'Content-Type' => 'application/json'
                        ],
                        'body' => json_encode( array(
                            "notificationIds" => $notificationId
                        ) )
                    ] );
            
            $response = $this->client->request( 'POST', 'notifications/confirm', $requestOptions );
            $response->getBody()->rewind();
            return json_decode( $response->getBody()->getContents(), true );
        }
        catch (ClientException $exception)
        {
            $response = json_decode( $exception->getResponse()->getBody(), true );
            $errors = [];
            
            foreach ($response as $attribute => $attribErrors)
            {
                if (isset( $attribErrors['errors'] ))
                    $errors[] = $attribErrors['message'] . ' - ' . implode( $attribErrors['errors'] );
                else
                    $errors[] = $attribErrors['message'];
            }
            
            throw new \Exception( join( '; ', $errors ) );
        }
        catch (\Exception $e)
        {
            Log::error( $e );
            throw $e;
        }
    }

    public function getTrafficShapingNotifications ()
    {
        try
        {
            $notificationResults = $this->getTerminalNotifications();
            
            if (count( $notificationResults ) > 0)
            {
                foreach ($notificationResults as $terminalNotification)
                {
                    $notificationConfirm = array();
                    
                    $uid = $terminalNotification['message']["terminal"]["uid"];
                    
                    $terminalData = Terminal::where( "TPK_DID", "=", $uid )->whereRaw( 'ifNull(Deactivation_Date,now()) >= now()' )->get();
                    
                    $terminalData = $terminalData->toArray();
                    
                    if (count( $terminalData ) > 0 && count( $terminalData[0]["trafficShaping"] ) > 0)
                    {
                        $thresholdValue = $terminalData[0]["trafficShaping"][0]["Threshold_Value"];
                        $streamingRate = $terminalData[0]["trafficShaping"][0]["Streaming_Rate"];

                        //if ($terminalNotification["message"]["rule"]["threshold"] == (($thresholdValue * 80) / 100))
                        //{
                        $traffic_shaping_notification_event = new TrafficShapingNotificationEvent();
                        
                        $traffic_shaping_notification_event->Terminal_Idx = $terminalData[0]["id"];
                        $traffic_shaping_notification_event->Flowguard_id = $terminalNotification["id"];
                        $traffic_shaping_notification_event->Uid = $uid;
                        $traffic_shaping_notification_event->Json_Body = json_encode( $terminalNotification );
                        $traffic_shaping_notification_event->Status = "OPEN";
                        $traffic_shaping_notification_event->Created_On = \Carbon::now();
                        
                        if ($traffic_shaping_notification_event->save())
                        {
                            $notificationConfirm[] = $terminalNotification["id"];
                        }
                        //}
                    }
                }

                $this->sendConfirmNotification( $notificationConfirm );

              }
        }
        catch (\Exception $e)
        {
            Log::error( $e );
    
            return response( [
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR );
        }
    }
    
    public function sendTrafficShapingNotifications ()
    {
        try
        {
            $Query_Data = DB::select(DB::raw( 'select *,traffic_shapping_notifications.Idx as id from traffic_shapping_notifications inner join  terminal_traffic_shaping ON `terminal_traffic_shaping`.`Terminal_Idx` = `traffic_shapping_notifications`.`Terminal_Idx` where terminal_traffic_shaping.Status="ACTIVE" and Start_Date < NOW() AND IFNULL(End_Date, NOW()) >= NOW() AND traffic_shapping_notifications.Status = "OPEN" ' ));
            
            $Query_Data = array_map(function ($value) {
                return (array)$value;
            }, $Query_Data);
            
            if (count( $Query_Data ) > 0)
            {
                foreach($Query_Data as $data)
                {
                    Mail::send( 'emails.traffic-shaping-notification', [
                        'expiryDetails' => array(
                            $data
                        )
                    ], 
                    function ($message) use ( $data)
                    {
                        
                        $usersToNotify = TrafficShapingNotification::where( 'Terminal_Idx', '=', $data["Terminal_Idx"] )->whereRaw( 'Start_Date < NOW() AND IFNULL(End_Date, NOW()) >= NOW()' )
                            ->get()
                            ->toArray();
                        
                        $message->from( 'noreply@honeywell.com', 'DBMan' )->subject( 
                                'DBMan ' . (App::environment( 'Production' ) ? '' : '(' . strtoupper( App::environment() ) . ')') . ' - Traffic Shaping Notification' );
                        $message->to( array_pluck( $usersToNotify, 'email' ) );
                        $message->priority( 2 );
                    } );
                    
                    $traffic_shaping_notification = TrafficShapingNotificationEvent::where( 'Idx', '=', $data["id"] )->first();
                    
                    $traffic_shaping_notification->Status = 'CLOSE';
                    $traffic_shaping_notification->Compleated_On = \Carbon::now();
                    $traffic_shaping_notification->save();
                }
            }
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

