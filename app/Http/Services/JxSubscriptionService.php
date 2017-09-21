<?php
namespace App\Http\Services;

use App\Http\Models\DBManNotification;
use App\Http\Models\JxPackages;
use App\Http\Models\Plan;
use App\Http\Models\JxPortSwitches;
use App\Http\Models\Subscription;
use App\Http\Models\SubscriptionPackage;
use App\Http\Models\Subscriptions;
use App\Http\Models\Terminal;
use App\Http\Models\JxSubscription;
use App\Http\Services\MobilewareService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;

ini_set('MAX_EXECUTION_TIME', 10000);
set_time_limit(700);
Class JxSubscriptionService
{
    public function __construct($packageId)
    {
        $mobilewareService = new MobilewareService();
        $subscriptionResponse = $mobilewareService->getSubscriptionData($packageId);
        $response = json_decode($subscriptionResponse,true);
        if(isset($response['subscriptions']))
        {
            $this->save($response['subscriptions']);
        }
        elseif(isset($response['status-msg']))
        {
            echo $response['status-msg'];
        }
    }

    function save($subscriptions)
    {
        try
        {
            $insertData = [];
            $metricNames = ['msisdn', 'package-id', 'package-type', 'friendly-name','status','activated-at','template-id','disconnected-at'];
            $uniqueColumns = ['msisdn', 'package-id'];
            foreach($subscriptions as $row)
            {
                $dbrow = null;
                foreach($row as $name => $value)
                {
                    if(in_array( $name, $metricNames))
                    {
                        $dbrow[$name] = $value;
                    }
                }
                $insertData[] = $dbrow;
            }
        
            if(count($insertData) > 0)
            {
                $mobilewareService = new MobilewareService;
        
                foreach($insertData as $values)
                {
                    foreach($values as $metrics => $metricValues)
                    {
                        if(in_array( $metrics, $uniqueColumns))
                        {
                            $attributes[$metrics] = $metricValues;
                        }
                    }
        
                    JxSubscription::updateOrInsert($attributes, $values);
        
                    $subscriptionPortDetails = $mobilewareService->subscriptionDetails($values['package-id']);
        
                    if(count($subscriptionPortDetails)>0)
                    {
                        $portMetricNames = [ 'port-number','auto-negotiation','eth-mode','eth-speed','tag-enable','svn-id'];
        
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
                                    if(in_array( $metrics, $portMetricNames))
                                    {
                                        if($metrics=="auto-negotiation" || $metrics=="tag-enable")
                                        {
                                            $portValues[$metrics] = (($metricValues==true)?"true":"false");
                                        }
                                        else 
                                            $portValues[$metrics] = $metricValues;
                                        
                                    }
                                }
        
                                $portValues["created-at"]=date('Y-m-d H:i:s',strtotime($portDetails['created-at']));
                                $portValues["package-id"]=$subscriptionPortDetails["package-id"];
        
                                JxPortSwitches::updateOrCreate($portAttributes,$portValues);
        
                                $var1="dhcp-range-start-address-ipv4";
                                $var2="dhcp-range-end-address-ipv4";
                                $var3="dhcp-server-netmask-v4";
                                $var4="dhcp-primary-dns-v4";
                                $var5="dhcp-secondary-dns-v4";
                                $var6="dhcp-subnet-v4";
                                $var7="svn-id";
                                $var8="acm-number";
                                
                                $JxSubscriptionData=JxSubscription::where('package-id','=',$subscriptionPortDetails['package-id'])->first();
        
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
                    } 
                }
            }
            else
            {
                echo "Error: No data found\n";exit;
            }
            $packageArray = $templateIds = $terminalData = $subscriptionData = [];
            $packagesData= JxPackages::where('type','=','commercial')->get()->toArray();
            foreach($packagesData as $package)
            {
                $packageArray[$package['templateId']] = $package;
            }
            $templateIds = array_keys($packageArray);
            foreach($insertData as $row)
            {
                if(in_array( $row['template-id'], $templateIds ))
                {
                    $planData = JxPackages::select('planid as id')->where('templateid','=',$row['template-id'])->get();
                    $subscriptionQuery = Subscription::select('all_subscriptions.Subscription_Idx')
                    ->join('subscription_locations as sl','sl.Subscription_Idx','=','all_subscriptions.Subscription_Idx')
                    ->join('system_location_mapping as slm','slm.Location_Idx','=','sl.Location_Idx')
                    ->join('system_customer_mapping as scm',function($join){
                        $join->on('slm.System_Idx','=', 'scm.System_Idx');
                        $join->on('all_subscriptions.Customer_Idx', '=', 'scm.Customer_Idx');
                    });
                    
                    $subscriptionData = $subscriptionQuery->join('terminal as t','slm.System_Idx','=','t.System_Idx')
                    ->where('t.TPK_DID', '=', $row['msisdn'])
                    ->where('all_subscriptions.Plan_Category_Name', '=', 'JX')
                    ->whereRaw('(all_subscriptions.Subscription_End_Date > NOW() or all_subscriptions.Subscription_End_Date is null)')   
                    ->whereRaw('(scm.Start_Date < NOW() AND (scm.End_Date > NOW() OR scm.End_Date IS NULL))
                                                                                  AND (slm.Start_Date < NOW() AND (slm.End_Date > NOW() OR slm.End_Date IS NULL))')
                                                                                              ->get()->toArray();
            
                }
                if($row['status'] == 'ACTIVE' && in_array( $row['template-id'], $templateIds ))
                {
                    $terminalData = [];
                    if(count($subscriptionData) == 0)
                    {
                        $terminalData = Terminal::select(['scm.Customer_Idx','slm.Location_Idx'])->join('system as s','s.Idx','=','terminal.System_Idx')
                        ->join('system_customer_mapping as scm','s.Idx','=','scm.System_Idx')
                        ->join('system_location_mapping as slm','s.Idx','=','slm.System_Idx')
                        ->whereRaw('(scm.Start_Date < NOW() AND (scm.End_Date > NOW() OR scm.End_Date IS NULL))
                                                           AND (slm.Start_Date < NOW() AND (slm.End_Date > NOW() OR slm.End_Date IS NULL))')
                                                                       ->where('terminal.TPK_DID', '=', $row['msisdn'])
                                                                       ->get();
                    }
                    if(count($terminalData) > 0)
                    {
                        foreach($terminalData as $data)
                        {
                            if(isset($planData[0]->planid) && $planData[0]->planid>0)
                            {
                                $subscriptions = new Subscriptions;
                                $subscriptions->customerId = $data->Customer_Idx;
                                $subscriptions->planId = $planData[0]->planid;
                                $subscriptions->status = 1;
                                $subscriptions->billingStatus = 1;
                                $subscriptions->dataLimit = $packageArray[$row['template-id']]['usage']*1024;
                                $subscriptions->planLimitType = 'MB';
                                $date = new \DateTime($row['activated-at']);
                                $subscriptions->startDate = $date->format('Y-m-d');
                                //$subscriptions->endDate='Annual';
                                $subscriptions->recurrence = 'Monthly';
                                $subscriptions->createdOn = \Carbon::now();
                                $subscriptions->createdBy = 'DBMan Admin';
                                $subscriptions->updatedOn = \Carbon::now();
                                $subscriptions->updatedBy = 'DBMan Admin';
                                $subscriptions->save();
                                DB::table('subscription_locations')->insert([
                                    'Subscription_Idx' => $subscriptions->id,
                                    'Location_Idx' => $data->Location_Idx,
                                    'Subscription_Start_Date' => $date->format('Y-m-d'),
                                    'Subscription_Status_Notes' => 'auto synced new subscription',
                                    'Created_On' => \Carbon::now(),
                                    'Created_By' => 'DBMan Admin',
                                    'Updated_On' => \Carbon::now(),
                                    'Last_Updated_By' => 'DBMan Admin'
                                ]);
                                $subscriptionPackage = new SubscriptionPackage;
                                $subscriptionPackage->subscriptionId = $subscriptions->Subscription_Idx;
                                $subscriptionPackage->packageId = $row['package-id'];
                                $subscriptionPackage->templateId = $row['template-id'];
                                $subscriptionPackage->save();
                            }
                            else 
                            {
                                $exception = "Plan Id not there";
                                Mail::send(
                                    'emails.jx-subscription-notification', [
                                        'exception' => $exception,
                                    ], function ($message) {
                                        $usersToNotify = DBManNotification::with(['user'])
                                        ->where('Notification_Type', '=', 'JX_SYNC')
                                        ->whereRaw('Start_Date < NOW() AND IFNULL(End_Date, NOW()) >= NOW()')
                                        ->get()->toArray();
                            
                                        $message->from('noreply@honeywell.com', 'DBMan')
                                        ->subject('DBMan ' . (App::environment('production') ? '':
                                                '(' . strtoupper( App::environment() ) . ')' )  .
                                                ' - JX Subscriptions sync notification');
                                        $message->to(array_pluck($usersToNotify, 'email'));
                                        $message->priority(2);
                                    });
                            }
                        }
                    }
                }
                elseif(in_array( $row['template-id'], $templateIds ) && count($subscriptionData) > 0)
                {
                    $subscriptions = new Subscriptions;
                    $subscriptions->id = $subscriptionData[0]['id'];
                    $subscriptions->endDate = $row['disconnected-at'];
                    $subscriptions->update();
                }
            }
        }
        catch (\Exception $e)
        {
            Log::error($e);
            throw new \Exception("Error occurred while JX Sync");
        }
    }
}

