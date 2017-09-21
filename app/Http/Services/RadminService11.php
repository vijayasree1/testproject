<?php
namespace App\Http\Services;

use App\Http\Models\DBManNotification;
use App\Http\Models\Network;
use App\Http\Models\RadminFirewallTier;
use App\Http\Models\RadminSimUser;
use App\Http\Models\RadminSync;
use App\Http\Models\RadminSyncDetails;
use App\Http\Models\RadminUserGroup;
use App\Http\Models\Service;
use App\Http\Models\Task;
use App\Http\Models\Terminal;
use App\Jobs\GoDirectProvision;
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

class RadminService
{
    use DispatchesJobs;

    protected $client = null;

    protected $requestOptions = [];

    public function __construct()
    {
        $clientConfig = [
            'base_uri' => config( 'dbman.radminBaseUri' )
        ];

        if( config('app.debug') )
        {
            $stack = HandlerStack::create();

            $logger = with(new \Monolog\Logger('api-consumer'))->pushHandler(
                new \Monolog\Handler\StreamHandler(storage_path('/logs/radmin-call-log.log'), Logger::DEBUG, true)
            );

            $stack->push(
                Middleware::log( $logger, new MessageFormatter('{req_headers} {req_body} - {res_headers} {res_body}') )
            );

            $clientConfig['handler'] = $stack;
        }

        $this->client = new Client($clientConfig);

        $this->requestOptions = config('dbman.radminRequestDefaultOptions');

        $this->requestOptions['headers']['Authorization'] = config('dbman.radminAccessToken');
    }

    public function getSimList( $searchOptions = [] )
    {
        $allowedSearchParameters = [ 'imsi', 'iccid', 'msisdn', 'active', 'demo', 'cabinbilling' ];
        $searchParameters = [];

        foreach ($allowedSearchParameters as $parameter)
        {
            if(array_key_exists($parameter, $searchOptions) && !empty($searchOptions[$parameter]))
            {
                $searchParameters[$parameter] = $searchOptions[$parameter];
            }
        }

        $searchString = http_build_query($searchParameters);

        $simListResponse = $this->client->request('GET', 'sims?' . $searchString, $this->requestOptions);

        if( $simListResponse->getStatusCode() == Response::HTTP_OK )
        {
            return json_decode($simListResponse->getBody(), true)['data'];
        }

        throw new \Exception('Error occurred while fetching sim list.');
    }

    public function getSimId($imsi)
    {
        $simDetails = $this->getSimDetails($imsi);
        return $simDetails['data']['id'];
    }
    
    private function getFirewallTierName ($firewallTierId)
    {
        try
        {
            $firewallTierDetails = RadminFirewallTier::findOrFail($firewallTierId);
    
            if(count($firewallTierDetails)>0)
            {
                return $firewallTierDetails->name;
            }
        }
        catch(\Exception $e)
        {
            Log::error($e);
            throw $e;
        }
    }

    public function getSimDetailsById($simId)
    {
        $response = $this->client->request('GET', 'sims/' . $simId, $this->requestOptions);
        return json_decode($response->getBody(), true);
    }

    public function getSimDetails($imsi)
    {
        $simDetailsResponse = $this->client->request('GET', 'sims?imsi=' . $imsi, $this->requestOptions);
        $simList = json_decode($simDetailsResponse->getBody(), true)['data'];

        if( count($simList) > 0 )
        {
            $simId = $simList[0]['id']; //Take the first element from search. Only one element will be there.

            $response = $this->client->request('GET', 'sims/' . $simId, $this->requestOptions);
            return json_decode($response->getBody(), true);
        }

        throw new \Exception("SIM Not found");
    }

    public function listUserGroups()
    {
        try
        {
            $userGroupsResponse = $this->client->request('GET', 'usergroups', $this->requestOptions);

            if( $userGroupsResponse->getStatusCode() == Response::HTTP_OK )
            {
                return json_decode($userGroupsResponse->getBody(), true)['data'];
            }
        }
        catch (\Exception $e)
        {
            throw new \Exception("Error occurred while listing user groups");
        }
    }

    public function listSimUsers($usergroupId)
    {
        try
        {
            $simUsers = $this->client->request( 'GET', 'usergroups/' . $usergroupId . '/available_sim_users', $this->requestOptions);

            if ($simUsers->getStatusCode() == Response::HTTP_OK)
            {
                return json_decode($simUsers->getBody(),true)['data'];
            }

            throw new \Exception($simUsers->getBody()->getContents());
        }
        catch (\Exception $e)
        {
            throw new \Exception("Error occurred while listing sim users");
        }
    }

    public function listAvailableFirewallTiers()
    {
        try
        {
            $userGroupsResponse = $this->client->request('GET', 'firewall_tiers', $this->requestOptions);

            if( $userGroupsResponse->getStatusCode() == Response::HTTP_OK )
            {
                return json_decode($userGroupsResponse->getBody(), true)['data'];
            }
        }
        catch (\Exception $e)
        {
            throw new \Exception("Error occurred while listing firewall tiers");
        }
    }

    public function cabinBillingUsage( $searchParams = [] )
    {
        try
        {
            $allowedSearchParameters = [
                'days' => 'days_ago',
                'class' => 'by_class'
            ];

            $searchParameters = [];

            foreach( $allowedSearchParameters as $parameter => $radminParamName )
            {
                if(array_key_exists($parameter, $searchParams) && !empty($searchParams[$parameter]))
                {
                    $searchParameters[$radminParamName] = $searchParams[$parameter];
                }
            }

            $usageDetails = $this->client->request( 'GET', 'cabinbilling?' . http_build_query($searchParameters),
                $this->requestOptions);

            if ($usageDetails->getStatusCode() == Response::HTTP_OK)
            {
                return json_decode($usageDetails->getBody(),true)['data'];
            }

            throw new \Exception($usageDetails->getBody()->getContents());
        }
        catch (\Exception $e)
        {
            Log::error($e);
            throw new \Exception("Error occurred while listing cabin billing usage");
        }
    }

    public function showSIM($simId)
    {
        try
        {
            $simDetails = $this->client->request( 'GET', 'sims/' . $simId, $this->requestOptions);

            if( $simDetails->getStatusCode() == Response::HTTP_OK )
            {
                return json_decode($simDetails->getBody(),true)['data'];
            }
            throw new \Exception($simDetails->getBody()->getContents());
        }
        catch (\Exception $e)
        {
            throw new \Exception("Error occurred while getting SIM details");
        }
    }

    public function listIPS($queryParams='')
    {
        try
        {
            $ipAddresses = $this->client->request( 'GET', 'ips/?' . $queryParams , $this->requestOptions);

            if ($ipAddresses->getStatusCode() == Response::HTTP_OK)
            {
                return json_decode($ipAddresses->getBody(),true)['data'];
            }
            throw new \Exception($ipAddresses->getBody()->getContents());
        }
        catch (\Exception $e)
        {
            throw new \Exception("Error occurred while getting IP's");
        }
    }

    public function listSIMProvisioning($simId)
    {
        try
        {
            $simProvisionings = $this->client->request( 'GET', 'sims/' . $simId . '/provisionings', $this->requestOptions);

            if ($simProvisionings->getStatusCode() == Response::HTTP_OK)
            {
                return json_decode($simProvisionings->getBody(),true)['data'];
            }
            throw new \Exception($simProvisionings->getBody()->getContents());
        }
        catch (\Exception $e)
        {
            throw new \Exception("Error occurred while getting SIM Provisionings");
        }
    }

    public function listCompanies()
    {
        try
        {
            $companies = $this->client->request( 'GET', 'companies', $this->requestOptions);

            if ($companies->getStatusCode() == Response::HTTP_OK)
            {
                return json_decode($companies->getBody(),true)['data'];
            }
            throw new \Exception($companies->getBody()->getContents());
        }
        catch (\Exception $e)
        {
            throw new \Exception("Error occurred while getting comapnies");
        }
    }

    public function createSim($terminalId)
    {
        try
        {
            $requestOptions = $this->prepareSimInput($terminalId);
            $response = $this->client->request('POST', 'sims', $requestOptions);
            $response->getBody()->rewind();
            return json_decode($response->getBody()->getContents(), true);
        }
        catch (ClientException $exception)
        {
            $response = json_decode( $exception->getResponse()->getBody(), true );
            $errors = [];

            foreach( $response as $attribute => $attribErrors )
            {
                foreach( $attribErrors as $errorDetails )
                {
                    $errors[] = $errorDetails['attribute'] . ' ' . $errorDetails['message'];
                }
            }

            throw new \Exception( join('; ', $errors) );
        }
        catch(\Exception $e)
        {
            Log::error($e);
            throw $e;
        }
    }

