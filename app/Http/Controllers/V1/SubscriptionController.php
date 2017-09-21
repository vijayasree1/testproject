<?php
namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Models\CallDataRecord;
use App\Http\Models\Customer;
use App\Http\Models\JxRealTime;
use App\Http\Models\Location;
use App\Http\Models\Subscription;
use App\Http\Models\SubscriptionUsage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Response;
class SubscriptionController extends Controller
{
    /**
     * @SWG\Get(
     *     path="/subscriptions",
     *     summary="/subscriptions resource",
     *     tags={"subscriptions"},
     *     description="This resource is dedicated to querying data around Subscriptions.",
     *     operationId="listSubscriptions",
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
     * @SWG\Parameter(
     *         name="page_size",
     *         in="query",
     *         description="Number of results Per Page",
     *         required=false,
     *         type="integer",
     *         @SWG\Items(type="integer"),
     *     ),
     * @SWG\Parameter(
     *         name="customerId",
     *         in="query",
     *         description="Filter Subscriptions by Customer Id",
     *         required=false,
     *         type="integer",
     *         @SWG\Items(type="integer"),
     *         collectionFormat="csv"
     *     ),
     * @SWG\Parameter(
     *         name="locationId",
     *         in="query",
     *         description="Filter Subscriptions by Location Id",
     *         required=false,
     *         type="integer",
     *         @SWG\Items(type="string"),
     *         collectionFormat="csv"
     *     ),
     * @SWG\Parameter(
     *         name="hwContactId",
     *         in="query",
     *         description="Filter Subscriptions by Honeywell Contact Id",
     *         required=false,
     *         type="integer",
     *         @SWG\Items(type="string"),
     *         collectionFormat="csv"
     *     ),
     * @SWG\Parameter(
     *         name="planType",
     *         in="query",
     *         description="Filter Subscriptions by Plan Type, e.g. SBB",
     *         required=false,
     *         type="string",
     *         enum={"SBB", "JX"},
     *     ),
     * @SWG\Response(
     *         response=200,
     *         description="Success",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(ref="#/definitions/Subscription")
     *         ),
     *     ),
     * @SWG\Response(
     *         response="400",
     *         description="Invalid tag value",
     *     ),
     * @SWG\Response(
     *         response="401",
     *         description="Unauthorized access",
     *     )
     *
     * ),
     */
    public function listSubscriptions(Request $request)
    {
        $subscriptionsQuery = Subscription::select(DB::raw('all_subscriptions.*'))
            ->leftJoin('all_subscription_locations', 'all_subscription_locations.Subscription_Idx', '=', 'all_subscriptions.Subscription_Idx')
            ->whereRaw('( NOW() BETWEEN IFNULL(all_subscriptions.Subscription_Start_Date, NOW()) AND IFNULL(all_subscriptions.Subscription_End_Date, NOW()) )');

        if ($request->has('hwContactId')) {
            $subscriptionsQuery->join('group_locations', 'group_locations.Location_Idx', '=', 'all_subscription_locations.Location_Idx')
                ->join('groups', 'groups.Group_Idx', '=', 'group_locations.Group_Idx')
                ->join('contact_groups', 'contact_groups.Group_Idx', '=', 'groups.Group_Idx')
                ->join('contact', 'contact_groups.Contact_Idx', '=', 'contact.Idx')
                ->where('contact.Honeywell_Id', 'LIKE', $request->input('hwContactId'))
                ->whereRaw('( NOW() BETWEEN IFNULL(contact_groups.Start_Date, NOW()) AND IFNULL(contact_groups.End_Date, NOW()) )')
                ->whereRaw('( NOW() BETWEEN IFNULL(group_locations.Start_Date, NOW()) AND IFNULL(group_locations.End_Date, NOW()) )');
        }

        if ($request->has('locationId')) {
            $subscriptionsQuery->where('all_subscription_locations.Location_Idx', '=', $request->input('locationId'))
                ->whereRaw('( NOW() BETWEEN IFNULL(all_subscription_locations.Start_Date, NOW()) AND IFNULL(all_subscription_locations.End_Date, NOW()) )');
        }

        if ($request->has('customerId')) {
            //$subscriptionsQuery->where('all_subscriptions.Customer_Idx', '=', $request->input('customerId'));
            $customer = Customer::find($request->input('customerId'));

            if( $customer->Is_Management_Company == 'Y' )
            {
                $subscriptionsQuery->whereIn('all_subscriptions.Customer_Idx', function($query) use($request) {
                    $query->select('Customer_Idx')
                        ->from('mgmt_managed_companies')
                        ->where('Management_Customer_Idx', $request->input('customerId'));
                });

                $subscriptionsQuery->whereIn('all_subscription_locations.Location_Idx', function($query) use($request) {
                    $query->select('Location_Idx')
                        ->from('mgmt_managed_locations')
                        ->where('Management_Customer_Idx', $request->input('customerId'));
                });
            }
            else
            {
                $subscriptionsQuery->where('all_subscriptions.Customer_Idx', '=', $request->input('customerId'));
            }
        }
        
        if ($request->has('planType')) {
            /*$subscriptionsQuery->join('plan', 'plan.Plan_Name', '=', 'all_subscriptions.Plan_Name')
                    ->join('plan_category', 'plan_category.Plan_Category_Idx', '=', 'plan.Plan_Category_Idx')
                    ->where('plan_category.Plan_Category_Name', '=', $request->input('planType'));*/
            $subscriptionsQuery->where('all_subscriptions.Plan_Category_Name','=',$request->input('planType'));	
        }

        return $subscriptionsQuery->groupBy('all_subscriptions.Subscription_Idx')->paginate(intval($request->input('page_size', 50)));
    }

