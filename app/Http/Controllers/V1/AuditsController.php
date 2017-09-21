<?php
namespace App\Http\Controllers\V1;

use App\Jobs\GoDirectProvision;
use App\Http\Services\MobilewareService;
use App\Jobs\JxSubscriptionDetailsInfo;
use App\Jobs\JxSubscriptionData;
use App\Http\Models\JxSubscription;
use App\Http\Models\JxPortSwitches;
use App\Http\Models\LocationSuperTableMapping;
use App\Http\Models\OndTollFreeMapping;
use App\Http\Models\Task;
use App\Http\Models\Terminal;
use App\Http\Models\Service;
use App\Jobs\GoDirectDeleteProvision;
use App\Jobs\LocationSubscriptionMissing;
use App\Http\Services\RadminService;
use App\Http\Services\FlowGuardService;
use App\Http\Services\DBManService;
use App\Http\Services\JxSubscriptionService;
use App\Http\Controllers\Controller;
use App\Http\Models\Auditing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Response;
use Validator;
use App;

class AuditsController extends Controller
{
    /**
     * @SWG\Get(
     *     path="/audits",
     *     summary="/audits resource",
     *     tags={"audits"},
     *     description="This resource is dedicated to querying data around Audits.",
     *     operationId="getAudits",
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
     *     @SWG\Response(
     *         response=200,
     *         description="Success",
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Invalid tag value",
     *     ),
     *     @SWG\Response(
     *         response="401",
     *         description="Unauthorized access",
     *     )
     *
     * ),
     */