    private function prepareSimInput($terminalId)
    {
        $terminalResponse = Terminal::select(DB::raw('terminal.*,scm.Customer_Idx,c.company,cb.status AS CabinBillingStatus, l.Location location,sc.SAP_Customer_Idx as sapCustomerId,s.number as MSISDN'))
            ->join('system_customer_mapping as scm', 'scm.System_Idx', '=', 'terminal.System_Idx')
            ->join('system_location_mapping as slm', 'slm.System_Idx', '=', 'terminal.System_Idx')
            ->join('location as l', 'l.Idx', '=', 'slm.Location_Idx')
            ->join('customer as c', 'c.Idx', '=', 'scm.Customer_Idx')
            ->leftjoin('service as s',function($join){
                $join->on('s.Terminal_Idx','=','terminal.Idx')
                    ->on('s.Service','=',DB::raw('"VOICE"'))
                    ->on(DB::raw('IFNULL(s.Deactivation_Date, NOW())'), '>=', DB::raw('now()'));
            })
            ->leftjoin('cabinbilling as cb', 'terminal.Idx', '=', 'cb.Terminal_Idx')
            ->leftjoin('sap_customer as sc', 'sc.Customer_Idx', '=', 'c.Idx') //FIXME: Should be changed to join in future!
            ->where('terminal.Idx', '=', $terminalId)
            ->whereRaw('(scm.Start_Date < now() AND ( scm.End_Date IS NULL OR scm.End_Date > now() ))')
            ->whereRaw('(slm.Start_Date < now() AND ( slm.End_Date IS NULL OR slm.End_Date > now() ))')
            ->first();

        if(!is_null($terminalResponse))
        {
            $simAttributes = [
                'imsi' => 'IMSI',
                'iccid' => 'ICC_ID',
                'msisdn' => 'MSISDN',
                'tailnumber' => 'location'
            ];

            foreach($simAttributes as $name => $key)
            {
                $attributes[$name] = $terminalResponse[$key];
            }

            $attributes['active'] = true;
            $attributes['company_name'] = $terminalResponse->company;
            $attributes['cabinbilling'] = false;

            if(strcasecmp($terminalResponse->CabinBillingStatus, 'Enabled') === 0) //Case sensitive!
            {
                $attributes['cabinbilling'] = true;
            }

            $attributes['avioip'] = false;
            $attributes['demo'] = false;

            $attributes['sap_id'] = is_null($terminalResponse->sapCustomerId) ? "": $terminalResponse->sapCustomerId;
            $attributes['remarks'] = is_null($terminalResponse->Comments)? "": $terminalResponse->Comments;

            $requestOptions = array_merge_recursive($this->requestOptions, [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'body' => json_encode([
                    'data' => [
                        'attributes' => $attributes
                    ]
                ])
            ]);

            return $requestOptions;
        }
    }

    public function updateSim($simId, $terminalId)
    {
        try
        {
            $requestOptions = $this->prepareSIMInput($terminalId);
            $response = $this->client->request('PUT','sims/' . $simId, $requestOptions);
            $response->getBody()->rewind();
            return json_decode($response->getBody()->getContents(), true);
        }
        catch(\Exception $e)
        {
            Log::error($e);
            throw $e;
        }
    }

    public function createSimProvisioning($terminalId,$simId)
    {
        try
        {
            $requestOptions = $this->prepareSimProvisioningInput($terminalId, $simId);
            $response = $this->client->request('POST', 'sims/' . $simId . '/provisionings', $requestOptions);
            $response->getBody()->rewind();
            return json_decode($response->getBody()->getContents(), true);
        }
        catch(\Exception $e)
        {
            Log::error($e);
            throw $e;
        }
    }

    public function getSimProvisionings($simId)
    {
        $simProvisioningResponse = $this->client->request('GET', 'sims/' . $simId . '/provisionings', $this->requestOptions);
        return json_decode($simProvisioningResponse->getBody(), true);
    }

    public function updateSimProvisioning($terminalId, $simId, $simProvisioningId)
    {
        try
        {
            $requestOptions = $this->prepareSimProvisioningInput($terminalId, $simId, true);
            $response = $this->client->request( 'PUT', 'sims/' . $simId . '/provisionings/' . $simProvisioningId, $requestOptions );
            $response->getBody()->rewind();
            return json_decode($response->getBody()->getContents(), true);
        }
        catch(\Exception $e)
        {
            Log::error($e);
            throw $e;
        }
    }

    public function enableCabinBilling( $locationId, $updatedBy, $category )
    {
        $this->updateCabinBilling($locationId, true, $updatedBy, $category);
    }

    public function disableCabinBilling( $locationId, $updatedBy, $category )
    {
        $this->updateCabinBilling($locationId, false, $updatedBy, $category);
    }
    
    private function updateCabinBilling($locationId, $cabinBilling, $updatedBy, $category)
    {
        try
        {
            
            $terminals = DB::table('terminal')->select(DB::raw('terminal.*, customer.Company'))
                            ->join('system', 'system.Idx', '=', 'terminal.System_Idx')
                            ->join('system_location_mapping as slm', 'slm.System_Idx', '=', 'system.Idx')
                            ->join('system_customer_mapping as scm', 'slm.System_Idx', '=', 'scm.System_Idx')
                            ->join('customer', 'customer.Idx', '=', 'scm.Customer_Idx')
                            ->join('cabinbilling as cb', 'terminal.Idx', '=', 'cb.Terminal_Idx')
                            ->whereRaw('(scm.Start_Date < now() AND ( scm.End_Date IS NULL OR scm.End_Date > now() ))')
                            ->whereRaw('(slm.Start_Date < now() AND ( slm.End_Date IS NULL OR slm.End_Date > now() ))')
                            ->whereRaw('IFNULL(terminal.Deactivation_Date, NOW()) >= NOW()')
                            ->where('slm.Location_Idx', '=', $locationId)
                            ->where('terminal.Category', '=', $category)
                            ->where('terminal.Status', '=', 'ACTIVE')
                            ->get();
    
            $activeGoDirectNetwork = Network::where('name', '=', 'GoDirect Network')
            ->where('Active_YN_Flag', '=', 'Yes')
            ->where('Inmarsat_Preferred_Network_YN_Flag', '=', 'Y')->first();
    
            $isGoDirectNetworkPrimary = false;
    
            if( !is_null($activeGoDirectNetwork) )
            {
                $isGoDirectNetworkPrimary = true;
            }
    
            foreach( $terminals as $terminal )
            {
                Log::info('Updating cabin billing for Location ' . $locationId . ' and terminal ' . $terminal->Idx . ' (IMSI: ' . $terminal->IMSI . ')' );
    
                if($terminal->Category == 'JX')
                {
                    try {
                        $task = new Task([
                            'task' => 'TASK_JX_CB_UPDATE',
                            'data1' => $terminal->Idx,
                            'data2' => $cabinBilling === true ? 'Enable': 'Disable',
                            'data3' => $updatedBy,
                            'createdOn' => \Carbon::now(),
                            'firstValidOn' => \Carbon::now(),
                            'status' => 'STATUS_WAIT'
                        ]);
                        
                        $task->save();
                        
                        DB::table('cabinbilling')
                        ->where('Terminal_Idx', $terminal->Idx)
                        ->update(['Status' => $cabinBilling === true ? 'Enabled': 'Disabled']);
                        
                        $radminTerminalId= $this->getRadminTerminalId($terminal->Idx);
                         
                        $response= $this->listSvnProfile($radminTerminalId);
                        
                        $svnProfileId=$response[0]['id'];
                        
                        $this->updateJxSvnProfile($terminal->Idx,$radminTerminalId,$svnProfileId);
                        
                        $task->Status = 'STATUS_DONE_OK';
                        $task->Finish_Date = \Carbon::now();
                        $task->Message = 'Cabin billing ' . ( $cabinBilling? 'enabled': 'disabled' ) . ' successfully.';
                        $task->save();
                    }
                    catch (\Exception $e)
                    {
                        Log::info('GoDirect network is active and updating Cabin billing status to ' . ($cabinBilling ? 'Error_Enabling': 'Error_Disabling') .
                                ' for terminal ' . $terminal->Idx);
                
                        DB::table('cabinbilling')
                            ->where('Terminal_Idx', $terminal->Idx)
                            ->update(['Status' => $cabinBilling === true ? 'Error_Enabling': 'Error_Disabling']);
                        
                        $task->Status = 'STATUS_DONE_FAIL';
                        $task->Finish_Date = \Carbon::now();
                        $task->Message = $e->getMessage();
                        $task->save();
                    }
                }
                else 
                {
                    $simId = $this->getSimId($terminal->IMSI);
                    
                    $task = new Task([
                        'task' => 'TASK_GODIRECT_CB_UPDATE',
                        'data1' => $terminal->Idx,
                        'data2' => $cabinBilling === true ? 'Enable': 'Disable',
                        'data3' => $updatedBy,
                        'createdOn' => \Carbon::now(),
                        'firstValidOn' => \Carbon::now(),
                        'status' => 'STATUS_WAIT'
                    ]);
                    
                    $task->save();
                    
                    $requestOptions = array_merge_recursive($this->requestOptions, [
                        'headers' => [
                            'Content-Type' => 'application/json'
                        ],
                        'body' => json_encode([
                            'data' => [
                                'attributes' => [
                                    'company_name' => $terminal->Company,
                                    'cabinbilling' => $cabinBilling
                                ]
                            ]
                        ])
                    ]);
        
                    try
                    {
                        $userGroup = RadminUserGroup::findOrFail($terminal->User_Group_Id);
        
                        if( $userGroup->User_Group == 'Satcom1' )
                        {
                            Log::info('CabinBilling: Satcom1 user group is selected for terminal ' . $terminal->Idx . '(IMSI: ' . $terminal->IMSI . ')');
        
                            $gdnIpService = Service::where('Terminal_Idx', '=', $terminal->Idx)
                            ->where('Service', '=', 'GDN_IP')
                            ->whereRaw('IFNULL(Deactivation_Date, NOW()) >= NOW()')->first();
        
                            $ipAddress = $gdnIpService->Number;
        
                            Log::info('CabinBilling: Current IP address: ' . $ipAddress);
        
                            if( $cabinBilling )
                            {
                                $ipAddress = preg_replace("/^37\.60\./", "10.11.", $ipAddress);
                            }
                            else
                            {
                                $ipAddress = preg_replace("/^10\.11\./", "37.60.", $ipAddress);
                            }
        
                            Log::info('CabinBilling: Updating IP address to: ' . $ipAddress);
        
                            $gdnIpService->Number = $ipAddress;
                            $gdnIpService->save();
        
                            $simProvisioningId = 0;
        
                            try
                            {
                                $simProvisioningDetails = $this->listSIMProvisioning($simId);
                                $simProvisioningId = $simProvisioningDetails[0]['id'];
                            }
                            catch(\Exception $e)
                            {
                                Log::error($e);
                            }
        
                            if( $simProvisioningId != 0 )
                            {
                                $this->deleteSimProvisioning($simId, $simProvisioningId);
                            }
        
                            $this->createSimProvisioning($terminal->Idx, $simId);
                        }
        
                        $this->client->request('PUT', 'sims/' . $simId, $requestOptions);
        
                        if( $isGoDirectNetworkPrimary )
                        {
                            Log::info('GoDirect network is active and updating Cabin billing status to ' . ($cabinBilling ? 'Enabled': 'Disabled') .
                                    ' for terminal ' . $terminal->Idx);
        
                            DB::table('cabinbilling')
                            ->where('Terminal_Idx', $terminal->Idx)
                            ->update(['Status' => $cabinBilling === true ? 'Enabled': 'Disabled']);
                        }
        
                        $task->Status = 'STATUS_DONE_OK';
                        $task->Finish_Date = \Carbon::now();
                        $task->Message = 'Cabin billing ' . ( $cabinBilling? 'enabled': 'disabled' ) . ' successfully.';
                        $task->save();
                    }
                    catch (\Exception $e)
                    {
                        if( $isGoDirectNetworkPrimary )
                        {
                            Log::info('GoDirect network is active and updating Cabin billing status to ' . ($cabinBilling ? 'Error_Enabling': 'Error_Disabling') .
                                    ' for terminal ' . $terminal->Idx);
        
                            DB::table('cabinbilling')
                            ->where('Terminal_Idx', $terminal->Idx)
                            ->update(['Status' => $cabinBilling === true ? 'Error_Enabling': 'Error_Disabling']);
                        }
        
                        $task->Status = 'STATUS_DONE_FAIL';
                        $task->Finish_Date = \Carbon::now();
                        $task->Message = $e->getMessage();
                        $task->save();
                    }
                }
            }
    
            return true;
        }
        catch (\Exception $e)
        {
            Log::error('Error occurred while ' . ( $cabinBilling? 'enabling': 'disabling' ) . ' cabin billing.');
            Log::error($e);
            throw new \Exception('Error occurred while ' . ( $cabinBilling? 'enabling': 'disabling' ) . ' cabin billing.');
        }
    }
    