    /**
     * @SWG\Get(
     *     path="/subscriptions/{subscriptionId}",
     *     summary="/subscriptions by Id resource",
     *     tags={"subscriptions"},
     *     description="This resource is dedicated to querying data around Subscriptions.",
     *     operationId="getSubscriptionById",
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
     * @SWG\Parameter(
     *         name="page_size",
     *         in="query",
     *         description="Number of results Per Page",
     *         required=false,
     *         type="integer",
     *         @SWG\Items(type="integer"),
     *     ),
     * @SWG\Parameter(
     *         name="subscriptionId",
     *         in="path",
     *         description="Filter Subscriptions by Subscription Id",
     *         required=true,
     *         type="integer",
     *         @SWG\Items(type="integer"),
     *         collectionFormat="csv"
     *     ),
     * @SWG\Parameter(
     *         name="customerId",
     *         in="query",
     *         description="Filter Subscriptions by Customer Id",
     *         required=false,
     *         type="integer",
     *         @SWG\Items(type="integer"),
     *         collectionFormat="csv"
     *     ),
     * @SWG\Parameter(
     *         name="hwContactId",
     *         in="query",
     *         description="Filter Subscriptions by Honeywell Contact Id",
     *         required=false,
     *         type="integer",
     *         @SWG\Items(type="string"),
     *         collectionFormat="csv"
     *     ),
     * @SWG\Response(
     *         response=200,
     *         description="Success",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(ref="#/definitions/Subscription")
     *         ),
     *     ),
     * @SWG\Response(
     *         response="400",
     *         description="Invalid tag value",
     *     ),
     * @SWG\Response(
     *         response="401",
     *         description="Unauthorized access",
     *     )
     *
     * ),
     */
    public function getSubscriptionById(Request $request, $subscriptionId)
    {
        $subscriptionsQuery = Subscription::select(DB::raw('distinct all_subscriptions.*'))
            ->join('all_subscription_locations', 'all_subscription_locations.Subscription_Idx', '=', 'all_subscriptions.Subscription_Idx')
            ->where('all_subscriptions.Subscription_Idx', '=', $subscriptionId)
            ->whereRaw('( NOW() BETWEEN IFNULL(all_subscriptions.Subscription_Start_Date, NOW()) AND IFNULL(all_subscriptions.Subscription_End_Date, NOW()) )');

        if ($request->has('customerId')) {
            //$subscriptionsQuery->where('all_subscriptions.Customer_Idx', '=', $request->input('customerId'));
            $customer = Customer::find($request->input('customerId'));

            if( $customer->Is_Management_Company == 'Y' )
            {
                $subscriptionsQuery->whereIn('all_subscriptions.Customer_Idx', function($query) use($request) {
                    $query->select('Customer_Idx')
                        ->from('mgmt_managed_companies')
                        ->where('Management_Customer_Idx', $request->input('customerId'));
                });

                $subscriptionsQuery->whereIn('all_subscription_locations.Location_Idx', function($query) use($request) {
                    $query->select('Location_Idx')
                        ->from('mgmt_managed_locations')
                        ->where('Management_Customer_Idx', $request->input('customerId'));
                });
            }
            else
            {
                $subscriptionsQuery->where('all_subscriptions.Customer_Idx', '=', $request->input('customerId'));
            }
        }

        $subscriptionLocationsQuery = Location::select(DB::raw('distinct location.*'))
            ->join('all_subscription_locations', 'all_subscription_locations.Location_Idx', '=', 'location.Idx')
            ->join('all_subscriptions', 'all_subscriptions.Subscription_Idx', '=', 'all_subscription_locations.Subscription_Idx')
            ->where('all_subscriptions.Subscription_Idx', '=', $subscriptionId)
            ->whereRaw('( NOW() BETWEEN IFNULL(all_subscription_locations.Start_Date, NOW()) AND IFNULL(all_subscription_locations.End_Date, NOW()) )');

        if ($request->has('hwContactId')) {
            $subscriptionLocationsQuery->join('group_locations', 'group_locations.Location_Idx', '=', 'all_subscription_locations.Location_Idx')
                ->join('groups', 'groups.Group_Idx', '=', 'group_locations.Group_Idx')
                ->join('contact_groups', 'contact_groups.Group_Idx', '=', 'groups.Group_Idx')
                ->join('contact', 'contact_groups.Contact_Idx', '=', 'contact.Idx')
                ->where('contact.Honeywell_Id', 'LIKE', $request->input('hwContactId'))
                ->whereRaw('( NOW() BETWEEN IFNULL(contact_groups.Start_Date, NOW()) AND IFNULL(contact_groups.End_Date, NOW()) )')
                ->whereRaw('( NOW() BETWEEN IFNULL(group_locations.Start_Date, NOW()) AND IFNULL(group_locations.End_Date, NOW()) )');
        }

        $subscription = $subscriptionsQuery->first();

        if ($subscription == null) {
            return response('Subscription Not Found', 404);
        }

        $subscriptionDetails = $subscription->toArray();

        $subscriptionDetails['locations'] = $subscriptionLocationsQuery->get();

        //If asked to show the subscription details, filtered by Contact and contact doesn't have access to any of the
        // locations in the subscription
        if ($request->has('hwContactId') && count($subscriptionDetails['locations']) == 0) {
            return response('Contact doesn\'t have access to any location in the subscription.', 401);
        }

        $usageDetailsQuery = DB::table('subscription_usages')
            ->select(DB::raw('SUM(Data_Usage) as dataUsed, SUM(Voice_Usage) as voiceUsed, SUM(Streaming_Usage) as streamingUsed'))
            ->where('Subscription_Idx', '=', $subscriptionId)
            ->groupBy('Subscription_Idx');

        if ($subscriptionDetails['recurrence'] == 'Monthly') {
            $usageDetailsQuery->whereRaw('`Year_Month`=EXTRACT(YEAR_MONTH FROM NOW())', []);
        }

        $usageDetails = $usageDetailsQuery->first();

        if ($usageDetails !== null) {
            $subscriptionDetails = array_merge($subscriptionDetails, get_object_vars($usageDetails));
        } else {
            $subscriptionDetails = array_merge($subscriptionDetails, ['dataUsed' => 0, 'voiceUsed' => 0, 'streamingUsed' => 0]);
        }

        return $subscriptionDetails;
    }