    public function getAudits(Request $request)
    {
        
        
            $this->dispatch( new LocationSubscriptionMissing() );
            
        exit();

        $flowGuardService= new FlowGuardService();
        $result=$flowGuardService->getTrafficShapingNotifications();
        print_r($result);
        exit();
        
        $flowGuardService= new FlowGuardService();
        $result=$flowGuardService->replaceIpAddress('2299','CQAIHFEVFLEEW===');
        exit();
        $trafficTerminal = 0;
        
        try
        {
            $trafficTerminal = $flowGuardService->getTrafficTerminalDetails("CUAAEZFNDING===");
        }
        catch(\Exception $e)
        {
            Log::error($e);
        }
        if($trafficTerminal==NULL)
        {
            echo "11"; exit();
            $flowGuardService->createFlowguardTerminal( 2408 );
            $flowGuardService->replaceIpAddress( 2408 , "CUAAEZFNDING===" );
            $flowGuardService->trafficRuleset( 2408 , "CUAAEZFNDING===");
        }
        else
        {
            echo "22"; exit();
            $flowGuardService->updateFlowguardTerminal( 2408 );
            $flowGuardService->replaceIpAddress( 2408 , "CUAAEZFNDING===" );
            $flowGuardService->trafficRuleset( 2408 , "$terminalTpkid" );
        }
        exit();

        /* $jxSubscriptionService = new JxSubscriptionService("");
        exit();
        $radminService = new RadminService;
        echo $v=$radminService->getRadminSsppId("50020 - SATCOM1_BAS-5_Test Package");

        exit();
        $packageId=12629;
        $terminalId=2530;
        $radminService = new RadminService;
        new JxSubscriptionService($packageId);

        $jxSubscriptionData=JxSubscription::where('package-id','=',$packageId)->get()->toArray();
        
        if(count($jxSubscriptionData)>0)
        {
            $terminal = Terminal::findOrFail($terminalId);
            $terminal->Activation_Date = date('Y-m-d H:i:s',strtotime($jxSubscriptionData[0]['activated-at']));
        
            $terminal->save();
        
            $attributes = [
                'dhcp-range-start-address-ipv4' => 'IP_RANGE'
            ];
        
            $iplong = ip2long($jxSubscriptionData[0]['dhcp-range-start-address-ipv4']);
            $masklong = ip2long($jxSubscriptionData[0]['dhcp-server-netmask-v4']);
            $base = ip2long('255.255.255.255');
        
            $ipmaskand = $iplong & $masklong;
            $network_address = long2ip($ipmaskand );
            $cidr = 32-log(($masklong ^ $base)+1,2);
        
            $network_address_range = $network_address . '/' .$cidr;
        
            foreach( $attributes as $mobilwareAttrName => $serviceName )
            {
                if(isset($jxSubscriptionData[0][$mobilwareAttrName] ))
                {
                    $service = new Service();
                    $service->service = $serviceName;
                    $service->Terminal_Idx = $terminalId;
                    $service->number = ($serviceName=="IP_RANGE"?$network_address_range:$jxSubscriptionData[0][$mobilwareAttrName]);
                    $service->Data1 = $jxSubscriptionData[0]['dhcp-server-netmask-v4'];
                    $service->activationDate = $jxSubscriptionData[0]['activated-at'];
        
                    $service->save();
                }
            }
        
            $response=$radminService->createJxSim($terminalId);
        
            $radminTerminalId=$response["data"]["id"];
        
        } */
        
        /* $dbManService = new DBManService();
        $dbManService->deactivateTerminal(2525, "VIJJI","DUMMY"); */
        
        //$radminService->createJxSvnProfile(2521, 22);
       // $radminService = new RadminService();
        //$radminService->createJxSim(2521);
        //$radminService->updateJxSvnProfile(2522,23,350029);
        //$radminService->createJxSvnProfile(2521, 22); 
        
        
        /* $radminService = new RadminService();
         $response=$radminService->disableJxSim(150018);
         print_r($response);
         exit(); */
        /* $packageId=13433;
        $terminalId=2522;
        new JxSubscriptionService($packageId);
        	
        $jxSubscriptionData=JxSubscription::where('package-id','=',$packageId)->get()->toArray();
        
        if(count($jxSubscriptionData)>0)
        {
            $terminal = Terminal::findOrFail($terminalId);
            $terminal->Activation_Date = date('Y-m-d H:i:s',strtotime($jxSubscriptionData[0]['activated-at']));
        
            $terminal->save();
        
            $attributes = [
                'dhcp-range-start-address-ipv4' => 'IP_RANGE'
            ];
        
            $iplong = ip2long($jxSubscriptionData[0]['dhcp-range-start-address-ipv4']);
            $masklong = ip2long($jxSubscriptionData[0]['dhcp-server-netmask-v4']);
            $base = ip2long('255.255.255.255');
        
            $ipmaskand = $iplong & $masklong;
            $network_address = long2ip($ipmaskand );
            $cidr = 32-log(($masklong ^ $base)+1,2);
        
            $network_address_range = $network_address . '/' .$cidr;
        
            foreach( $attributes as $mobilwareAttrName => $serviceName )
            {
                if(isset($jxSubscriptionData[0][$mobilwareAttrName] ))
                {
                    $service = new Service();
                    $service->service = $serviceName;
                    $service->Terminal_Idx = $terminalId;
                    $service->number = ($serviceName=="IP_RANGE"?$network_address_range:$jxSubscriptionData[0][$mobilwareAttrName]);
                    $service->Data1 = $jxSubscriptionData[0]['dhcp-server-netmask-v4'];
                    $service->activationDate = $jxSubscriptionData[0]['activated-at'];
        
                    $service->save();
                }
            }
        } */
         /* $task = new Task([
         'task' => 'TASK_GODIRECT_JX_ADD',
         'data1' => 2521,
         'createdOn' => \Carbon::now(),
         'firstValidOn' => \Carbon::now(),
         'status' => 'STATUS_NEW'
         ]);

        $task->save();
        
        $goDirectJob = (new GoDirectProvision( $task ))->delay( 2 );
        
        if (! is_null( $goDirectJob ))
        {
            $this->dispatch( $goDirectJob );
        } */
        
/* $packagId="13301";

new JxSubscriptionService("");
        exit();  */

        //$radminService = new RadminService();
        //$response=$radminService->updateJxSvnProfile (2474,150018,350019);
        //$response=$radminService->updateJxSvnProfile(2299);
        //$response=$radminService->createJxSvnProfile(2299,9);
        /*  $response=new JxSubscriptionDetailsInfo(0);
         print_r($response); */


        /* $mobilewareService = new MobilewareService;

        $jxSubscritions=JxSubscription::where('id','!=','0')->get()->toArray();
         
        foreach ($jxSubscritions as $jxSubscrition)
        {
        $subscriptionPortDetails = $mobilewareService->subscriptionDetails($jxSubscrition['package-id']);

        if(count($subscriptionPortDetails)>0)
        {
        $metricNames = ['id','package-id', 'port-number','auto-negotiation','eth-mode','eth-speed','tag-enable','svn-id'];
        $uniqueColumns = ['id', 'package-id'];

        $portAttributes=array();
        $portValues=array();

        if(isset($subscriptionPortDetails["portswitchs"]))
        {
        foreach ($subscriptionPortDetails["portswitchs"] as $portDetails)
        {
        $portAttributes["package-id"]=$subscriptionPortDetails["package-id"];
        $portAttributes["id"]=$portDetails["id"];

        foreach($portDetails as $metrics => $metricValues)
        {
        if(in_array( $metrics, $metricNames))
        {
        $portValues[$metrics] = $metricValues;
        }
        }

        $portValues["created-at"]=date('Y-m-d H:i:s',strtotime($portDetails['created-at']));
        $portValues["package-id"]=$subscriptionPortDetails["package-id"];

        JxPortSwitches::updateOrCreate($portAttributes,$portValues);

        $JxSubscriptionData=JxSubscription::where('package-id','=',$subscriptionPortDetails['package-id'])->first();

        $var1="dhcp-range-start-address-ipv4";
        $var2="dhcp-range-end-address-ipv4";
        $var3="dhcp-server-netmask-v4";
        $var4="dhcp-primary-dns-v4";
        $var5="dhcp-secondary-dns-v4";
        $var6="dhcp-subnet-v4";
        $var7="svn-id";
        $var8="acm-number";

        if(!is_null($JxSubscriptionData))
        {
        $JxSubscriptionData->$var1 = $subscriptionPortDetails[$var1];
        $JxSubscriptionData->$var2 = $subscriptionPortDetails[$var2];
        $JxSubscriptionData->$var3 = $subscriptionPortDetails[$var3];
        $JxSubscriptionData->$var4 = $subscriptionPortDetails[$var4];
        $JxSubscriptionData->$var5 = $subscriptionPortDetails[$var5];
        $JxSubscriptionData->$var6 = $subscriptionPortDetails[$var6];
        $JxSubscriptionData->$var7 = $subscriptionPortDetails[$var7];
        $JxSubscriptionData->$var8 = $subscriptionPortDetails[$var8];

        $JxSubscriptionData->save();
        }
        }
        }
        else print_r($subscriptionPortDetails);
        }
        } */
         
    }

    /**
     * @SWG\Get(
     *     path="/audits/{transactionId}",
     *     summary="/audits with Transaction Id resource",
     *     tags={"audits"},
     *     description="This resource is dedicated to querying data around Audit Id.",
     *     operationId="getAuditDetails",
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
     *         name="transactionId",
     *         in="path",
     *         description="Filter Audits by Transaction Id",
     *         required=true,
     *         type="string",
     *         @SWG\Items(type="string"),
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Success",
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Invalid tag value",
     *     ),
     *     @SWG\Response(
     *         response="401",
     *         description="Unauthorized access",
     *     )
     *
     * ),
     */

    public function getAuditDetails($transactionId, Request $request)
    {
        $audits = Auditing::where('uniqu', '=', $transactionId)->orderBy('Idx','DESC');

        if( $audits->count() < 1 ) {
            return response('Audits not found', 200);
        }

        return $audits->paginate(intval(Input::input('page_size', 50))); //->toSql();
         
    }
}