    private function prepareSimProvisioningInput($terminalId, $simId, $update = false)
    {
        $terminalDetails = DB::table('terminal')
            ->select(
                'terminal.Idx as TerminalIdx',
                'terminal.IMSI as IMSI',
                'terminal.Sim_User_Idx as simUserId',
                's.Number as ipAddress',
                'terminal_network_mapping.PDP_Allowed_YN_Flag as standardip',
                'terminal.User_Group_Id AS User_Group',
                'terminal.QoService_Idx as highstreaming',
                'terminal.Firewall_Filter_Idx AS Firewall_Tier',
                'sm.Service_Key',
                'sm.Service_Name'
            )
            ->leftJoin('terminal_network_mapping', function($join){
                $join->on('terminal_network_mapping.Terminal_Idx', '=', 'terminal.Idx')
                    ->on('terminal_network_mapping.Network_Idx', '=', DB::raw("'2'"));
            })
            ->leftJoin('services_master as sm', 'sm.Service_Idx', '=', 'terminal.QoService_Idx')
            ->leftJoin('service as s', function($join){
                $join->on('s.Terminal_Idx', '=', 'terminal.Idx')
                    ->on('s.Service', '=', DB::raw("'GDN_IP'"))
                    ->on('s.Activation_Date', '<=', DB::raw("NOW()"))
                    ->on(DB::raw('IFNULL(s.Deactivation_Date, DATE_ADD(NOW(), INTERVAL 5 SECOND))'), '>', DB::raw("NOW()"));
            })
            ->join('network as n',function($join){
                $join->on('n.Network_Idx','=','terminal_network_mapping.Network_Idx');
            })
            ->where('n.Network_Name', '=', 'GoDirect Network')
            ->where('terminal.Idx', '=', $terminalId);

        $terminalDetails = $terminalDetails->get();

        $streamingInfo = $this->getStreamingInfoByServiceKey($terminalDetails[0]->Service_Key, $terminalDetails[0]->Service_Name);

        if(count($terminalDetails) > 0)
        {
            $attributes = [
                'fixedipv4' => $terminalDetails[0]->ipAddress,
                'standardip' => (($terminalDetails[0]->standardip === 'Yes') ? true: false),
                'streaming_option' => $streamingInfo['streaming_option'],
                'firewall_tier_name' => $this->getFirewallTierName($terminalDetails[0]->Firewall_Tier), //$terminalDetails[0]->Firewall_Tier
                'streaminghdrhalfasymmetric' => $streamingInfo['streaminghdrhalfasymmetric'],
                'streaminghdrhalfsymmetric' => $streamingInfo['streaminghdrhalfsymmetric'],
                'streaminghdrfullasymmetric' => $streamingInfo['streaminghdrfullasymmetric'],
                'streaminghdrfullsymmetric' => $streamingInfo['streaminghdrfullsymmetric']
            ];

            $radminRequest['data'] = [
                'type' => 'provisionings',
                'attributes' => $attributes
            ];

            if( $update !== true )
            {
                $radminRequest['data']['relationships'] = [
                    'sim' => [
                        'data' => [
                            'id' => $simId,
                            'type' => 'sims'
                        ]
                    ],
                    'usergroup' => [
                        'data' => [
                            'id' => $terminalDetails[0]->User_Group,
                            'type' => 'usergroups'
                        ]
                    ],
                    'sim_user' => [
                        'data' => [
                            'id' => $terminalDetails[0]->simUserId,
                            'type' => 'sim-users'
                        ]
                    ]
                ];
            }

            $requestOptions = array_merge_recursive($this->requestOptions, [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'body' => json_encode($radminRequest)
            ]);

            return $requestOptions;
        }

        throw new \Exception('Terminal id '.$terminalId.' data not found');
    }

    public function getStreamingInfoByServiceKey($serviceKey, $serviceName = null)
    {
        $streamingInfo = [
            'streaming_option' => "",
            'streaminghdrhalfasymmetric' => false,
            'streaminghdrhalfsymmetric' => false,
            'streaminghdrfullasymmetric' => false,
            'streaminghdrfullsymmetric' => false
        ];

        if (!is_null($serviceKey))
        {
            $streamingInfo['streaming_option'] = (string) $serviceKey;
        }
        else
        {
            $streamingInfo['streaming_option'] = 'Remove all options';
        }

        if (strcasecmp($serviceName, 'HDR Full Channel Symmetric') === 0)
        {
            $streamingInfo['streaminghdrfullsymmetric'] = true;
            $streamingInfo['streaminghdrfullasymmetric'] = true;
            $streamingInfo['streaminghdrhalfsymmetric'] = true;
            $streamingInfo['streaminghdrhalfasymmetric'] = true;
        }
        elseif (strcasecmp($serviceName, 'HDR 64k/Full Channel') === 0 || strcasecmp($serviceName, 'HDR Full Channel/64k') === 0)
        {
            $streamingInfo['streaminghdrfullasymmetric'] = true;
            $streamingInfo['streaminghdrhalfsymmetric'] = true;
            $streamingInfo['streaminghdrhalfasymmetric'] = true;
        }
        elseif (strcasecmp($serviceName, 'HDR Half Channel/64k') === 0 || strcasecmp($serviceName, 'HDR 64k/Half Channel') === 0)
        {
            $streamingInfo['streaminghdrhalfasymmetric'] = true;
        }
        elseif (strcasecmp($serviceName, 'HDR Half Channel Symmetric') === 0)
        {
            $streamingInfo['streaminghdrhalfsymmetric'] = true;
            $streamingInfo['streaminghdrhalfasymmetric'] = true;
        }

        return $streamingInfo;
    }

    public function deleteSIMProvisioning($simId,$simProvisioningId)
    {
        try
        {
            $deleteSIMProvisioningResponse = $this->client->request('DELETE', 'sims/'.$simId.'/provisionings/'.$simProvisioningId, $this->requestOptions);
            $deleteSIMProvisioningResponse->getBody()->rewind();
            return json_decode($deleteSIMProvisioningResponse->getBody()->getContents(), true);
        }
        catch(\Exception $e)
        {
            Log::error($e);
            throw $e;
        }
    }

    public function disableSIM($simId)
    {
        try
        {
            $disableSIMResponse = $this->client->request('POST', 'sims/'.$simId.'/disable', $this->requestOptions);
            $disableSIMResponse->getBody()->rewind();
            return json_decode($disableSIMResponse->getBody()->getContents(), true);
        }
        catch(\Exception $e)
        {
            Log::error($e);
            throw $e;
        }
    }