    /**
     * @SWG\Get(
     *     path="/subscriptions/{subscriptionId}/usage",
     *     summary="/subscription usage by Id resource",
     *     tags={"subscriptions"},
     *     description="This resource is dedicated to querying usage data around Subscriptions.",
     *     operationId="getSubscriptionUsageDetails",
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
     * @SWG\Parameter(
     *         name="page_size",
     *         in="query",
     *         description="Number of results Per Page",
     *         required=false,
     *         type="integer",
     *         @SWG\Items(type="integer"),
     *     ),
     * @SWG\Parameter(
     *         name="subscriptionId",
     *         in="path",
     *         description="Filter Subscription usage details by Subscription Id",
     *         required=true,
     *         type="integer",
     *         @SWG\Items(type="integer"),
     *         collectionFormat="csv"
     *     ),
     * @SWG\Parameter(
     *         name="customerId",
     *         in="query",
     *         description="Filter Subscription usage details by Customer Id",
     *         required=false,
     *         type="integer",
     *         @SWG\Items(type="integer"),
     *         collectionFormat="csv"
     *     ),
     * @SWG\Parameter(
     *         name="locationId",
     *         in="query",
     *         description="Filter Subscription usage details by Location Id",
     *         required=false,
     *         type="integer",
     *         @SWG\Items(type="string"),
     *         collectionFormat="csv"
     *     ),
     * @SWG\Parameter(
     *         name="hwContactId",
     *         in="query",
     *         description="Filter Subscription usage details by Honeywell Contact Id",
     *         required=false,
     *         type="integer",
     *         @SWG\Items(type="string"),
     *         collectionFormat="csv"
     *     ),
     * @SWG\Parameter(
     *         name="from",
     *         in="query",
     *         description="Filter Subscription usage details from month e.g: for month 201703, for day 2017-03-03, for hour 2017-03-03 23:10:10",
     *         required=false,
     *         type="integer",
     *         @SWG\Items(type="integer"),
     *         collectionFormat="csv"
     *     ),
     * @SWG\Parameter(
     *         name="to",
     *         in="query",
     *         description="Filter Subscription usage details till month e.g: for month 201703, for day 2017-03-05, for hour 2017-03-05 00:10:10",
     *         required=false,
     *         type="integer",
     *         @SWG\Items(type="integer"),
     *         collectionFormat="csv"
     *     ),
     * @SWG\Parameter(
     *         name="interval",
     *         in="query",
     *         description="Filter Subscription usage details",
     *         required=false,
     *         type="string",
     *         enum={"month", "day", "hour"},
     *         @SWG\Items(type="string"),
     *         collectionFormat="csv"
     *     ),
     * @SWG\Response(
     *         response=200,
     *         description="Success",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(ref="#/definitions/SubscriptionUsage")
     *         ),
     *     ),
     * @SWG\Response(
     *         response="400",
     *         description="Invalid tag value",
     *     ),
     * @SWG\Response(
     *         response="401",
     *         description="Unauthorized access",
     *     )
     *
     * ),
     */
    public function getSubscriptionUsageDetails(Request $request, $subscriptionId)
    {     
        $subscriptionCategory = Subscription::where('Subscription_Idx', '=', $subscriptionId)->get()->first();
        if(! is_null($subscriptionCategory) && $subscriptionCategory->planCategory != 'JX' && isset($request->interval) && ($request->interval=='day' || $request->interval=='hour'))
        {
            return \response([
                'message' => 'Type allowed only for JX'
            ], Response::HTTP_BAD_REQUEST);
        }
        elseif(is_null($subscriptionCategory))
        {
            return \response([
                'message' => 'Subscription data not found'
            ], Response::HTTP_BAD_REQUEST);
        }

        $subscriptionUsageQuery = SubscriptionUsage::select(DB::raw('distinct subscription_usages.*'))
            ->where('subscription_usages.Subscription_Idx', '=', $subscriptionId)
            ->join('all_subscriptions', 'all_subscriptions.Subscription_Idx', '=', 'subscription_usages.Subscription_Idx')
            ->join('all_subscription_locations', 'all_subscription_locations.Subscription_Idx', '=', 'all_subscriptions.Subscription_Idx')
            ->whereRaw('( NOW() BETWEEN IFNULL(all_subscriptions.Subscription_Start_Date, NOW()) AND IFNULL(all_subscriptions.Subscription_End_Date, NOW()) )');

        $searchParams = [
            'locationId' => [
                'columnName' => 'subscription_usages.Location_Idx',
                'operator' => '='
            ],
            'from' => [
                'columnName' => 'subscription_usages.Year_Month',
                'operator' => '>='
            ],
            'to' => [
                'columnName' => 'subscription_usages.Year_Month',
                'operator' => '<='
            ]
        ];

        foreach ($searchParams as $searchParam => $column) {
            if ($request->has($searchParam)) {
                $subscriptionUsageQuery->where($column['columnName'], $column['operator'], $request->input($searchParam));
            }
        }

        if ($request->has('hwContactId')) {
            $subscriptionUsageQuery->join('group_locations', 'group_locations.Location_Idx', '=', 'all_subscription_locations.Location_Idx')
                ->join('groups', 'groups.Group_Idx', '=', 'group_locations.Group_Idx')
                ->join('contact_groups', 'contact_groups.Group_Idx', '=', 'groups.Group_Idx')
                ->join('contact', 'contact_groups.Contact_Idx', '=', 'contact.Idx')
                ->where('contact.Honeywell_Id', 'LIKE', $request->input('hwContactId'))
                ->whereRaw('( NOW() BETWEEN IFNULL(contact_groups.Start_Date, NOW()) AND IFNULL(contact_groups.End_Date, NOW()) )')
                ->whereRaw('( NOW() BETWEEN IFNULL(group_locations.Start_Date, NOW()) AND IFNULL(group_locations.End_Date, NOW()) )');
        }

        if($subscriptionCategory->planCategory == 'JX')
        {
            $updatedAtRow = JxRealTime::orderBy('cts','Desc')->first();
            $updatedAt = ! is_null($updatedAtRow)?$updatedAtRow->cts:null;
        }
        else
        {
            $latestCdr = CallDataRecord::query()->orderBy('Date', 'DESC')->first();
        }
        if(! is_null($subscriptionCategory) && $subscriptionCategory->planCategory == 'JX' && $request->interval == 'day')
        {
            $trafficQuery = DB::table('jx_traffic_daily as jt')->select(DB::raw("all_subscription_locations.Location_Idx as locationId,date_format(`timestamp`,'%Y%m') month,dataUsed,date_format(timestamp,'%Y-%m-%d %H:%i:%S') timestamp,0 'voiceUsed',0 'streamingUsed'"));
            if($request->has('from'))
            {
                $trafficQuery->whereRaw("date(timestamp)>='".$request->input('from')."'");
            }
            if($request->has('to'))
            {
                $trafficQuery->whereRaw("date(`timestamp`) <= '".$request->input('to')."'");
            }
        }
        elseif(! is_null($subscriptionCategory) && $subscriptionCategory->planCategory == 'JX' && $request->interval == 'hour')
        {
            $trafficQuery = DB::table('jx_traffic_hourly as jt')->select(DB::raw("all_subscription_locations.Location_Idx as locationId,date_format(`timestamp`,'%Y%m') month,dataUsed,date_format(timestamp,'%Y-%m-%d %H:%i:%S') timestamp,0 'voiceUsed',0 'streamingUsed'"));
            if($request->has('from'))
            {
                $trafficQuery->whereRaw("timestamp >= '".$request->input('from')."'");
            }
            if($request->has('to'))
            {
                $trafficQuery->whereRaw("timestamp <= '".$request->input('to')."'");
            }
        }
        if(! is_null($subscriptionCategory) && $subscriptionCategory->planCategory == 'JX' && ($request->interval == 'hour' || $request->interval == 'day'))
        {
            $trafficQuery->join('subscription_package as sp','sp.package_id','=','jt.package-id')
                         ->join('all_subscriptions', 'all_subscriptions.Subscription_Idx', '=', 'sp.subscription_id')
                         ->join('all_subscription_locations', 'all_subscription_locations.Subscription_Idx', '=', 'all_subscriptions.Subscription_Idx')
                         ->whereRaw('( NOW() BETWEEN IFNULL(all_subscriptions.Subscription_Start_Date, NOW()) AND IFNULL(all_subscriptions.Subscription_End_Date, NOW()) )');
            $trafficQuery->where('sp.subscription_id','=',$subscriptionId);
            if($request->has('locationId'))
            {
                $trafficQuery->where('all_subscription_locations.Location_Idx','=',$request->input('locationId'));
            }
            if($request->has('customerId'))
            {
                $trafficQuery->join('all_subscriptions as as','as.Subscription_Idx','=','sp.subscription_id')
                             ->where('as.Customer_Idx','=',$request->input('customerId'));
            }
            if ($request->has('hwContactId')) 
            {
                $trafficQuery->join('group_locations', 'group_locations.Location_Idx', '=', 'all_subscription_locations.Location_Idx')
                            ->join('groups', 'groups.Group_Idx', '=', 'group_locations.Group_Idx')
                            ->join('contact_groups', 'contact_groups.Group_Idx', '=', 'groups.Group_Idx')
                            ->join('contact', 'contact_groups.Contact_Idx', '=', 'contact.Idx')
                            ->where('contact.Honeywell_Id', 'LIKE', $request->input('hwContactId'))
                            ->whereRaw('( NOW() BETWEEN IFNULL(contact_groups.Start_Date, NOW()) AND IFNULL(contact_groups.End_Date, NOW()) )')
                            ->whereRaw('( NOW() BETWEEN IFNULL(group_locations.Start_Date, NOW()) AND IFNULL(group_locations.End_Date, NOW()) )');
            }
            $trafficQuery->orderBy('timestamp');
            return array(
                'lastUpdatedAt' => $updatedAt,
                'data' => $trafficQuery->get()
            );
        }

        return array(
            'lastUpdatedAt' => ($subscriptionCategory->planCategory == 'JX')?$updatedAt:$latestCdr['Date'],
            'data' => $subscriptionUsageQuery->get()
        );
    }


