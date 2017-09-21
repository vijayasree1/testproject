<?php

namespace App\Jobs;

use App\Http\Models\JxPackages;
use App\Http\Models\Plan;
use App\Http\Models\Subscription;
use App\Http\Models\SubscriptionPackage;
use App\Http\Models\Subscriptions;
use App\Http\Models\Terminal;
use App\Http\Services\MobilewareService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class JxSubscriptionData extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;
    
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $mobilewareService = new MobilewareService();
        $subscriptionResponse = $mobilewareService->getSubscriptionData();
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
            $mobilewareService->insertOrUpdate($uniqueColumns, $insertData, '\App\Http\Models\JxSubscription');
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
				$subscriptionData = Subscription::select('all_subscriptions.Subscription_Idx')
                                                ->join('subscription_locations as sl','sl.Subscription_Idx','=','all_subscriptions.Subscription_Idx')
                                                ->join('system_location_mapping as slm','slm.Location_Idx','=','sl.Location_Idx')
                                                ->join('system_customer_mapping as scm','slm.System_Idx','=','scm.System_Idx')
                                                ->join('terminal as t','slm.System_Idx','=','t.System_Idx')
                                                ->where('t.TPK_DID', '=', $row['msisdn'])
                                                ->where('all_subscriptions.Plan_Category_Name', '=', 'JX')
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
}