    public function sync( $syncInitiatedBy = 'UNKNOWN', $imsiList = [] )
    {
        if( empty($syncInitiatedBy) )
        {
            throw new \Exception('Unable to identify the user who has triggered sync.');
        }

        set_time_limit(600);

        $radminSync = new RadminSync;
        $radminSync->Created_By = $syncInitiatedBy;
        $radminSync->Created_At = \Carbon::now();
        $radminSync->save();

        $radminSyncId = $radminSync->Idx;

        $radminService = new RadminService();

        $syncStartTime = date("Y-m-d H:i:s");

        $radminSimsList = array_column($radminService->getSimList(['active' => 'true']), 'attributes', 'id');

        $simsList = [];

        foreach($radminSimsList as $radminSimId => $radminSimAttributes)
        {
            $simsList[$radminSimAttributes['imsi']] = $radminSimId;
        }

        $radminTerminalsQuery = DB::table('radmin_terminal_services');

        if( is_array( $imsiList ) && count($imsiList) > 0 )
        {
            $radminTerminalsQuery->whereIn('IMSI', $imsiList);
        }
        else if( !empty($imsiList) )
        {
            $radminTerminalsQuery->where('IMSI', '=', $imsiList);
        }

        $radminTerminals = $radminTerminalsQuery->get();

        $errors = [];

        $simsInDBMan = [];

        foreach( $radminTerminals as $terminal )
        {
            if( !array_key_exists( $terminal->IMSI, $simsList ) )
            {
                Log::info('SIM ' . $terminal->IMSI . ' details not found or error occurred while getting SIM details.');

                $task = new Task([
                    'task' => 'TASK_GODIRECT_ADD',
                    'data1' => $terminal->Terminal_Idx,
                    'data2' => $terminal->GoDirectIP,
                    'data3' => 'Radmin Sync',
                    'createdOn' => \Carbon::now(),
                    'firstValidOn' => \Carbon::now(),
                    'status' => 'STATUS_WAIT'
                ]);

                $task->save();

                $errors[$terminal->Terminal_Idx] = [
                    'imsi' => $terminal->IMSI,
                    'errors' => [
                        'IMSI ' . $terminal->IMSI . ' not present in Radmin.'
                    ],
                    'taskId' => $task->Idx,
                    'action' => 'CREATE'
                ];

                $this->dispatch((new GoDirectProvision($task))->delay(2));

                continue;
            }

            $simsInDBMan[] = $terminal->IMSI;

            $simDetails = $radminService->getSimDetailsById($simsList[$terminal->IMSI]);

            $simErrors = [];

            if( !$simDetails['data']['attributes']['active'] )
            {
                array_push($simErrors, 'SIM is marked as inactive in Radmin.');
            }

            if( ( $simDetails['data']['attributes']['cabinbilling'] ? 'true': 'false' ) != $terminal->CabinBilling)
            {
                array_push($simErrors, 'Cabin billing status is not in sync.  Radmin: ' .
                    ( $simDetails['data']['attributes']['cabinbilling'] ? 'true': 'false' ) . "; DBMan: $terminal->CabinBilling");
            }

            if($simDetails['data']['attributes']['iccid'] != $terminal->ICC_ID)
            {
                array_push($simErrors, "ICC ID Not matching. Radmin: {$simDetails['data']['attributes']['iccid']}; DBMan: $terminal->ICC_ID");
            }

            if($simDetails['data']['attributes']['msisdn'] != $terminal->MSISDN)
            {
                array_push($simErrors, "MSISDN Not matching. Radmin: {$simDetails['data']['attributes']['msisdn']}; DBMan: $terminal->MSISDN");
            }

            if($simDetails['data']['attributes']['company_name'] != $terminal->Company_Name)
            {
                array_push($simErrors, "Company name didn't match. Radmin: {$simDetails['data']['attributes']['company_name']}; DBMan: $terminal->Company_Name");
            }

            if($simDetails['data']['attributes']['tailnumber'] != $terminal->Tail_Number)
            {
                array_push($simErrors, "Tail number didn't match. Radmin: {$simDetails['data']['attributes']['tailnumber']}; DBMan: $terminal->Tail_Number");
            }

            if($simDetails['data']['attributes']['sap_id'] != $terminal->SAP_Customer_Idx)
            {
                array_push($simErrors, "SAP ID is not matching. Radmin: {$simDetails['data']['attributes']['sap_id']}; DBMan: $terminal->SAP_Customer_Idx");
            }

            if($simDetails['data']['attributes']['remarks'] != $terminal->Remarks)
            {
                array_push($simErrors, "Remarks are not matching. Radmin: {$simDetails['data']['attributes']['remarks']}; DBMan: $terminal->Remarks");
            }

            $response = $radminService->getSimProvisionings($simDetails['data']['id']);

            $provisioningDetails = $response['data'];

            if( count($provisioningDetails) > 0 )
            {
                $provisioningDetails = $provisioningDetails[0]; //Take the first element from search.

                if($provisioningDetails['relationships']['usergroup']['data']['id'] != $terminal->User_Group_Id)
                {
                    array_push($simErrors, 'User group is not matched. Radmin: ' .
                        $provisioningDetails['relationships']['usergroup']['data']['id'] .
                        '; DBMan: ' . $terminal->User_Group_Id);
                }

                if($provisioningDetails['relationships']['sim_user']['data']['id'] != $terminal->Sim_User_Idx)
                {
                    array_push($simErrors, 'SIM User is not matched. Radmin: ' .
                        $provisioningDetails['relationships']['sim_user']['data']['id'] .
                        '; DBMan: ' . $terminal->Sim_User_Idx);
                }

                if( $provisioningDetails['attributes']['fixedipv4'] != $terminal->GoDirectIP )
                {
                    array_push($simErrors, 'IP addresses did not match. Radmin: ' .
                        $provisioningDetails['attributes']['fixedipv4'] .
                        '; DBMan: ' . $terminal->GoDirectIP);
                }

                if( $provisioningDetails['attributes']['streaming_option'] != $terminal->MaxAllowedStreaming )
                {
                    array_push($simErrors, 'Streaming level not matched. Radmin: ' .
                        $provisioningDetails['attributes']['streaming_option'] .
                        '; DBMan: ' . $terminal->MaxAllowedStreaming);
                }

                if( $provisioningDetails['attributes']['standardip'] != ( $terminal->PDP == 'Yes' ? true: false ) )
                {
                    array_push($simErrors, 'PDP setting is not matched. Radmin: ' .
                        ( $provisioningDetails['attributes']['standardip'] ? 'true': 'false' ) .
                        '; DBMan: ' . ( $terminal->PDP == 'Yes' ? 'true': 'false' ));
                }

                if($provisioningDetails['attributes']['firewall_tier_name'] != $this->getFirewallTierName($terminal->Firewall_Filter_Idx))
                {
                    array_push($simErrors, "GoDirect Filter setting is not matching. Radmin: {$provisioningDetails['attributes']['firewall_tier_name']}; DBMan: $this->getFirewallTierName($terminal->Firewall_Filter_Idx)"); // $terminal->Firewall_Filter_Idx
                }

                $dbmanStreamingSettings = $radminService->getStreamingInfoByServiceKey($terminal->MaxAllowedStreaming, $terminal->QoS);

                $streamingProperties = [
                    'streaminghdrhalfasymmetric',
                    'streaminghdrhalfsymmetric',
                    'streaminghdrfullasymmetric',
                    'streaminghdrfullsymmetric'
                ];

                foreach($streamingProperties as $streamingName)
                {
                    if( $dbmanStreamingSettings[$streamingName] != $provisioningDetails['attributes'][$streamingName] )
                    {
                        array_push($simErrors, $streamingName . ' not matched. Radmin: ' .
                            ( $provisioningDetails['attributes'][$streamingName] === true? 'true': 'false')  . '; ' .
                            'DBMan: ' . ($dbmanStreamingSettings[$streamingName] === true? 'true': 'false') );
                    }
                }
            }
            else
            {
                Log::info('No provisionings available for IMSI ' . $simDetails['data']['attributes']['imsi'] .
                    '(Radmin SIM ID: ' . $simDetails['data']['id'] . '; DBMan terminal ID: ' . $terminal->Terminal_Idx . ')');

                $task = new Task([
                    'task' => 'TASK_GODIRECT_UPDATE',
                    'data1' => $terminal->Terminal_Idx,
                    'data2' => $terminal->GoDirectIP,
                    'data3' => 'Radmin Sync',
                    'createdOn' => \Carbon::now(),
                    'firstValidOn' => \Carbon::now(),
                    'status' => 'STATUS_WAIT'
                ]);

                $task->save();

                $errors[$terminal->Terminal_Idx] = [
                    'imsi' => $terminal->IMSI,
                    'errors' => [
                        'No provisionings available for IMSI ' . $simDetails['data']['attributes']['imsi'] .
                        '(Radmin SIM ID: ' . $simDetails['data']['id'] . '; DBMan terminal ID: ' . $terminal->Terminal_Idx . ')'
                    ],
                    'taskId' => $task->Idx,
                    'radminSimId' => $simDetails['data']['id'],
                    'action' => 'CREATE PROVISIONING'
                ];

                $this->dispatch((new GoDirectProvision($task))->delay(2));
            }

            if( count($simErrors) > 0 )
            {
                $task = new Task([
                    'task' => 'TASK_GODIRECT_UPDATE',
                    'data1' => $terminal->Terminal_Idx,
                    'data2' => $terminal->GoDirectIP,
                    'data3' => 'Radmin Sync',
                    'createdOn' => \Carbon::now(),
                    'firstValidOn' => \Carbon::now(),
                    'status' => 'STATUS_WAIT'
                ]);

                $task->save();

                $errors[$terminal->Terminal_Idx] = [
                    'imsi' => $terminal->IMSI,
                    'errors' => $simErrors,
                    'taskId' => $task->Idx,
                    'action' => 'UPDATE',
                    'radminSimId' => $simDetails['data']['id']
                ];

                $this->dispatch((new GoDirectProvision($task))->delay(2));
            }
        }

        $activeRadminOnlySims = array_diff(array_keys($simsList), $simsInDBMan);

        $inactiveRadminSims = array_column($radminService->getSimList(['active' => 'false']), 'attributes', 'id');

        $inactiveSimsList = [];

        foreach($inactiveRadminSims as $radminSimId => $radminSimAttributes)
        {
            $inactiveSimsList[$radminSimAttributes['imsi']] = $radminSimId;
        }

        $inactiveRadminOnlySims = array_diff(array_keys($inactiveSimsList), $simsInDBMan);

        $syncEndTime = date("Y-m-d H:i:s");

        $usersToNotify = DBManNotification::with(['user'])
            ->where('Notification_Type', '=', 'RADMIN_SYNC')
            ->whereRaw('Start_Date < NOW() AND IFNULL(End_Date, NOW()) >= NOW()')
            ->get()->toArray();

        $sbbTaskIds = array_column($errors, 'taskId');

        $jxSyncErrors = $this->syncJxTerminals($syncInitiatedBy, $imsiList);

        $jxTaskIds = array_column($jxSyncErrors['errors'], 'taskId');

        $taskIds = array_merge($sbbTaskIds, $jxTaskIds);

        for( $i = 0; $i < 15; $i++ )
        {
            if( Task::whereIn('Idx', $taskIds)->where('Status', '=', 'STATUS_WAIT')->count() > 0 )
            {
                sleep(2);
            }
        }

        $tasksStatus = [
            'passed' => 0,
            'failed' => 0,
            'waiting' => 0,
        ];

        foreach($errors as $terminalId => $errorDetails)
        {
            $task = Task::find($errorDetails['taskId']);
            $errors[$terminalId]['taskStatus'] = $task->Status;
            $errors[$terminalId]['taskStatusMessage'] = $task->Message;

            if( $task->Status == 'STATUS_DONE_OK' )
            {
                $tasksStatus['passed'] = $tasksStatus['passed'] + 1;
            }
            else if( $task->Status == 'STATUS_DONE_FAIL' )
            {
                $tasksStatus['failed'] = $tasksStatus['failed'] + 1;
            }
            else
            {
                $tasksStatus['waiting'] = $tasksStatus['waiting'] + 1;
            }

            foreach( $errorDetails['errors'] as $error )
            {
                $radminSyncError = new RadminSyncDetails;
                $radminSyncError->Radmin_Sync_Idx = $radminSyncId;

                if( !empty($errorDetails['radminSimId']) )
                {
                    $radminSyncError->Radmin_Sim_Id = $errorDetails['radminSimId'];
                }

                $radminSyncError->Terminal_Idx = $terminalId;
                $radminSyncError->IMSI = $errorDetails['imsi'];
                $radminSyncError->Task_Idx = $errorDetails['taskId'];
                $radminSyncError->Error_Message = $error;
                $radminSyncError->Category = 'DATA_MISMATCH';
                $radminSyncError->Action = $errorDetails['action'];
                $radminSyncError->save();
            }
        }

        //FIXME: Merge JX and SBB logic!

        foreach($jxSyncErrors['errors'] as $terminalId => $errorDetails)
        {
            $task = Task::find($errorDetails['taskId']);
            $jxSyncErrors['errors'][$terminalId]['taskStatus'] = $task->Status;
            $jxSyncErrors['errors'][$terminalId]['taskStatusMessage'] = $task->Message;

            if( $task->Status == 'STATUS_DONE_OK' )
            {
                $tasksStatus['passed'] = $tasksStatus['passed'] + 1;
            }
            else if( $task->Status == 'STATUS_DONE_FAIL' )
            {
                $tasksStatus['failed'] = $tasksStatus['failed'] + 1;
            }
            else
            {
                $tasksStatus['waiting'] = $tasksStatus['waiting'] + 1;
            }

            foreach( $errorDetails['errors'] as $error )
            {
                $radminSyncError = new RadminSyncDetails;
                $radminSyncError->Radmin_Sync_Idx = $radminSyncId;

                if( !empty($errorDetails['radminSimId']) )
                {
                    $radminSyncError->Radmin_Sim_Id = $errorDetails['radminSimId'];
                }

                $radminSyncError->Terminal_Idx = $terminalId;
                $radminSyncError->IMSI = $errorDetails['imsi'];
                $radminSyncError->Task_Idx = $errorDetails['taskId'];
                $radminSyncError->Error_Message = $error;
                $radminSyncError->Category = 'DATA_MISMATCH';
                $radminSyncError->Action = $errorDetails['action'];
                $radminSyncError->save();
            }
        }

        foreach( $activeRadminOnlySims as $imsi )
        {
            $radminSyncError = new RadminSyncDetails;

            $radminSyncError->Radmin_Sync_Idx = $radminSyncId;
            $radminSyncError->IMSI = $imsi;
            $radminSyncError->Radmin_Sim_Id = $simsList[$imsi];
            $radminSyncError->Category = 'RADMIN_ONLY_ACTIVE';
            $radminSyncError->Error_Message = 'IMSI is active in Radmin but not available in DBMan.';
            $radminSyncError->Action = 'IGNORE';
            $radminSyncError->save();
        }

        foreach( $inactiveRadminOnlySims as $imsi )
        {
            $radminSyncError = new RadminSyncDetails;

            $radminSyncError->Radmin_Sync_Idx = $radminSyncId;
            $radminSyncError->IMSI = $imsi;
            $radminSyncError->Radmin_Sim_Id = $inactiveSimsList[$imsi];
            $radminSyncError->Category = 'RADMIN_ONLY_INACTIVE';
            $radminSyncError->Error_Message = 'IMSI is inactive in Radmin and not available in DBMan.';
            $radminSyncError->Action = 'IGNORE';
            $radminSyncError->save();
        }

        $radminIgnoredSims = DB::table('radmin_ignored_imsi')->get();

        foreach( $radminIgnoredSims as $ignoredSim)
        {
            $radminSyncError = new RadminSyncDetails;

            $radminSyncError->Radmin_Sync_Idx = $radminSyncId;
            $radminSyncError->IMSI = $ignoredSim->IMSI;
            $radminSyncError->Category = 'IGNORED';
            $radminSyncError->Error_Message = $ignoredSim->Comments;
            $radminSyncError->Action = 'IGNORE';
            $radminSyncError->save();
        }

        if( !empty( $imsiList ) )
        {
            $dataToInsert = [];

            foreach( $imsiList as $imsi )
            {
                $dataToInsert[] = [
                    'Radmin_Sync_Idx' => $radminSyncId,
                    'IMSI' => $imsi
                ];
            }

            DB::table('radmin_sync_imsi')->insert($dataToInsert);
        }

        $radminSync->Completed_At = \Carbon::now();
        $radminSync->save();

        Log::error($errors);

        Mail::send(
            'emails.radmin-sync-notification', [
            'errors' => $errors,
            'jxErrors' => $jxSyncErrors['errors'],
            'taskStatusCount' => $tasksStatus,
            'imsiList' => $imsiList,
            'radminSims' => $activeRadminOnlySims,
            'radminIgnoredSims' => $radminIgnoredSims,
            'inactiveRadminOnlySims' => $inactiveRadminOnlySims,
            'dbmanSimCount' => count($radminTerminals),
            'radminSimCount' => count($simsList),
            'syncStartTime' => $syncStartTime,
            'syncEndTime' => $syncEndTime
        ], function ($message) use($usersToNotify, $errors, $activeRadminOnlySims) {
            $message->from('noreply@honeywell.com', 'DBMan')
                    ->subject('DBMan ' . (App::environment('production') ? '':
                                '(' . strtoupper( App::environment() ) . ')' )  .
                                ' - Radmin Sync Report');
            $message->to(array_pluck($usersToNotify, 'email'));

            if( count($errors) > 0 || ( !empty($imsiList) && count($activeRadminOnlySims) > 0 ) )
            {
                $message->priority(2);
            }
        });
    }