    public function createSubscription(Request $request) {

        /*DB::listen(function($sql){
            echo $sql->sql;
        });*/

        try {

            \DB::beginTransaction();
            $subscription = new Subscription([
                'customerId' => $request['customerId'],
                'startDate' => $request['startDate'],
                'endDate' => $request['endDate'],
                'planName' => $request['planName'],
                'recurrence' => $request['reccurence'],
                'dataLimit' => $request['dataLimit'],
            ]);

            /*if (!$subscription->validate($request->method())) {
                return response($group->errors(), 400);
            }*/

            if (empty($request['startDate'])) { $subscription->startDate = \Carbon::now(); } else { $subscription->startDate = $request['startDate']; }
            if (empty($request['endDate'])) { $subscription->endDate = null; } else { $subscription->endDate = $request['endDate']; }
            
            $subscription->save();

            $locations = Location::find($request['location']); //Location is Location[] from the HTML Form name input.
            if (!empty($locations)) {
                foreach ($request['location'] as $location) { //location idx  

                    $locationIds = DB::table('location')->select('location.Idx as LocationIdx', 'customer.Idx as CustomerIdx')
                    ->join('system_location_mapping','system_location_mapping.Location_Idx', '=', 'location.Idx' )
                    ->join('system','system_location_mapping.System_Idx', '=', 'system.Idx' )
                    ->join('system_customer_mapping','system_customer_mapping.System_Idx', '=', 'system.Idx' )
                    ->join('customer','system_customer_mapping.Customer_Idx', '=', 'customer.Idx' )
                    ->whereRaw('(((now() >= system_customer_mapping.Start_Date and system_customer_mapping.End_Date is NULL) OR (now() > system_customer_mapping.Start_Date and now() < system_customer_mapping.End_Date))
                        AND ((now() >= system_location_mapping.Start_Date and system_location_mapping.End_Date is NULL) OR (now() > system_location_mapping.Start_Date and now() < system_location_mapping.End_Date))) ')
                    ->where('location.Idx', '=', $location)
                    ->where('customer.Idx', '=', $group->customerId)
                    ->groupBy('location.Idx')->get();

                    if (count($locationIds) == 0) {  throw new \Exception("A location specified does not belong to the customer", 1); };

                    $group->locations()->attach($location, ['Group_Idx' => $group->groupId, 'Start_Date' => \Carbon::now(), 'Updated_On' => \Carbon::now(), 'Last_Updated_By' => 'API REQUEST']);
                }
            }

            if($group->save()) {
                \DB::commit();
                return response(array('groupId' => $group->groupId), 200);
            } else {
                \DB::rollback();
                return response(301);
            }

        }
        catch (\Exception $e) {
            $error = $e->getMessage();
            \DB::rollback();
            if($error){
                return response($error, 301);
            }
        }

    }
}
