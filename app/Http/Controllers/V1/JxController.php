<?php
namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Models\JxMetric;
use App\Http\Models\JxRealTime;
use App\Http\Models\JxCDR;
use App\Http\Models\SubscriptionPackage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Models\JxFleetLocation;
use App\Http\Models\Customer;
class JxController extends Controller
{
    /**
     * @SWG\Get(
     *     path="/jx-metrics",
     *     summary="/jx metrics list",
     *     tags={"jx"},
     *     description="This resource is dedicated to querying data around Jx Metrics.",
     *     operationId="getJxMetrics",
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
     * @SWG\Response(
     *         response=200,
     *         description="Success",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(ref="#/definitions/JxMetric")
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
    public function getJxMetrics(Request $request)
    {
        // Get Metric List from DB which are marked as Active
        $metricsList = JxMetric::select(DB::raw('Id, metric_id, metric_name, status'))
                       ->where('jx_metric_list.status', '=', 'Active');

        return $metricsList->groupBy('jx_metric_list.Id')->paginate(intval($request->input('page_size', 50)));
    }

    /**
     * @SWG\Get(
     *     path="/jx-metrics/{subscriptionId}",
     *     summary="/jx metrics list based on subscription",
     *     tags={"jx"},
     *     description="This resource is dedicated to querying data around Jx Metrics for given Subscription",
     *     operationId="getJxMetricsBySubscriptionId",
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
     *         description="Filter Jx metrics by Subscription Id",
     *         required=true,
     *         type="integer",
     *         @SWG\Items(type="integer"),
     *         collectionFormat="csv"
     *     ),
     *     @SWG\Parameter(
     *         name="metric",
     *         in="query",
     *         description="Metrics",
     *         required=false,
     *         type="array",
     *         @SWG\Items(type="integer"),
     *     ),
     * @SWG\Parameter(
     *         name="from",
     *         in="query",
     *         description="Filter Jx metrics from date. Format: YYYYMMDD",
     *         required=true,
     *         type="integer",
     *         @SWG\Items(type="integer"),
     *         collectionFormat="csv"
     *     ),
     * @SWG\Parameter(
     *         name="to",
     *         in="query",
     *         description="Filter Jx metrics till date. Format: YYYYMMDD",
     *         required=true,
     *         type="integer",
     *         @SWG\Items(type="integer"),
     *         collectionFormat="csv"
     *     ),
     * @SWG\Response(
     *         response=200,
     *         description="Success",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(ref="#/definitions/JxCDR")
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
    public function getJxMetricsBySubscriptionId(Request $request, $subscriptionId)
    {
        // Get Metric List from DB which are marked as Active
        $metricsListQuery = JxMetric::select(DB::raw('*'))
                       ->where('jx_metric_list.status', '=', 'Active')
                       ->where('jx_metric_list.used_metric', '=', 1)
                       ->get()->toArray();

        $metricsList = array();
        foreach($metricsListQuery as $metricsListRow)
        {
            $metricsList[$metricsListRow['default_name']] = $metricsListRow['metric_id'];
        }

        // Query to get the location and package details based on Subscription ID
        $subscriptionPackageQuery = SubscriptionPackage::select(DB::raw('*'))
                            ->where('subscription_package.subscription_id', '=', $subscriptionId)
                            ->join('subscription_locations', 'subscription_locations.Subscription_Idx', '=', 'subscription_package.subscription_id')
                            ->leftjoin('jx_packages as jp', 'jp.templateid', '=', 'subscription_package.template_id')
                            ->get()->toArray();

        if (empty($subscriptionPackageQuery)) 
        {
            return response('No package associated to given Subscription Id', 404);
        }

        $fromDate = date('Y-m-d H:i:s', strtotime($request->input('from')));
        $toDate = date('Y-m-d 23:59:59', strtotime($request->input('to')));
        $fromDateTime = new \DateTime($fromDate);
        $toDateTime = new \DateTime($toDate);
        $diff = $toDateTime->diff($fromDateTime)->format("%a");

        if($diff > '6')
        {
            return response('Date range cannot exceed 7 days', 400);
        }

        $cuttoffMetrics = [];
        foreach ($subscriptionPackageQuery as $subscriptionPackage)
        {
            $locationId = $subscriptionPackage['Location_Idx'];
            $packageId = $subscriptionPackage['package_id'];
            $cuttoffMetrics['downlinkcir'] = $subscriptionPackage['downlinkcir'];
            $cuttoffMetrics['uplinkcir'] = $subscriptionPackage['uplinkcir'];
            $cuttoffMetrics['downlinkmir'] = $subscriptionPackage['downlinkmir'];
            $cuttoffMetrics['uplinkmir'] = $subscriptionPackage['uplinkmir'];
        }

        // Query to get the CDR data from DB based on the package ID
        $jxCDRQuery = JxCDR::select(DB::raw('jx_cdr_raw.*'))
            ->where('jx_cdr_raw.package-id', '=', $packageId);

        $lastUpdatedDate = JxCDR::query()->orderBy('timestamp', 'DESC')->first();

        // Query to get the Real Time data from DB based last updated timestamp from CDR data
        $jxRealtimeQuery = JxRealTime::select(DB::raw('jx_real_time_raw.*'))
            ->where('jx_real_time_raw.package-id', '=', $packageId)
            ->where('jx_real_time_raw.time', '>', $lastUpdatedDate['timestamp']);

        if ($request->has('metric')) 
        {
            $metricIdxs = explode(',', $request->input('metric'));
            $metrics = array();
            foreach($metricIdxs as $metricIdx)
            {
                $metricId = JxMetric::select(DB::raw('jx_metric_list.*'))
                       ->where('jx_metric_list.Idx', '=', $metricIdx)
                       ->pluck('metric_id');
                $metrics[] = $metricId;
            }
            $jxCDRQuery->whereIn('jx_cdr_raw.metric-id', $metrics);
            $jxRealtimeQuery->whereIn('jx_real_time_raw.metric-id', $metrics);
        }

        $jxCDRQuery->where('jx_cdr_raw.timestamp', '>=', $fromDate);
        $jxRealtimeQuery->where('jx_real_time_raw.time', '>=', $fromDate);
        $jxCDRQuery->where('jx_cdr_raw.timestamp', '<=', $toDate);
        $jxRealtimeQuery->where('jx_real_time_raw.time', '<=', $toDate);

        $output = $jxCDRQuery->orderBy('jx_cdr_raw.timestamp', 'desc')->get()->toArray();
        $realtimeData = $jxRealtimeQuery->orderBy('jx_real_time_raw.time', 'desc')->get()->toArray();

        $result = array();
        $excludeNames = ['latitude', 'longitude'];
        // Appending Realtime data to CDR data to make sure the data is up to date
        if(!empty($realtimeData))
        {
            foreach($realtimeData as $realtimeValue)
            {
                $name = array_search($realtimeValue['metric-id'],$metricsList);
                $timestamp = $realtimeValue['time'];
                $result[$timestamp]['timestamp'] = $timestamp;
                $result[$timestamp]['metrics'][$name] = !in_array($name, $excludeNames)?round($realtimeValue['value'],2):$realtimeValue['value'];
            }
        }

        foreach($output as $value)
        {
            $name = array_search($value['metric-id'],$metricsList);
            $timestamp = $value['timestamp'];
            $result[$timestamp]['timestamp'] = $timestamp;
            $result[$timestamp]['metrics'][$name] = ! in_array($name, $excludeNames)?round($value['value'],2):$value['value'];
        }

        $metricsArray = array_values($result);

        $metricsOuput = array();

        foreach($metricsArray as $metricArray)
        {
            foreach($metricsList as $metricKey => $metricList)
            {
                if(array_key_exists($metricKey, $metricArray['metrics']) == false)
                {
                    $metricArray['metrics'][$metricKey] = '0';
                }
            }
            unset($metricArray['metrics']['0']);
            $metricsOuput[] = $metricArray;
        }
       // if(!empty($metricsOuput))
       // {
            return array(
                'lastUpdatedAt' => isset($metricsOuput[0]['timestamp'])?$metricsOuput[0]['timestamp']:date('Y-m-d H:i:s'),
                'locationId' => $locationId,
                'downlinkcircutoff' => $cuttoffMetrics['downlinkcir'],
                'downlinkmircutoff' => $cuttoffMetrics['downlinkmir'],
                'uplinkcircutoff' => $cuttoffMetrics['uplinkcir'],
                'uplinkmircutoff' => $cuttoffMetrics['uplinkmir'],
                'data' => $metricsOuput
            );
       // }
        }
    /**
     * @SWG\Get(
     *     path="/fleet-location",
     *     summary="/last location of fleet",
     *     tags={"jx"},
     *     description="This resource is dedicated to querying data around fleet location.",
     *     operationId="getLastLocation",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         name="customerId",
     *         in="query",
     *         description="Filter by Customer Id",
     *         required=true,
     *         type="integer",
     *         @SWG\Items(type="integer"),
     *     ),
     *     @SWG\Parameter(
     *         name="locationId",
     *         in="query",
     *         description="Filter by Location Id",
     *         required=false,
     *         type="integer",
     *         @SWG\Items(type="integer"),
     *     ),
     *     @SWG\Parameter(
     *         name="hwContactId",
     *         in="query",
     *         description="Filter by honeywell contact Id",
     *         required=false,
     *         type="integer",
     *         @SWG\Items(type="integer"),
     *     ),
     *     @SWG\Parameter(
     *         name="from",
     *         in="query",
     *         description="From (timestamp)",
     *         required=false,
     *         type="string",
     *         format="date-time",
     *     ),
     *     @SWG\Parameter(
     *         name="to",
     *         in="query",
     *         description="To (Timestamp, example:  2017-03-05 13:10:10)",
     *         required=false,
     *         type="string",
     *         format="date-time",
     *     ),
     * @SWG\Response(
     *         response=200,
     *         description="Success"
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
    public function getLastLocation(Request $request)
    {
        $hwContactId = null;

        if($request->has('locationId'))
        {
            $locationId = $request->input('locationId');
        }

        if($request->has('hwContactId'))
        {
            $hwContactId = $request->input('hwContactId');
        }

        if( !$request->has('from') && !$request->has('to') )
        {
            $locationsQuery = DB::table('jx_recent_location AS jfl')
                            ->join('location AS l', 'l.Idx', '=', 'jfl.Location_Idx')
                            ->select(DB::raw('jfl.latitude, jfl.longitude, jfl.Timestamp, l.Location as tailNumber, l.Idx AS Location_Idx'));

            $locationsQuery->groupBy('jfl.Subscription_Idx')
                           ->orderBy('jfl.Timestamp', 'DESC');
        }
        else
        {
            ini_set("memory_limit","64M");

            $locationsQuery = DB::table('jx_location_history AS jfl')
                                ->join('location AS l', 'l.Idx', '=', 'jfl.Location_Idx')
                                ->select(DB::raw('jfl.latitude, jfl.longitude, jfl.Timestamp, l.Location as tailNumber, l.Idx AS Location_Idx'));

            if($request->has('from'))
            {
                $locationsQuery->where('jfl.Timestamp', '>=', $request->input('from'));
            }

            if($request->has('to'))
            {
                $locationsQuery->where('jfl.Timestamp', '<=', $request->input('to'));
            }

            $locationsQuery->groupBy('jfl.Location_Idx')
                            ->groupBy('jfl.Timestamp')
                            ->orderBy('jfl.Timestamp', 'DESC');
        }        

        if($request->has('customerId'))
        {
            $customerId = $request->input('customerId');
            $customer = Customer::find($customerId);
            if( $customer->Is_Management_Company == 'Y' )
            {
                $locationsQuery->whereIn('customerId', function($query) use($request) {
                    $query->select('Customer_Idx')
                        ->from('mgmt_managed_companies')
                        ->where('Management_Customer_Idx', $request->input('customerId'));
                });

                $locationsQuery->whereIn('locationId', function($query) use($request) {
                    $query->select('Location_Idx')
                        ->from('mgmt_managed_locations')
                        ->where('Management_Customer_Idx', $request->input('customerId'));
                });
            }
            else
            {
                $locationsQuery->where('Customer_Idx', '=', $customerId);
            }
        }
        else
        {
            return response('Customer Id is required', 400);
        }

        if(isset($locationId))
        {
            $locationsQuery->where('Location_Idx','=', $locationId);
        }

        if (! is_null($hwContactId)) {
            $locationsQuery->join('group_locations', 'group_locations.Location_Idx', '=', 'jx_fleet_location.Location_Id')
                ->join('groups', 'groups.Group_Idx', '=', 'group_locations.Group_Idx')
                ->join('contact_groups', 'contact_groups.Group_Idx', '=', 'groups.Group_Idx')
                ->join('contact', 'contact_groups.Contact_Idx', '=', 'contact.Idx')
                ->where('contact.Honeywell_Id', 'LIKE', $request->input('hwContactId'))
                ->whereRaw('( NOW() BETWEEN IFNULL(contact_groups.Start_Date, NOW()) AND IFNULL(contact_groups.End_Date, NOW()) )')
                ->whereRaw('( NOW() BETWEEN IFNULL(group_locations.Start_Date, NOW()) AND IFNULL(group_locations.End_Date, NOW()) )');
        }

        $metrics = $locationsQuery->get();

        $data = [];
        if(count($metrics) > 0)
        {
            foreach($metrics as $metricsData)
            {
                $data[] = [
                    'timestamp' => $metricsData->Timestamp,
                    'latitude' => $metricsData->latitude,
                    'longitude' => $metricsData->longitude,
                    'locationId' => $metricsData->Location_Idx,
                    'tailNumber' => $metricsData->tailNumber
                ];
            }    
        }

        return [
            'customerId' => $customerId,
            'hwContactId' => $hwContactId,
            'data' => $data
        ];
    }
}