    public function syncJxTerminals( $syncInitiatedBy = 'UNKNOWN', $tpkIds = [] )
    {
        if( empty($syncInitiatedBy) )
        {
            throw new \Exception('Unable to identify the user who has triggered sync.');
        }

        set_time_limit(600);

        $syncStartTime = date("Y-m-d H:i:s");

        $radminTerminalsList = array_column($this->listJxTerminals(), 'attributes', 'id');

        $radminJxTerminals = [];

        foreach($radminTerminalsList as $radminSimId => $radminSimAttributes)
        {
            if( !empty($radminSimAttributes['terminal_provisioning_key']) ) 
            {
                $radminJxTerminals[$radminSimAttributes['terminal_provisioning_key']] = $radminSimId;
            }
        }

        $jxTerminalsQuery = DB::table('radmin_jx_terminals');

        if( is_array( $tpkIds ) && count($tpkIds) > 0 )
        {
            $jxTerminalsQuery->whereIn('TPK_DID', $tpkIds);
        }
        else if( !empty($tpkIds) )
        {
            $jxTerminalsQuery->where('TPK_DID', '=', $tpkIds);
        }

        $jxTerminals = $jxTerminalsQuery->get();

        $errors = [];

        $jxTerminalsInDBMan = [];

        foreach( $jxTerminals as $jxTerminal )
        {
            if( !array_key_exists( $jxTerminal->TPK_DID, $radminJxTerminals ) )
            {
                Log::info('Terminal (TPK DID: ' . $jxTerminal->TPK_DID . ') details not found or error occurred while getting JX terminal details.');

                $task = new Task([
                    'task' => 'TASK_GODIRECT_JX_ADD',
                    'data1' => $jxTerminal->Terminal_Id,
                    'data2' => $jxTerminal->Package_ID,
                    'data3' => 'Radmin Sync',
                    'createdOn' => \Carbon::now(),
                    'firstValidOn' => \Carbon::now(),
                    'status' => 'STATUS_WAIT'
                ]);

                $task->save();

                $errors[$jxTerminal->Terminal_Idx] = [
                    'imsi' => $jxTerminal->TPK_DID,
                    'errors' => [
                        'IMSI ' . $jxTerminal->TPK_DID . ' not present in Radmin.'
                    ],
                    'taskId' => $task->Idx,
                    'action' => 'CREATE'
                ];

                $this->dispatch((new GoDirectProvision($task))->delay(2));

                continue;
            }

            $jxTerminalsInDBMan[] = $jxTerminal->TPK_DID;

            $jxTerminalDetails = $this->getJxTerminalDetails( $jxTerminal->TPK_DID );

            $terminalErrors = [];

            if( !$jxTerminalDetails['attributes']['active'] )
            {
                array_push($terminalErrors, 'Terminal is marked as inactive in Radmin.');
            }

            if( $jxTerminalDetails['attributes']['tailnumber'] != $jxTerminal->Tail_Number )
            {
                array_push($terminalErrors, "Tail number didn't match. Radmin: {$jxTerminalDetails['attributes']['tailnumber']}; DBMan: $jxTerminal->Tail_Number");
            }

            if( $jxTerminalDetails['attributes']['acm'] != $jxTerminal->ACM_Number )
            {
                array_push($terminalErrors, "ACM number didn't match. Radmin: {$jxTerminalDetails['attributes']['acm']}; DBMan: $jxTerminal->ACM_Number");
            }

            if( $jxTerminalDetails['attributes']['sap_id'] != $jxTerminal->SAP_Customer_Idx )
            {
                array_push($terminalErrors, "SAP ID is not matching. Radmin: {$jxTerminalDetails['attributes']['sap_id']}; DBMan: $jxTerminal->SAP_Customer_Idx");
            }

            if( $jxTerminalDetails['attributes']['name'] != ( $jxTerminal->Tail_Number . " " . $jxTerminal->ACM_Number )  )
            {
                array_push($terminalErrors, "SAP ID is not matching. Radmin: {$jxTerminalDetails['attributes']['sap_id']}; DBMan: $jxTerminal->SAP_Customer_Idx");
            }

            $response = $this->listSvnProfile($jxTerminalDetails['id']);

            if( count($response) > 0 )
            {
                $svnProfile = $response[0];

                /* svn_id, network, firewall_tier_name, cabinbilling, xiplink */
                if($svnProfile['attributes']['svn_id'] != $jxTerminal->SVN_ID)
                {
                    array_push($terminalErrors, 'SVN ID is not matched. Radmin: ' .
                        $svnProfile['attributes']['svn_id'] .
                        '; DBMan: ' . $jxTerminal->SVN_ID);
                }

                $iplong = ip2long($jxTerminal->Starting_IP);
                $masklong = ip2long($jxTerminal->Server_Netmask);
                $base = ip2long('255.255.255.255');
        
                $ipmaskand = $iplong & $masklong;
                $network_address = long2ip($ipmaskand );
                $cidr = 32-log(($masklong ^ $base)+1,2);
        
                $network_address_range = $network_address . '/' .$cidr;

                if($svnProfile['attributes']['network'] != $network_address_range)
                {
                    array_push($terminalErrors, 'IP Range is not matched. Radmin: ' .
                        $svnProfile['attributes']['network'] .
                        '; DBMan: ' . $network_address_range);
                }

                if($svnProfile['attributes']['firewall_tier_name'] != $this->getFirewallTierName($jxTerminal->Firewall_Filter_Idx)) // $jxTerminal->Firewall_Filter_Idx
                {
                    array_push($terminalErrors, 'User group is not matched. Radmin: ' .
                        $svnProfile['attributes']['firewall_tier_name'] .
                        '; DBMan: ' . $this->getFirewallTierName($jxTerminal->Firewall_Filter_Idx));
                }

                if( ( $svnProfile['attributes']['cabinbilling'] ? 'true': 'false' ) != $jxTerminal->CabinBilling)
                {
                    array_push($terminalErrors, 'Cabin Billing is not matched. Radmin: ' .
                        ( $svnProfile['attributes']['cabinbilling'] ? 'true': 'false' ) .
                        '; DBMan: ' . $jxTerminal->CabinBilling);
                }

                /*if($svnProfile['attributes']['xiplink'] != $terminal->Sim_User_Idx)
                {
                    array_push($terminalErrors, 'SIM User is not matched. Radmin: ' .
                        $svnProfile['attributes']['xiplink'] .
                        '; DBMan: ' . $terminal->Sim_User_Idx);
                }

                if($svnProfile['relationships']['subscription_service_plan_profile']['data']['id'] != $jxTerminal->Template_ID)
                {
                    array_push($terminalErrors, 'SSPP is not matched. Radmin: ' .
                        $svnProfile['relationships']['subscription_service_plan_profile']['data']['id'] .
                        '; DBMan: ' . $jxTerminal->Template_ID);
                }*/
            }
            else
            {
                Log::info('No SVN Profiles exists for TPK DID ' . $jxTerminalDetails['attributes']['terminal_provisioning_key'] .
                    '(Radmin Terminal ID: ' . $jxTerminalDetails['id'] . '; DBMan terminal ID: ' . $jxTerminal->Terminal_Idx . ')');

                $task = new Task([
                    'task' => 'TASK_GODIRECT_JX_UPDATE',
                    'data1' => $jxTerminal->Terminal_Idx,
                    'data2' => '',
                    'data3' => 'Radmin Sync',
                    'createdOn' => \Carbon::now(),
                    'firstValidOn' => \Carbon::now(),
                    'status' => 'STATUS_WAIT'
                ]);

                $task->save();

                $errors[$jxTerminal->Terminal_Idx] = [
                    'imsi' => $jxTerminal->TPK_DID,
                    'errors' => [
                        'No SVN Profile for TPK DID ' . $jxTerminalDetails['attributes']['terminal_provisioning_key'] .
                        '(Radmin terminal ID: ' . $jxTerminalDetails['id'] . '; DBMan terminal ID: ' . $jxTerminal->Terminal_Idx . ')'
                    ],
                    'taskId' => $task->Idx,
                    'radminSimId' => $jxTerminalDetails['id'],
                    'action' => 'CREATE SVN PROFILE'
                ];

                $this->dispatch((new GoDirectProvision($task))->delay(2));
            }

            if( count($terminalErrors) > 0 )
            {
                $task = new Task([
                    'task' => 'TASK_GODIRECT_JX_UPDATE',
                    'data1' => $jxTerminal->Terminal_Idx,
                    'data2' => '',
                    'data3' => 'Radmin Sync',
                    'createdOn' => \Carbon::now(),
                    'firstValidOn' => \Carbon::now(),
                    'status' => 'STATUS_WAIT'
                ]);

                $task->save();

                $errors[$jxTerminal->Terminal_Idx] = [
                    'imsi' => $jxTerminal->TPK_DID,
                    'errors' => $terminalErrors,
                    'taskId' => $task->Idx,
                    'action' => 'UPDATE',
                    'radminSimId' => $jxTerminalDetails['id']
                ];

                $this->dispatch((new GoDirectProvision($task))->delay(2));
            }
            else
            {
                Log::info('JX Terminal ' . $jxTerminalDetails['attributes']['terminal_provisioning_key'] .
                    ' doesn\'t have any errors.');
            }
        }

        $activeRadminJxTerminals = [];

        foreach( $radminTerminalsList as $radminJxTerminalId => $radminJxTerminal )
        {
            if( $radminJxTerminal['active'] === true )
            {
                $activeRadminJxTerminals[$radminJxTerminal['terminal_provisioning_key']] = $radminJxTerminalId;
            }
        }

        $activeRadminOnlyJxTerminals = array_diff(array_keys($activeRadminJxTerminals), $jxTerminalsInDBMan);

        $inactiveRadminTerminals = [];

        foreach( $radminTerminalsList as $radminJxTerminalId => $radminJxTerminal )
        {
            if( $radminJxTerminal['active'] === false )
            {
                $inactiveRadminTerminals[$radminJxTerminal['terminal_provisioning_key']] = $radminJxTerminalId;
            }
        }

        $inactiveRadminOnlyTerminals = array_diff(array_keys($inactiveRadminTerminals ), $jxTerminalsInDBMan);

        return [
            'errors' => $errors,
            'activeRadminOnlyTerminals' =>  $activeRadminOnlyJxTerminals,
            'inactiveRadminOnlyTerminals' => $inactiveRadminOnlyTerminals
        ];
    }

