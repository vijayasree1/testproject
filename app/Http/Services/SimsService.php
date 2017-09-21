<?php
namespace App\Http\Services;

use App\Http\Models\Plan;
use App\Http\Models\PlanCategory;
use App\Http\Models\SimsCustomerMapping;
use App\Http\Models\SubscriptionStatus;
use App\Http\Models\SubscriptionLocationMapping;
use App\Http\Models\Subscriptions;
use App\Http\Models\MonthlyCallDataRecord;
use App\Http\Models\DailyCallLogs;
use App\Http\Models\SimsSyncActivity;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Illuminate\Http\Response;
use Monolog\Logger;
ini_set('memory_limit', '-1');
set_time_limit(10000);

Class SimsService
{
    var $client = null;

    var $requestOptions = [];
    public function __construct()
    {
        $this->requestOptions = config('simslink.requestDefaultOptions');
        $clientConfig = [
            'base_uri' => config('simslink.baseUri')
        ];
        
        if( config('app.debug') )
        {
            $stack = HandlerStack::create();

            $logger = with(new \Monolog\Logger('api-consumer'))->pushHandler(
                new \Monolog\Handler\StreamHandler(storage_path('/logs/sims-call-log.log'), Logger::DEBUG, true)
            );

            $stack->push(
                Middleware::log( $logger, new MessageFormatter('{req_headers} {req_body} - {res_headers} {res_body}') )
            );

            $clientConfig['handler'] = $stack;
        }

        $this->client = new Client($clientConfig);
    }

    public function listSubscriptions()
    {
        try
        {
            $subscriptionData=Subscriptions::orderBy('Updated_On', 'DESC')->first();
            if( is_null($subscriptionData))
            {
                $SyncDate="1970-01-01 00:00:01";
            }
            else 
            {
                $data=$subscriptionData->toArray();
               
                if($data['updatedOn']=='0000-00-00 00:00:00' || $data['updatedOn']==NULL)
                {
                    $SyncDate="1970-01-01 00:00:01";
                }
                else
                {
                    $SyncDate=$data['updatedOn'];
                }
               
            }
            
           
            $subscription = $this->client->request('GET', 'subscription?lastSyncDate='.$SyncDate, $this->requestOptions);

            if ($subscription->getStatusCode() == Response::HTTP_OK)
            {
                return json_decode($subscription->getBody(),true);
            }

            throw new \Exception($subscription->getBody()->getContents());
        }
        catch (\Exception $e)
        {
            throw new \Exception($e);
        }
    }
    
    public function listSubscriptionPackagePlan()
    {
        try
        {
            $subscriptionData=Subscriptions::orderBy('Updated_On', 'DESC')->first();
            if( is_null($subscriptionData))
            {
                $SyncDate="1970-01-01 00:00:01";
            }
            else
            {
                $data=$subscriptionData->toArray();
                if($data['updatedOn']=='0000-00-00 00:00:00' || $data['updatedOn']==NULL)
                {
                    $SyncDate="1970-01-01 00:00:01";
                }
                else
                {
                    $SyncDate=$data['updatedOn'];
                }
            }
            
            $listSubscriptions = $this->client->request('GET', 'subscription/packageplan?lastSyncDate='.$SyncDate, $this->requestOptions);
    
            if ($listSubscriptions->getStatusCode() == Response::HTTP_OK)
            {
                return json_decode($listSubscriptions->getBody(),true);
            }

            throw new \Exception($listSubscriptions->getBody()->getContents());
        }
        catch (\Exception $e)
        {
            throw new \Exception($e);
        }
    }
    
    public function listSubscriptionDetails()
    {
        try
        {
            $subscriptionData=Subscriptions::orderBy('Updated_On', 'DESC')->first();
            if( is_null($subscriptionData))
            {
                $SyncDate="1970-01-01 00:00:01";
            }
            else
            {
                $data=$subscriptionData->toArray();
                if($data['updatedOn']=='0000-00-00 00:00:00' || $data['updatedOn']==NULL)
                {
                    $SyncDate="1970-01-01 00:00:01";
                }
                else
                {
                    $SyncDate=$data['updatedOn'];
                }
            }
            
            $listSubscriptionDetails = $this->client->request('GET', 'subscription/details?lastSyncDate='.$SyncDate, $this->requestOptions);
    
            if ($listSubscriptionDetails->getStatusCode() == Response::HTTP_OK)
            {
                return json_decode($listSubscriptionDetails->getBody(),true);
            }

            throw new \Exception($listSubscriptionDetails->getBody()->getContents());
        }
        catch (\Exception $e)
        {
            throw new \Exception($e);
        }
    }
    
    public function listSubscriptionLocations()
    {
        try
        {
            $subscriptionLocationsData=SubscriptionLocationMapping::orderBy('Updated_On', 'DESC')->first();
            if( is_null($subscriptionLocationsData))
            {
                $SyncDate="1970-01-01 00:00:01";
            }
            else
            {
                $data=$subscriptionLocationsData->toArray();
                if($data['updatedOn']=='0000-00-00 00:00:00' || $data['updatedOn']==NULL)
                {
                    $SyncDate="1970-01-01 00:00:01";
                }
                else
                {
                    $SyncDate=$data['updatedOn'];
                }
            }
            
            $subscriptionLocations = $this->client->request('GET', 'subscription/location?lastSyncDate='.$SyncDate, $this->requestOptions);
    
            if ($subscriptionLocations->getStatusCode() == Response::HTTP_OK)
            {
                return json_decode($subscriptionLocations->getBody(),true);
            }

            throw new \Exception($subscriptionLocations->getBody()->getContents());
        }
        catch (\Exception $e)
        {
            throw new \Exception($e);
        }
    }
    
    public function listSubscriptionStatus()
    {
        try
        {
            $statusData=SubscriptionStatus::orderBy('Updated_On', 'DESC')->first();
            if( is_null($statusData))
            {
                $SyncDate="1970-01-01 00:00:01";
            }
            else
            {
                $data=$statusData->toArray();
                if($data['updatedOn']=='0000-00-00 00:00:00' || $data['updatedOn']==NULL)
                {
                    $SyncDate="1970-01-01 00:00:01";
                }
                else
                {
                    $SyncDate=$data['updatedOn'];
                }
            }
            
            $status = $this->client->request('GET', 'status?lastSyncDate='.$SyncDate, $this->requestOptions);
    
            if ($status->getStatusCode() == Response::HTTP_OK)
            {
                return json_decode($status->getBody(),true);
            }

            throw new \Exception($status->getBody()->getContents());
        }
        catch (\Exception $e)
        {
            throw new \Exception($e);
        }
    }
    
    public function listCustomerMapping()
    {
        try
        {
            $simsCustomerData=SimsCustomerMapping::orderBy('Updated_On', 'DESC')->first();
            if( is_null($simsCustomerData))
            {
                $SyncDate="1970-01-01 00:00:01";
            }
            else
            {
                $data=$simsCustomerData->toArray();
                if($data['updatedOn']=='0000-00-00 00:00:00' || $data['updatedOn']==NULL)
                {
                    $SyncDate="1970-01-01 00:00:01";
                }
                else
                {
                    $SyncDate=$data['updatedOn'];
                }
            }
            
            $customerMapping = $this->client->request('GET', 'customer?lastSyncDate='.$SyncDate, $this->requestOptions);
    
            if ($customerMapping->getStatusCode() == Response::HTTP_OK)
            {
                return json_decode($customerMapping->getBody(),true);
            }

            throw new \Exception($customerMapping->getBody()->getContents());
        }
        catch (\Exception $e)
        {
            throw new \Exception($e);
        }
    }
    
    public function listPackagePlanTypes()
    {
        try
        {
            $planTypeData=PlanCategory::orderBy('Updated_On', 'DESC')->first();
            if( is_null($planTypeData))
            {
                $SyncDate="1970-01-01 00:00:01";
            }
            else
            {
                $data=$planTypeData->toArray();
                if($data['updatedOn']=='0000-00-00 00:00:00' || $data['updatedOn']==NULL)
                {
                    $SyncDate="1970-01-01 00:00:01";
                }
                else
                {
                    $SyncDate=$data['updatedOn'];
                }
            }
            
            $packagePlanType = $this->client->request('GET', 'packageplan/type?lastSyncDate='.$SyncDate, $this->requestOptions);
    
            if ($packagePlanType->getStatusCode() == Response::HTTP_OK)
            {
                return json_decode($packagePlanType->getBody(),true);
            }
            throw new \Exception($packagePlanType->getBody()->getContents());
        }
        catch (\Exception $e)
        {
            throw new \Exception($e);
        }
    }
    
    public function listPackagePlanName()
    {
        try
        {
            $plan=Plan::orderBy('Updated_On', 'DESC')->first();
            if( is_null($plan))
            {
               $SyncDate="1970-01-01 00:00:01";
            }
            else
            {
               $data=$plan->toArray();
               if($data['updatedOn']=='0000-00-00 00:00:00' || $data['updatedOn']==NULL)
               {
                   $SyncDate="1970-01-01 00:00:01";
               }
               else
               {
                   $SyncDate=$data['updatedOn'];
               }
            }
            
            $packagePlanName = $this->client->request('GET', 'packageplan/name?lastSyncDate='.$SyncDate, $this->requestOptions);
    
            if ($packagePlanName->getStatusCode() == Response::HTTP_OK)
            {
                return json_decode($packagePlanName->getBody(),true);
            }
            throw new \Exception($packagePlanName->getBody()->getContents());
        }
        catch (\Exception $e)
        {
            throw new \Exception($e);
        }
    }
    
    public function listPackagePlan()
    {
        try
        {
            $plan=Plan::orderBy('Updated_On', 'DESC')->first();
            if( is_null($plan))
            {
                $SyncDate="1970-01-01 00:00:01";
            }
            else
            {
                $data=$plan->toArray();
                if($data['updatedOn']=='0000-00-00 00:00:00' || $data['updatedOn']==NULL)
                {
                    $SyncDate="1970-01-01 00:00:01";
                }
                else
                {
                    $SyncDate=$data['updatedOn'];
                }
            }
            
            $packagePlan = $this->client->request('GET', 'packageplan?lastSyncDate='.$SyncDate, $this->requestOptions);
    
            if ($packagePlan->getStatusCode() == Response::HTTP_OK)
            {
                return json_decode($packagePlan->getBody(),true);
            }

            throw new \Exception($packagePlan->getBody()->getContents());
        }
        catch (\Exception $e)
        {
            throw new \Exception($e);
        }
    }
    
    public function listDailyCallLogs($page,$pageSize)
    {
        try
        {
            $simsSync=SimsSyncActivity::where('Activity', '=', 'DAILY')->first();
            if( is_null($simsSync))
            {
                $SyncDate="1970-01-01 00:00:01";
            }
            else
            {
                $data=$simsSync->toArray();
                if($data['lastSyncDate']=='0000-00-00 00:00:00' || $data['lastSyncDate']==NULL)
                {
                    $SyncDate="1970-01-01 00:00:01";
                }
                else
                {
                    $SyncDate=$data['lastSyncDate'];
                }
            }
            
            //$SyncDate="1970-01-01 00:00:01";
    
            $dailyCallLogs = $this->client->request('GET', 'dailycallogs?lastSyncDate='.$SyncDate."&PageNm=".$page."&PageSize=".$pageSize, $this->requestOptions);
    
            if ($dailyCallLogs->getStatusCode() == Response::HTTP_OK)
            {
                return json_decode($dailyCallLogs->getBody(),true);
            }
    
            throw new \Exception($dailyCallLogs->getBody()->getContents());
        }
        catch (\Exception $e)
        {
            throw new \Exception($e);
        }
    }
    
    public function listMonthlyCallLogs($page,$pageSize)
    {
        try
        {
            $simsSync=SimsSyncActivity::where('Activity', '=', 'MONTHLY')->first();
            if( is_null($simsSync))
            {
                $SyncDate="1970-01-01 00:00:01";
            }
            else
            {
                $data=$simsSync->toArray();
                if($data['lastSyncDate']=='0000-00-00 00:00:00' || $data['lastSyncDate']==NULL)
                {
                    $SyncDate="1970-01-01 00:00:01";
                }
                else
                {
                    $SyncDate=$data['lastSyncDate'];
                }
            }
    
            $monthlyCallLogs = $this->client->request('GET', 'monthlycallogs?lastSyncDate='.$SyncDate."&PageNm=".$page."&PageSize=".$pageSize, $this->requestOptions);
    
            if ($monthlyCallLogs->getStatusCode() == Response::HTTP_OK)
            {
                return json_decode($monthlyCallLogs->getBody(),true);
            }
    
            throw new \Exception($monthlyCallLogs->getBody()->getContents());
        }
        catch (\Exception $e)
        {
            throw new \Exception($e);
        }
    }
    
    // Sync Status
    public function syncStatus ()
    {
        $statusResponse =$this->listSubscriptionStatus();
        
        try 
        {
            if (count( $statusResponse ) > 0)
            {
                foreach ($statusResponse as $status)
                {
                    $statusCount = SubscriptionStatus::where('Subscription_Status_Idx', '=', $status['status_id']);
                    $data=$statusCount->get()->toArray();
        
                    if( count($data) > 0 )
                    {
                        $statusData = SubscriptionStatus::where('Subscription_Status_Idx', '=', $status['status_id'])->first();
                    }
                    else
                    {
                        $statusData = new SubscriptionStatus();
                        $statusData->id = $status['status_id'];
                    }
        
                    $statusData->status = $status['status_name'];
                    $statusData->createdOn = $status['created_date'];
                    $statusData->updatedOn = $status['last_updated_date'];
        
                    $statusData->save();
        
                }
            }
        }
        catch (\Exception $e)
        {
            throw new \Exception($e);
        }
    }
    
    // DBMan Customer SIMS Sync
    public function syncCustomerMapping ()
    {
        try 
        {
            $customerMappingResponse = $this->listCustomerMapping();
            
            if (count( $customerMappingResponse ) > 0)
            {
                foreach ($customerMappingResponse as $customerMapping)
                {
                    $simsCustomerMappingCount = SimsCustomerMapping::where('customerId', '=', $customerMapping['dbman_Customer_Idx']);
                    $data=$simsCustomerMappingCount->get()->toArray();
        
                    if( count($data) > 0 )
                    {
                        $customerMappingData = SimsCustomerMapping::where('customerId', '=', $customerMapping['dbman_Customer_Idx'])->first();
                    }
                    else
                    {
                        $customerMappingData = new SimsCustomerMapping();
                        $customerMappingData->customerId = $customerMapping['dbman_Customer_Idx'];
                    }
        
                    $customerMappingData->account = $customerMapping['account_Id'];
                    // FIXME: Null constraint
                    $customerMappingData->createdOn = (empty( $customerMapping['created_date'] ) ? '' :$customerMapping['created_date']);
                    $customerMappingData->updatedOn = $customerMapping['last_updated_date'];
        
                    $customerMappingData->save();
                }
            }
        }
        catch (\Exception $e)
        {
            throw new \Exception($e);
        }
    }
    
    // Plan Category
    public function syncPackagePlanTypes()
    {
        try 
        {
           $planCategoryResponse = $this->listPackagePlanTypes();
            
            if (count( $planCategoryResponse ) > 0)
            {
                foreach ($planCategoryResponse as $planCategory)
                {
                    $planCategoryCount = PlanCategory::where('Plan_Category_Idx', '=', $planCategory['pack_plan_type_id']);
                    $data=$planCategoryCount->get()->toArray();
        
                    if( count($data) > 0 )
                    {
                        $planCategoryData = PlanCategory::where('Plan_Category_Idx', '=', $planCategory['pack_plan_type_id'])->first();
                    }
                    else
                    {
                        $planCategoryData = new PlanCategory();
                        $planCategoryData->id = $planCategory['pack_plan_type_id'];
                    }
        
                     
                    $planCategoryData->planCategory = $planCategory['pack_plan_type_name'];
                    $planCategoryData->planDescription = $planCategory['description'];
                    $planCategoryData->createdBy = $planCategory['created_by'];
                    $planCategoryData->statusId = $planCategory['status_id'];
                    // FIXME:NULL CONSTRAINT
                    $planCategoryData->updatedBy = (empty( $planCategory['last_updated_by'] ) ? '' :$planCategory['last_updated_by']);
                    $planCategoryData->createdOn = $planCategory['created_timestamp'];
                    $planCategoryData->updatedOn = $planCategory['last_updated_timestamp'];
        
                    $planCategoryData->save();
                }
           }
       }
       catch (\Exception $e)
       {
           throw new \Exception($e);
       }
    }
    
    // Sync Plan
    public function syncPlan()
    {
        try
        {
            $packagePlanName = $this->listPackagePlanName();
            $packagePlan =  $this->listPackagePlan();
        
            if(count($packagePlanName)>0 || count($packagePlan)>0)
            {
                foreach ($packagePlanName as $packagePlanName_data)
                {
                    $planCount = Plan::where('Plan_Idx', '=', $packagePlanName_data['package_plan_name_id'])->get();
                    if(count($planCount->toArray())>0 )
                    {
                        $planNameData = Plan::where('Plan_Idx', '=', $packagePlanName_data['package_plan_name_id'])->first();
                    }
                    else
                    {
                        $planNameData = new Plan();
                        $planNameData->planId = $packagePlanName_data['package_plan_name_id'];
                    }
        
        
                    $planNameData->planName = $packagePlanName_data['package_plan_name'];
                    // FIXME:NULL CONSTRAINT
                    $planNameData->planDescription = (empty( $packagePlanName_data['description'] ) ? '' : $packagePlanName_data['description']);
                    $planNameData->createdOn = $packagePlanName_data['created_timestamp'];
                    $planNameData->createdBy = $packagePlanName_data['created_by'];
                    $planNameData->updatedOn = $packagePlanName_data['last_updated_timestamp'];
                    // FIXME:NULL CONSTRAINT
                    $planNameData->updatedBy = (empty( $packagePlanName_data['last_updated_by'] ) ? '' : $packagePlanName_data['last_updated_by']);
        
                    $planNameData->save();
                }
        
                foreach ($packagePlan as $packagePlan_data)
                {
                    if($packagePlan_data['package_plan_type_id']!=0)
                    {
                        $planCount = Plan::where('Plan_Idx', '=', $packagePlan_data['package_plan_name_id'])->get();
            
                        if(count($planCount->toArray())>0)
                        {
                            $planData = Plan::where('Plan_Idx', '=', $packagePlan_data['package_plan_name_id'])->first();
                            $planData->planCategory = $packagePlan_data['package_plan_type_id'];
                            $planData->planLimit = $packagePlan_data['usage_included_in_pack'];
                            //$planData->createdOn = $packagePlan_data['created_timestamp'];
                            //$planData->createdBy = $packagePlan_data['created_by'];
                            $planData->updatedOn = $packagePlan_data['last_updated_timestamp'];
            
                            // FIXME:NULL CONSTRAINT
                            $planData->updatedBy = (empty( $packagePlan_data['last_updated_by'] ) ? '' : $packagePlan_data['last_updated_by']);
            
                            $planData->save();
                        }
                    }
                }
            }
        }
        catch (\Exception $e)
        {
            throw new \Exception($e);
        }
    }

    // Sync Plan
    public function syncPlanNew()
    {
        try
        {
            $packagePlanNames = $this->listPackagePlanName();
            $packagePlans =  $this->listPackagePlan();
            
            $plans = [];
            $packagePlanDetails = [];
            
            if( !empty($packagePlans) )
            {
                foreach( $packagePlans as $packagePlan )
                {
                    // FIXME:ACTIVE PLANS
                    //if($packagePlan['status_id']==1)
                        $packagePlanDetails[$packagePlan['package_plan_name_id']] = $packagePlan;
                }
            }
           
            if( !empty($packagePlanNames) )
            {
                foreach( $packagePlanNames as $packagePlanName )
                {
                    $plans[$packagePlanName['package_plan_name_id']] = $packagePlanName;
            
                    if( array_key_exists($packagePlanName['package_plan_name_id'], $packagePlanDetails)
                            && (!empty( $packagePlanDetails[$packagePlanName['package_plan_name_id']]['package_plan_type_id'] ) && (!is_null($packagePlanDetails[$packagePlanName['package_plan_name_id']]['package_plan_type_id'] ))) )
                    {
                        $plans[$packagePlanName['package_plan_name_id']]['package_plan_type_id'] =
                        $packagePlanDetails[$packagePlanName['package_plan_name_id']]['package_plan_type_id'];
                        
                        $plans[$packagePlanName['package_plan_name_id']]['usage_included_in_pack'] =
                        $packagePlanDetails[$packagePlanName['package_plan_name_id']]['usage_included_in_pack'];
                    }
                }
            }
   
            if( count($plans) > 0 )
            {
                foreach($plans as $packagePlanName_data)
                {
                    $planEntity = Plan::where('Plan_Idx', '=', $packagePlanName_data['package_plan_name_id'])->first();
                    
                    if( (is_null($planEntity) && array_key_exists('package_plan_type_id', $packagePlanName_data) || (!is_null($planEntity) && !array_key_exists('package_plan_type_id', $packagePlanName_data))) )
                    {
                        if( is_null($planEntity) )
                        {
                            $planEntity = new Plan();
                        }
            
                        if(array_key_exists('package_plan_type_id', $packagePlanName_data))
                        {
                            $planEntity->planCategory = $packagePlanName_data['package_plan_type_id'];
                            $planEntity->planLimit = $packagePlanName_data['usage_included_in_pack'];
                        }
            
                        $planEntity->planId = $packagePlanName_data['package_plan_name_id'];
                        $planEntity->planName = $packagePlanName_data['package_plan_name'];
                        $planEntity->planDescription = (empty( $packagePlanName_data['description'] ) ? '' : $packagePlanName_data['description']); // FIXME:NULL CONSTRAINT
                        $planEntity->createdOn = $packagePlanName_data['created_timestamp'];
                        $planEntity->createdBy = $packagePlanName_data['created_by'];
                        $planEntity->updatedOn = $packagePlanName_data['last_updated_timestamp'];
                        $planEntity->updatedBy = (empty( $packagePlanName_data['last_updated_by'] ) ? '' : $packagePlanName_data['last_updated_by']); // FIXME:NULL CONSTRAINT
                        
                        $planEntity->save();
                    }
                }
            }
           
            $result=array_diff_key($packagePlanDetails,$plans);
            foreach ($result as $row)
            {
                if($row['status_id']==1)
                {
                    //print_r($row);
                    $planEntity = Plan::where('Plan_Idx', '=', $row['package_plan_name_id'])->first();
                    
                    if(!is_null($planEntity))
                    {
                        $planEntity->planCategory = $row['package_plan_type_id'];
                        $planEntity->planLimit = $row['usage_included_in_pack'];
                        $planEntity->createdOn = $row['created_timestamp'];
                        $planEntity->createdBy = $row['created_by'];
                        $planEntity->updatedOn = $row['last_updated_timestamp'];
                        $planEntity->updatedBy = (empty( $row['last_updated_by'] ) ? '' : $row['last_updated_by']); // FIXME:NULL CONSTRAINT
                        
                        $planEntity->save();
                        //echo "<br>YES<br>";
                    }
                }
            }
            
        }
        catch (\Exception $e)
        {
            throw new \Exception($e);
        }
    }
    
    // Sync Subscriptions
    public function syncSubscriptions()
    {
        try 
        {
            $simsSubscriptions = $this->listSubscriptions();
            $simsSubscriptionDetails = $this->listSubscriptionDetails();
            $simsSubscriptionPackagePlan = $this->listSubscriptionPackagePlan();
        
            $subscriptions = [];
            $subscriptionDetails = [];
            $subscriptionPackageDetails = [];
        
            if( !empty($simsSubscriptionPackagePlan) )
            {
                foreach( $simsSubscriptionPackagePlan as $subscription )
                {
                    //FIXME: Implement date validation here.
                    if( !array_key_exists($subscription['subs_plan_dtls_id'], $subscriptionDetails) )
                    {
                        $subscriptionPackageDetails[$subscription['subs_plan_dtls_id']] = $subscription;
                    }
                }
            }
        
            if( !empty($simsSubscriptionDetails) )
            {
                foreach( $simsSubscriptionDetails as $subscription )
                {
                    if($subscription['subs_planid']!=0)
                    {
                        if( !array_key_exists($subscription['subscription_id'], $subscriptionDetails) )
                        {
                            $subscriptionDetails[$subscription['subscription_id']] = $subscription;
        
                            if( array_key_exists($subscription['subs_planid'], $subscriptionPackageDetails)
                                    && !empty( $subscriptionPackageDetails[$subscription['subs_planid']]['package_plan_name_id'] ) )
                            {
                                $subscriptionDetails[$subscription['subscription_id']]['plan_name_id'] =
                                $subscriptionPackageDetails[$subscription['subs_planid']]['package_plan_name_id'];
                            }
                        }
                    }
                }
            }
        
            if( !empty($simsSubscriptions) )
            {
                foreach( $simsSubscriptions as $subscription )
                {
                    $subscriptions[$subscription['subscription_id']] = $subscription;
        
                    if( array_key_exists($subscription['subscription_id'], $subscriptionDetails)
                            && !empty( $subscriptionDetails[$subscription['subscription_id']]['plan_name_id'] ) )
                    {
                        $subscriptions[$subscription['subscription_id']]['plan_name_id'] =
                        $subscriptionDetails[$subscription['subscription_id']]['plan_name_id'];
                    }
                }
            }
        
            if( count($subscriptions) > 0 )
            {
                foreach($subscriptions as $subscription)
                {
                    $subscriptionEntity = Subscriptions::where('Subscription_Idx', '=', $subscription['subscription_id'])->first();
    
                    /* if( is_null($subscriptionEntity) && !array_key_exists('plan_name_id', $subscription) )
                    {
                        throw new \Exception('Don\'t throw.');
                    } */
        
                    if( (is_null($subscriptionEntity) && array_key_exists('plan_name_id', $subscription) || (!is_null($subscriptionEntity) && !array_key_exists('plan_name_id', $subscription))) )
                    {
                        if( is_null($subscriptionEntity) )
                        {
                            $subscriptionEntity = new Subscriptions();
                        }
                        
                        if(array_key_exists('plan_name_id', $subscription))
                            $subscriptionEntity->planId = $subscription['plan_name_id'];
                        
                        $subscriptionEntity->id = $subscription['subscription_id'];
                        $subscriptionEntity->customerId = $subscription['service_account_id'];
                        $subscriptionEntity->recurrence = $subscription['validity'];
                        $subscriptionEntity->contractPeriodInMonths = $subscription['contract_period_in_months'];
                        //$subscriptionEntity->subscriptionType = $subscription['subscription_type'];
                        $subscriptionEntity->status = $subscription['status_id'];
                        $subscriptionEntity->billingStatus = $subscription['subscription_billing_status_id'];
                        $subscriptionEntity->createdOn = $subscription['created_Date'];
                        $subscriptionEntity->createdBy = (empty( $subscription['created_by'] ) ? '' :$subscription['created_by']);
                        $subscriptionEntity->updatedOn = $subscription['last_Updated_Date'];
                        $subscriptionEntity->updatedBy = (empty( $subscription['updated_by'] ) ? '' :$subscription['updated_by']);
            
                        $subscriptionEntity->save();
                    }
                    
                }
            }
        
            foreach ($subscriptionDetails as $subscriptionDetails_data)
            {
                $subscriptionPackagePlanData = Subscriptions::where('Subscription_Idx', '=', $subscriptionDetails_data['subscription_id'])->first();
        
                if(count( $subscriptionPackagePlanData->toArray() ) > 0 )
                {
                    $subscriptionPackagePlanData->dataLimit = $subscriptionPackageDetails[$subscriptionDetails_data['subs_planid']]['usage_included_in_pack'];
                    $subscriptionPackagePlanData->planLimitType = $subscriptionPackageDetails[$subscriptionDetails_data['subs_planid']]['units_for_usage'];
                    $subscriptionPackagePlanData->customerPlanPrice = $subscriptionPackageDetails[$subscriptionDetails_data['subs_planid']]['price'];
                    $subscriptionPackagePlanData->customerPlanDiscount = $subscriptionPackageDetails[$subscriptionDetails_data['subs_planid']]['discount_amount'];
                    $subscriptionPackagePlanData->customerPlanLimitOnHand = $subscriptionPackageDetails[$subscriptionDetails_data['subs_planid']]['left_over_limit'];
                    $subscriptionPackagePlanData->startDate = $subscriptionPackageDetails[$subscriptionDetails_data['subs_planid']]['plan_start_date'];
                    $subscriptionPackagePlanData->endDate = $subscriptionPackageDetails[$subscriptionDetails_data['subs_planid']]['plan_end_date'];
        
                    $subscriptionPackagePlanData->save();
                }
            }
        }
        catch (\Exception $e)
        {
            throw new \Exception($e);
        }
    }
    
    
    // Sync Subscription Location
    public function syncSubscriptionLocations ()
    {
        try 
        {
            $subscriptionLocationsResponse = $this->listSubscriptionLocations();
            if (count( $subscriptionLocationsResponse ) > 0)
            {
                foreach ($subscriptionLocationsResponse as $subscriptionLocation)
                {
                    $subscriptionLocationCount = SubscriptionLocationMapping::where('Subscription_Location_Idx', '=', $subscriptionLocation['subscription_location_id']);
                    $data=$subscriptionLocationCount->get()->toArray();
        
                    if( count($data) > 0 )
                    {
                        $subscriptionLocationData = SubscriptionLocationMapping::where('Subscription_Location_Idx', '=',  $subscriptionLocation['subscription_location_id'])->first();
                    }
                    else
                    {
                        $subscriptionLocationData = new SubscriptionLocationMapping();
                        $subscriptionLocationData->id = $subscriptionLocation['subscription_location_id'];
                    }
        
                    // FIXME: Null constraint
                    $subscriptionLocationData->subscriptionId = $subscriptionLocation['subscription_id'];
                    $subscriptionLocationData->locationId = $subscriptionLocation['location_id'];
                    $subscriptionLocationData->startDate = (empty( $subscriptionLocation['start_date'] ) ? '' :$subscriptionLocation['start_date']);
                    $subscriptionLocationData->endDate = $subscriptionLocation['end_date'];
                    $subscriptionLocationData->createdOn = $subscriptionLocation['created_date'];
                    $subscriptionLocationData->updatedOn = $subscriptionLocation['last_updated_date'];
        
                    $subscriptionLocationData->save();
                }
            }
        }
        catch (\Exception $e)
        {
            throw new \Exception($e);
        }
    }
    
    // Update DailyCallLogs
    public function updateDailyCallLogs ()
    {
        try 
        {
            $currentPage = 1;
            $pageSize=10000;
            
            $dailyCallLogsResponse = $this->listDailyCallLogs($currentPage,$pageSize);
            
            $lastpage=$dailyCallLogsResponse['last_page'];
            
            if (count( $dailyCallLogsResponse["data"] ) > 0)
            {
                for (;($currentPage<=$lastpage);$currentPage++)
                {
                    if($currentPage!=1)
                        $dailyCallLogsResponse = $this->listDailyCallLogs($currentPage,$pageSize);
                    
                    foreach ($dailyCallLogsResponse["data"] as $dailyCallLogs)
                    {
                        $dailyCallLogsData = DailyCallLogs::where('Idx', '=', $dailyCallLogs['idx'])->first();
                        
                        if( !is_null($dailyCallLogsData))
                        {
                            //$dailyCallLogsData->Charge = $dailyCallLogs['charge'];
                            //$dailyCallLogsData->Discount = $dailyCallLogs['discount'];
                            //$dailyCallLogsData->Discount_Type  = $dailyCallLogs['discount_type'];
                            //$dailyCallLogsData->Matching_Sc_Pricelist_Key = $dailyCallLogs['matching_sc_pricelist_key'];
                            //$dailyCallLogsData->Matching_Dest_Number_Pricelist_Key  = $dailyCallLogs['matching_dest_number_pricelist'];
                            //$dailyCallLogsData->Dest_Number_Rate  = $dailyCallLogs['dest_number_rate'];
                            //$dailyCallLogsData->Dest_Number_Price  = $dailyCallLogs['dest_number_price'];
                            
                            $dailyCallLogsData->Cust_Volume  = $dailyCallLogs['cust_volume'];
                            $dailyCallLogsData->Cust_Duration  = $dailyCallLogs['cust_duration'];
                            $dailyCallLogsData->Cust_Unit  = $dailyCallLogs['cust_unit'];
                            $dailyCallLogsData->Rate = $dailyCallLogs['rate'];
                            $dailyCallLogsData->Price  = $dailyCallLogs['price'];
                            $dailyCallLogsData->Surcharge_Rate  = $dailyCallLogs['surcharge_rate'];
                            $dailyCallLogsData->Surcharge_Price  = $dailyCallLogs['surcharge_price'];
                            
                            $dailyCallLogsData->save();
                        }
                    }
                }
                
                $updateLastSyncDate=SimsSyncActivity::where('Activity', '=', 'DAILY')->first();
                $updateLastSyncDate->lastSyncDate = \Carbon::now(); //$dailyCallLogs["updated_on"]; 
                $updateLastSyncDate->save();
            }
        }
        catch (\Exception $e)
        {
            throw new \Exception($e);
        }
    }
    
    // Update MonthlyCallLogs
    public function updateMonthlyCallLogs ()
    {
        try 
        {
            $currentPage = 1;
            $pageSize=10000;
            
            $MonthlyCallLogsResponse = $this->listMonthlyCallLogs($currentPage,$pageSize);
            
            $lastpage=$MonthlyCallLogsResponse['last_page'];
            
            if (count($MonthlyCallLogsResponse["data"] ) > 0)
            {
                for (;($currentPage<=$lastpage);$currentPage++)
                {
                    if($currentPage!=1)
                        $MonthlyCallLogsResponse = $this->listMonthlyCallLogs($currentPage,$pageSize);
                
                    foreach ($MonthlyCallLogsResponse["data"] as $MonthlyCallLogs)
                    {
                        $MonthlyCallLogsData = MonthlyCallDataRecord::where('Idx', '=', $MonthlyCallLogs['idx'])->first();
            
                        if( !is_null($MonthlyCallLogsData))
                        {
                            //$MonthlyCallLogsData->Charge = $MonthlyCallLogs['charge'];
                            //$MonthlyCallLogsData->Discount = $MonthlyCallLogs['discount'];
                            //$MonthlyCallLogsData->Discount_Type  = $MonthlyCallLogs['discount_type'];
                            //$MonthlyCallLogsData->Matching_Sc_Pricelist_Key = $MonthlyCallLogs['matching_sc_pricelist_key'];
                            //$MonthlyCallLogsData->Matching_Dest_Number_Pricelist_Key  = $MonthlyCallLogs['matching_dest_number_pricelist'];
                            //$MonthlyCallLogsData->Dest_Number_Rate  = $MonthlyCallLogs['dest_number_rate'];
                            //$MonthlyCallLogsData->Dest_Number_Price  = $MonthlyCallLogs['dest_number_price'];
                            
                            $MonthlyCallLogsData->Cust_Volume  = $MonthlyCallLogs['cust_volume'];
                            $MonthlyCallLogsData->Cust_Duration  = $MonthlyCallLogs['cust_duration'];
                            $MonthlyCallLogsData->Cust_Unit  = $MonthlyCallLogs['cust_unit'];
                            $MonthlyCallLogsData->Rate = $MonthlyCallLogs['rate'];
                            $MonthlyCallLogsData->Price  = $MonthlyCallLogs['price'];
                            $MonthlyCallLogsData->Surcharge_Rate  = $MonthlyCallLogs['surcharge_rate'];
                            $MonthlyCallLogsData->Surcharge_Price  = $MonthlyCallLogs['surcharge_price'];
                            
                            $MonthlyCallLogsData->save();
                       }
                    }
                }
            }
            
            $updateLastSyncDate=SimsSyncActivity::where('Activity', '=', 'MONTHLY')->first();
            $updateLastSyncDate->lastSyncDate = \Carbon::now(); //$MonthlyCallLogsData["updated_on"]; 
            $updateLastSyncDate->save(); 
        }
        catch (\Exception $e)
        {
            throw new \Exception($e);
        }
    }
}