    public function syncMasterData()
    {
        try
        {
            $errors = [];

            try
            {
                $this->syncUserGroups();
            }
            catch(\Exception $e)
            {
                $errors[] = $e->getMessage();
            }

            try
            {
                $this->syncFirewallTiers();
            }
            catch(\Exception $e)
            {
                $errors[] = $e->getMessage();
            }

            if( count($errors) > 0 )
            {
                throw new \Exception(join('; ', $errors));
            }
        }
        catch (\Exception $e)
        {
            Log::error( 'Error occurred while performing Radmin - DBMan master data sync.' );
            Log::error($e);

            $usersToNotify = DBManNotification::with(['user'])
                ->where('Notification_Type', '=', 'RADMIN_MASTERDATA_SYNC')
                ->whereRaw('Start_Date < NOW() AND IFNULL(End_Date, NOW()) >= NOW()')
                ->get()->toArray();

            Mail::send(
                'emails.radmin-masterdata-sync-notification', [
                'exception' => $e->getMessage()
            ], function ($message) use($usersToNotify) {
                $message->from('noreply@honeywell.com', 'DBMan')
                        ->subject('DBMan ' . (App::environment('production') ? '':
                                    '(' . strtoupper( App::environment() ) . ')' )  .
                                    ' - Radmin Master Data Sync Report');
                $message->to(array_pluck($usersToNotify, 'email'));
                $message->priority(2);
            });
        }
    }

    private function syncUserGroups()
    {
        $usergroups = $this->listUserGroups();

        $radminUserGroups = [];

        foreach( $usergroups as $usergroup )
        {
            $radminUserGroups[] = [
                'id' => $usergroup['id'],
                'name' => $usergroup['attributes']['name'],
                'active' => $usergroup['attributes']['active']
            ];
        }

        $dbmanUserGroups = RadminUserGroup::all()->toArray();

        $deletedUserGroups = array_diff(array_column($dbmanUserGroups, 'name', 'id'), array_column($radminUserGroups, 'name', 'id'));

        foreach( $radminUserGroups as $radminUserGroup )
        {
            $userGroup = RadminUserGroup::find($radminUserGroup['id']);

            if( $userGroup == null )
            {
                $userGroup = new RadminUserGroup();
                $userGroup->User_Group_Idx = $radminUserGroup['id'];
            }

            $userGroup->User_Group =  $radminUserGroup['name'];
            $userGroup->Updated_On = \Carbon::now();
            $userGroup->Last_Updated_By = 'SYNC';

            $userGroup->save();

            $this->syncSimUsers( $radminUserGroup['id'] );
        }

        if( count($deletedUserGroups) > 0 )
        {
            Log::error('Following User groups are present in DBMan but missing in Radmin: ');
            Log::error($deletedUserGroups);
            throw new \Exception('Following User groups are present in DBMan but missing in Radmin: ' .
                '\'' . join('\', \'', array_values($deletedUserGroups)) . '\'');
        }
    }

    private function syncSimUsers( $userGroupId )
    {
        $simUsers = $this->listSimUsers($userGroupId);

        $radminSimUsers = [];

        foreach( $simUsers as $simUser )
        {
            $radminSimUsers[] = [
                'id' => $simUser['id'],
                'name' => $simUser['attributes']['name'],
                'password' => $simUser['attributes']['password']
            ];
        }

        $dbmanSimUsers = RadminSimUser::where('userGroupId', '=', $userGroupId )->get()->toArray();

        $deletedSimUser = array_diff(array_column($dbmanSimUsers, 'name', 'id'), array_column($radminSimUsers, 'name', 'id'));

        foreach( $radminSimUsers as $radminSimUser )
        {
            $simUser = RadminSimUser::find($radminSimUser['id']);

            if( $simUser == null )
            {
                $simUser = new RadminSimUser();
                $simUser->SIM_User_Idx = $radminSimUser['id'];
            }

            $simUser->User_Group_Idx = $userGroupId;
            $simUser->SIM_User =  $radminSimUser['name'];
            $simUser->SIM_Password = $radminSimUser['password'];
            $simUser->Updated_On = \Carbon::now();
            $simUser->Last_Updated_By = 'SYNC';

            $simUser->save();
        }

        if( count($deletedSimUser) > 0 )
        {
            Log::error('Following SIM Users are present in DBMan but missing in Radmin: ');
            Log::error($deletedSimUser);
            throw new \Exception('Following SIM Users are present in DBMan but missing in Radmin: ' .
                '\'' . join('\', \'', array_values($deletedSimUser)) . '\'');
        }
    }

    private function syncFirewallTiers()
    {
        $firewallTiers = $this->listAvailableFirewallTiers();

        $radminFirewallTiers = [];

        foreach( $firewallTiers as $firewallTier )
        {
            $radminFirewallTiers[] = [
                'id' => $firewallTier['id'],
                'name' => $firewallTier['attributes']['name'],
                'description' => $firewallTier['attributes']['description']
            ];
        }

        $dbmanFirewallTiers = RadminFirewallTier::all()->toArray();

        $deletedFirewallTiers = array_diff(array_column($dbmanFirewallTiers, 'name', 'id'), array_column($radminFirewallTiers, 'name', 'id'));

        foreach( $radminFirewallTiers as $radminFirewallTier )
        {
            $simUser = RadminFirewallTier::find($radminFirewallTier['id']);

            if( $simUser == null )
            {
                $simUser = new RadminFirewallTier();
                $simUser->Firewall_Filter_Idx = $radminFirewallTier['id'];
            }

            $simUser->Firewall_Filter_Name = $radminFirewallTier['name'];
            $simUser->Firewall_Description = $radminFirewallTier['description'];
            $simUser->Updated_On = \Carbon::now();
            $simUser->Last_Updated_By = 'SYNC';

            $simUser->save();
        }

        if( count($deletedFirewallTiers) > 0 )
        {
            Log::error('Following Firewall Tiers are present in DBMan but missing in Radmin: ');
            Log::error($deletedFirewallTiers);
            throw new \Exception('Following Firewall Tiers are present in DBMan but missing in Radmin: ' .
                '\''. join( '\', \'', array_values( $deletedFirewallTiers ) ) . '\'' );
        }
    }

    public function listJxTerminals()
    {
        try
        {
            $jx_terminals = $this->client->request( 'GET', 'jx_terminals', $this->requestOptions );

            if ($jx_terminals->getStatusCode() == Response::HTTP_OK)
            {
                return json_decode($jx_terminals->getBody(),true)['data'];
            }

            throw new \Exception($jx_terminals->getBody()->getContents());
        }
        catch (\Exception $e)
        {
            throw new \Exception("Error occurred while listing JX Terminals");
        }
    }

    public function getJxTerminalDetails( $tpkId )
    {
        $terminals=$this->listJxTerminals();

        if(count($terminals) > 0)
        {
            foreach ($terminals as $terminal)
            {
                if( $terminal['attributes']['terminal_provisioning_key'] == $tpkId )
                {
                    return $terminal;
                }
            }
        }

        return null;
    }

    public function getRadminTerminalId($terminalId)
    {
        $terminalData= Terminal::where('Idx','=',$terminalId)->first();

        $tpkId = $terminalData->TPK_DID;

        $jxTerminal = $this->getJxTerminalDetails($tpkId);

        if( $jxTerminal != null )
        {
            return $jxTerminal['id'];
        }

        return 0;
    }

    public function listSvns()
    {
        try
        {
            $svns = $this->client->request( 'GET', 'svns', $this->requestOptions);

            if ($svns->getStatusCode() == Response::HTTP_OK)
            {
                return json_decode($svns->getBody(),true)['data'];
            }

            throw new \Exception($svns->getBody()->getContents());
        }
        catch (\Exception $e)
        {
            throw new \Exception("Error occurred while listing SVN's");
        }
    }

    public function getRadminSvnId($svnId)
    {
        $svns=$this->listSvns();

        if(count($svns)>0)
        {
            foreach( $svns as $svn )
            {
                if( $svn['attributes']['name'] == $svnId )
                {
                    return $svn['id'];
                }
            }
        }
    }
    
    public function listSvnProfile($radminTerminalId)
    {
        try
        {
            $svnProfile = $this->client->request( 'GET', 'jx_terminals/'.$radminTerminalId.'/svn_profiles', $this->requestOptions);
    
            if ($svnProfile->getStatusCode() == Response::HTTP_OK)
            {
                return json_decode($svnProfile->getBody(),true)['data'];
            }
    
            throw new \Exception($svnProfile->getBody()->getContents());
        }
        catch (\Exception $e)
        {
            throw new \Exception("Error occurred while listing SVN Profile's");
        }
    }
    
    public function listSspps()
    {
        try
        {
            $sspp = $this->client->request( 'GET', 'subscription_service_plan_profiles', $this->requestOptions);
    
            if ($sspp->getStatusCode() == Response::HTTP_OK)
            {
                return json_decode($sspp->getBody(),true)['data'];
            }
    
            throw new \Exception($sspp->getBody()->getContents());
        }
        catch (\Exception $e)
        {
            throw new \Exception("Error occurred while listing SSPP's");
        }
    }
    
    public function getRadminSsppId($name)
    {
        $sspps=$this->listSspps();
    
        if(count($sspps)>0)
        {
            foreach ($sspps as $sspp)
            {
                if($sspp['attributes']['name']==$name)
                    return $sspp['id'];
            }
        }
    }
    
    public function createJxSim($terminalId)
    {
        try
        {
            $requestOptions = $this->prepareJxSimInput($terminalId,false);
            $response = $this->client->request('POST', 'jx_terminals', $requestOptions);
            $response->getBody()->rewind();
            return json_decode($response->getBody()->getContents(), true);
        }
        catch (ClientException $exception)
        {
            $response = json_decode( $exception->getResponse()->getBody(), true );
            $errors = [];
    
            foreach( $response as $attribute => $attribErrors )
            {
                foreach( $attribErrors as $errorDetails )
                {
                    $errors[] = $errorDetails['attribute'] . ' ' . $errorDetails['message'];
                }
            }
    
            throw new \Exception( join('; ', $errors) );
        }
        catch(\Exception $e)
        {
            Log::error($e);
            throw $e;
        } 
    }
    
    public function updateJxSim($terminalId,$radminTerminalId)
    {
    
        try
        {
            //$radminTerminalId= $this->getRadminTerminalId($terminalId);
            
            $requestOptions = $this->prepareJxSimInput($terminalId,true);
            $response = $this->client->request('PUT', 'jx_terminals/'.$radminTerminalId, $requestOptions);
            $response->getBody()->rewind();
            return json_decode($response->getBody()->getContents(), true);
        }
        catch (ClientException $exception)
        {
            $response = json_decode( $exception->getResponse()->getBody(), true );
            $errors = [];
    
            foreach( $response as $attribute => $attribErrors )
            {
                foreach( $attribErrors as $errorDetails )
                {
                    $errors[] = $errorDetails['attribute'] . ' ' . $errorDetails['message'];
                }
            }
    
            throw new \Exception( join('; ', $errors) );
        }
        catch(\Exception $e)
        {
            Log::error($e);
            throw $e;
        }
    }

    private function prepareJxSimInput($terminalId,$update)
    {
        $terminalResponse = Terminal::select(DB::raw('terminal.*, l.Location location,sc.SAP_Customer_Idx as sapCustomerId,subscription.`acm-number` as ACM_No'))
        ->join('system_customer_mapping as scm', 'scm.System_Idx', '=', 'terminal.System_Idx')
        ->join('system_location_mapping as slm', 'slm.System_Idx', '=', 'terminal.System_Idx')
        ->join('location as l', 'l.Idx', '=', 'slm.Location_Idx')
        ->join('customer as c', 'c.Idx', '=', 'scm.Customer_Idx')
        ->leftjoin('jx_subscription_raw as subscription',function($join){
            $join->on('subscription.msisdn','=','terminal.TPK_DID')
            ->on('subscription.Status','=',DB::raw('"ACTIVE"'))
            ->on(DB::raw('IFNULL(subscription.`disconnected-at`, NOW())'), '>=', DB::raw("NOW()"));
         })
        ->leftjoin('sap_customer as sc', 'sc.Customer_Idx', '=', 'c.Idx') //FIXME: Should be changed to join in future!
        ->where('terminal.Idx', '=', $terminalId)
        ->whereRaw('(scm.Start_Date < now() AND ( scm.End_Date IS NULL OR scm.End_Date > now() ))')
        ->whereRaw('(slm.Start_Date < now() AND ( slm.End_Date IS NULL OR slm.End_Date > now() ))')
        ->first();

        if(!is_null($terminalResponse))
        {
            $simAttributes = [
                'terminal_provisioning_key' => 'TPK_DID',
                'acm' => 'ACM_No',
                'sap_id' => 'sapCustomerId',
                'tailnumber' => 'location'
            ];

            foreach($simAttributes as $name => $key)
            {
                $attributes[$name] = $terminalResponse->$key;
            }

            $attributes['name'] = $terminalResponse->location." ".$terminalResponse->ACM_No;

            if($update==true)
            {
                $attributes['active'] = ($terminalResponse->Status=="ACTIVE"?true:false);
            }

            $requestOptions = array_merge_recursive($this->requestOptions, [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'body' => json_encode([
                    'data' => [
                        'attributes' => $attributes
                    ]
                ])
            ]);

            return $requestOptions;
        }

        throw new \Exception('Terminal id '.$terminalId.' data not found');
    }

    public function createJxSvnProfile ($terminalId, $radminTerminalId)
    {
        try
        {
            $requestOptions = $this->prepareJxSvnProfileInput( $terminalId, $radminTerminalId, false );
            
            $response = $this->client->request( 'POST', 'jx_terminals/' . $radminTerminalId . '/svn_profiles', $requestOptions );
            $response->getBody()->rewind();
            return json_decode( $response->getBody()->getContents(), true ); 
        }
        catch (ClientException $exception)
        {
            $response = json_decode( $exception->getResponse()->getBody(), true );
            $errors = [];
        
            foreach( $response as $attribute => $attribErrors )
            {
                foreach( $attribErrors as $errorDetails )
                {
                    $errors[] = $errorDetails['attribute'] . ' ' . $errorDetails['message'];
                }
            }
        
            throw new \Exception( join('; ', $errors) );
        }
        catch(\Exception $e)
        {
            Log::error($e);
            throw $e;
        }
    }
    
    public function updateJxSvnProfile ($terminalId,$radminTerminalId,$svnProfileId)
    {
         try
         { 

             $requestOptions = $this->prepareJxSvnProfileInput( $terminalId, $radminTerminalId, true );
             
             $response = $this->client->request('PUT', 'jx_terminals/' . $radminTerminalId . '/svn_profiles/'. $svnProfileId, $requestOptions );
             $response->getBody()->rewind();
             return json_decode( $response->getBody()->getContents(), true );
         }
         catch (ClientException $exception)
         {
             $response = json_decode( $exception->getResponse()->getBody(), true );
             $errors = [];
         
             foreach( $response as $attribute => $attribErrors )
             {
                 foreach( $attribErrors as $errorDetails )
                 {
                     $errors[] = $errorDetails['attribute'] . ' ' . $errorDetails['message'];
                 }
             }
         
             throw new \Exception( join('; ', $errors) );
         }
         catch(\Exception $e)
         {
             Log::error($e);
             throw $e;
         }
    }

    private function prepareJxSvnProfileInput ($terminalId, $radminTerminalId, $update)
    {
        $terminalDetailsQuery = Terminal::select( DB::raw( 'terminal.Xiplink as Xiplink, l.Location location,terminal.Firewall_Filter_Idx as Firewall_Tier,cb.status AS CabinBillingStatus,subscription.`svn-id` as SVN,sv.Number as Network,subscription.`acm-number` as ACM_No,subscription.`friendly-name` as ssppName' ) )
                                    ->join('system_customer_mapping as scm', 'scm.System_Idx', '=', 'terminal.System_Idx')
                                    ->join('system_location_mapping as slm', 'slm.System_Idx', '=', 'terminal.System_Idx')
                                    ->join('location as l', 'l.Idx', '=', 'slm.Location_Idx')
                                    ->leftjoin( 'cabinbilling as cb', 'terminal.Idx', '=', 'cb.Terminal_Idx' )
                                    ->leftjoin('jx_subscription_raw as subscription',function($join){
                                        $join->on('subscription.msisdn','=','terminal.TPK_DID')
                                        ->on('subscription.Status','=',DB::raw('"ACTIVE"'))
                                        ->on(DB::raw('IFNULL(subscription.`disconnected-at`, NOW())'), '>=', DB::raw("NOW()"));
                                    })
                                    ->leftjoin('service as sv',function($join){
                                        $join->on('sv.Terminal_Idx','=','terminal.Idx')
                                        ->on('sv.Service','=',DB::raw('"IP_RANGE"'))
                                        ->on(DB::raw('IFNULL(sv.Deactivation_Date, NOW())'), '>=', DB::raw('now()'));
                                    })
                                    ->where('terminal.Idx','=',$terminalId );
        
        $terminalDetails = $terminalDetailsQuery->get();
        
        if (count( $terminalDetails ) > 0)
        {
            $attributes = [
                'jx_terminal_id' => $radminTerminalId,
                'svn_id' => $this->getRadminSvnId($terminalDetails[0]->SVN), // 3 FIXME: SVN ID from radmin // $this->getRadminTerminalId($terminalDetails[0]->SVN);
                'network' =>$terminalDetails[0]->Network, //FIXME: ($terminalDetails[0]->Network),
                'firewall_tier_name' => $this->getFirewallTierName($terminalDetails[0]->Firewall_Tier), //$terminalDetails[0]->Firewall_Tier
                'cabinbilling' => ($terminalDetails[0]->CabinBillingStatus=='Enabled'?true:false),
                'xiplink' => ($terminalDetails[0]->Xiplink=='true'?true:false) //$terminalDetails[0]->Xiplink //false
            ];
            
            if($update==false)
            {
                $ssppName=$terminalDetails[0]->ssppName;
                $attributes['subscription_service_plan_profile_id']=$this->getRadminSsppId($ssppName); // 150001 FIXME: Radmin SSPP // $this->getRadminSsppId($terminalDetails[0]->SVN);
            }
            
            $radminRequest['data'] = [
                'type' => 'svn_profiles',
                'attributes' => $attributes
            ];
            
            $requestOptions = array_merge_recursive($this->requestOptions, [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'body' => json_encode($radminRequest)
            ]);
    
            return $requestOptions;
        }

        throw new \Exception('Terminal id '.$terminalId.' data not found');
    }
    
    public function deleteJxSvnProfile($radminTerminalId,$svnProfileId)
    {
        try
        {

            $deleteSIMProvisioningResponse = $this->client->request('DELETE', 'jx_terminals/' . $radminTerminalId . '/svn_profiles/'. $svnProfileId, $this->requestOptions );
             
            $deleteSIMProvisioningResponse->getBody()->rewind();
            return json_decode($deleteSIMProvisioningResponse->getBody()->getContents(), true);
        }
        catch (ClientException $exception)
        {
            $response = json_decode( $exception->getResponse()->getBody(), true );
            $errors = [];
             
            foreach( $response as $attribute => $attribErrors )
            {
                foreach( $attribErrors as $errorDetails )
                {
                    $errors[] = $errorDetails['attribute'] . ' ' . $errorDetails['message'];
                }
            }
             
            throw new \Exception( join('; ', $errors) );
        }
        catch(\Exception $e)
        {
            Log::error($e);
            throw $e;
        }
    }
    
    public function disableJxSim($radminTerminalId)
    {
        try
        {
            $disableSIMResponse = $this->client->request('POST', 'jx_terminals/'.$radminTerminalId.'/disable', $this->requestOptions);
            $disableSIMResponse->getBody()->rewind();
            return json_decode($disableSIMResponse->getBody()->getContents(), true);
        }
        catch (ClientException $exception)
        {
            $response = json_decode( $exception->getResponse()->getBody(), true );
            $errors = [];
             
            foreach( $response as $attribute => $attribErrors )
            {
                foreach( $attribErrors as $errorDetails )
                {
                    $errors[] = $errorDetails['attribute'] . ' ' . $errorDetails['message'];
                }
            }
             
            throw new \Exception( join('; ', $errors) );
        }
        catch(\Exception $e)
        {
            Log::error($e);
            throw $e;
        }
    }
    
}
