<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Models\AircraftModel;
use App\Http\Models\Location;
use App\Http\Models\Service;
use App\Http\Models\System;
use App\Http\Models\SystemLocationMapping;
use App\Http\Models\SystemCustomerMapping;
use App\Http\Models\MonthlyCallDataRecord;
use App\Http\Models\GoDirectAccess;
use App\Http\Models\GoDirectActivation;
use App\Http\Models\DailyCallLogs;
use App\Http\Models\ManagementLocations;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Models\ServicesMaster;
use Illuminate\Support\Facades\DB;

class ExportDataController extends Controller
{
    /**
     * @SWG\Get(
     *     path="/export/aircraft-models",
     *     summary="/export/aircraft-models resource",
     *     tags={"export"},
     *     description="This resource is dedicated to export Aircraft Models with refrence to lastSyncDate for SIMS.",
     *     operationId="listAllAircraftModels",
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
     *         description="LastSyncDate is used to export data later than the date given e.g: 2017-03-05 13:10:10, SIMS is reading the DBMan data with LastSyncDate as reference",
     *         required=true,
     *         type="string",
     *         format="date-time",
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
     *         description="Not Found",
     *     ),
     *     @SWG\Response(
     *         response="405",
     *         description="Method Not Allowed",
     *     ),
     *    @SWG\Response(
     *          response="500",
     *         description="Internal Server Error",
     *     ),
     *    @SWG\Response(
     *         response="503",
     *         description="Service unavailable",
     *     )
     *
     * ),
     */
    
    public function listAllAircraftModels(Request $request)
    {
        $this->validate($request, [
                'lastSyncDate' => 'date_format:Y-m-d H:i:s|regex:/[0-9]{4}\-/|required',
        ]);
        
        $airCraftModelQuery=AircraftModel::query()->where('Updated_On', '>', $request->input('lastSyncDate'));
    
        return $airCraftModelQuery->paginate(intval($request->input('page_size', 50))); 
    }
    
    /**
     * @SWG\Get(
     *     path="/export/locations",
     *     summary="/export/locations resource",
     *     tags={"export"},
     *     description="This resource is dedicated to export Locations with refrence to lastSyncDate for SIMS",
     *     operationId="listAllLocations",
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
     *         description="LastSyncDate is used to export data later than the date given e.g: 2017-03-05 13:10:10, SIMS is reading the DBMan data with LastSyncDate as reference",
     *         required=true,
     *         type="string",
     *         format="date-time",
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
     *         description="Not Found",
     *     ),
     *      @SWG\Response(
     *         response="405",
     *         description="Method Not Allowed",
     *     ),
     *    @SWG\Response(
     *          response="500",
     *         description="Internal Server Error",
     *     ),
     *    @SWG\Response(
     *         response="503",
     *         description="Service unavailable",
     *     )
     *
     * ),
     */
    public function listAllLocations(Request $request)
    {
        $this->validate($request, [
                'lastSyncDate' => 'date_format:Y-m-d H:i:s|regex:/[0-9]{4}\-/|required',
        ]);
        
        $locationsQuery = Location::query()->where('Updated_On', '>', $request->input('lastSyncDate'));
    
        return $locationsQuery->groupBy('location.Idx')->paginate(intval($request->input('page_size', 50)));
    }
    
    /**
     * @SWG\Get(
     *     path="/export/services",
     *     summary="/export/services resource",
     *     tags={"export"},
     *     description="This resource is dedicated to export Services with refrence to lastSyncDate for SIMS.",
     *     operationId="listServices",
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
     *         description="LastSyncDate is used to export data later than the date given e.g: 2017-03-05 13:10:10, SIMS is reading the DBMan data with LastSyncDate as reference",
     *         required=true,
     *         type="string",
     *         format="date-time",
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
     *         description="Not Found",
     *     ),
     *     @SWG\Response(
     *         response="405",
     *         description="Method Not Allowed",
     *     ),
     *    @SWG\Response(
     *          response="500",
     *         description="Internal Server Error",
     *     ),
     *    @SWG\Response(
     *         response="503",
     *         description="Service unavailable",
     *     )
     * ),
     */
    
    public function listServices(Request $request)
    {
        $this->validate($request, [
                'lastSyncDate' => 'date_format:Y-m-d H:i:s|regex:/[0-9]{4}\-/|required',
        ]);
        
        $service = Service::query()->where('Updated_On', '>', $request->input('lastSyncDate'));
    
        return $service->paginate(intval($request->input('page_size', 50)));
    
    }
    
    /**
     * @SWG\Get(
     *     path="/export/systems",
     *     summary="/export/systems resource",
     *     tags={"export"},
     *     description="This resource is dedicated to export systems with refrence to lastSyncDate for SIMS.",
     *     operationId="listallSystems",
     *     produces={"application/json"},
     *
     *   @SWG\Parameter(
     *         name="page",
     *         in="query",
     *         description="Filter by Page Number",
     *         required=false,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="page_size",
     *         in="query",
     *         description="Number of results Per Page",
     *         required=false,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="lastSyncDate",
     *         in="query",
     *         description="LastSyncDate is used to export data later than the date given e.g: 2017-03-05 13:10:10, SIMS is reading the DBMan data with LastSyncDate as reference",
     *         required=true,
     *         type="string",
     *         format="date-time",
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
     *         description="Not Found",
     *     ),
     *     @SWG\Response(
     *         response="405",
     *         description="Method Not Allowed",
     *     ),
     *    @SWG\Response(
     *          response="500",
     *         description="Internal Server Error",
     *     ),
     *    @SWG\Response(
     *         response="503",
     *         description="Service unavailable",
     *     )
     * ),
     */
    public function listallSystems(Request $request )
    {
        $this->validate($request, [
                'lastSyncDate' => 'date_format:Y-m-d H:i:s|regex:/[0-9]{4}\-/|required',
        ]);
        
        $systemsQuery = System::query()->where('Updated_On', '>', $request->input('lastSyncDate'));
    
        return $systemsQuery->groupBy('system.Idx')->paginate(intval($request->input('page_size', 50)));
    }
    
    /**
     * @SWG\Get(
     *     path="/export/system-locations",
     *     summary="/export/system-locations resource",
     *     tags={"export"},
     *     description="This resource is dedicated to export SystemLocations with refrence to lastSyncDate for SIMS.",
     *     operationId="systemLocations",
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
     *         description="LastSyncDate is used to export data later than the date given e.g: 2017-03-05 13:10:10, SIMS is reading the DBMan data with LastSyncDate as reference",
     *         required=true,
     *         type="string",
     *         format="date-time",
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
     *         description="Not Found",
     *     ),
     *     @SWG\Response(
     *         response="405",
     *         description="Method Not Allowed",
     *     ),
     *    @SWG\Response(
     *          response="500",
     *         description="Internal Server Error",
     *     ),
     *    @SWG\Response(
     *         response="503",
     *         description="Service unavailable",
     *     )
     * ),
     */
    public function systemLocations(Request $request)
    {
        $this->validate($request, [
                'lastSyncDate' => 'date_format:Y-m-d H:i:s|regex:/[0-9]{4}\-/|required',
        ]);
        
        $systemlocationQuery = SystemLocationMapping::query()->where('Updated_On', '>', $request->input('lastSyncDate'));
    
        return $systemlocationQuery->paginate(intval($request->input('page_size', 50)));
    
    }
    
    /**
     * @SWG\Get(
     *     path="/export/system-customers",
     *     summary="/export/system-customers resource",
     *     tags={"export"},
     *     description="This resource is dedicated to export SystemCustomer with refrence to lastSyncDate for SIMS.",
     *     operationId="systemCustomers",
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
     *         description="LastSyncDate is used to export data later than the date given e.g: 2017-03-05 13:10:10, SIMS is reading the DBMan data with LastSyncDate as reference",
     *         required=true,
     *         type="string",
     *         format="date-time",
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
     *         description="Not Found",
     *     ),
     *     @SWG\Response(
     *         response="405",
     *         description="Method Not Allowed",
     *     ),
     *    @SWG\Response(
     *          response="500",
     *         description="Internal Server Error",
     *     ),
     *    @SWG\Response(
     *         response="503",
     *         description="Service unavailable",
     *     )
     * ),
     */
    public function systemCustomers(Request $request)
    {
        $this->validate($request, [
                'lastSyncDate' => 'date_format:Y-m-d H:i:s|regex:/[0-9]{4}\-/|required',
        ]);
        
        $systemCustomerQuery = SystemCustomerMapping::query()->where('Updated_On', '>', $request->input('lastSyncDate'));
    
        return $systemCustomerQuery->paginate(intval($request->input('page_size', 50)));
    }
    
    /**
     * @SWG\Get(
     *     path="/export/monthly-logs",
     *     summary="/Monthly call logs resource",
     *     tags={"export"},
     *     description="This resource is dedicated to export Monthly Call Data Records with refrence to lastSyncDate for SIMS.",
     *     operationId="monthlyListCdr",
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
     *         description="LastSyncDate is used to export data later than the date given e.g: 2017-03-05 13:10:10, SIMS is reading the DBMan data with LastSyncDate as reference",
     *         required=true,
     *         type="string",
     *         format="date-time",
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
     *         description="Not Found",
     *     ),
     *     @SWG\Response(
     *         response="405",
     *         description="Method Not Allowed",
     *     ),
     *    @SWG\Response(
     *          response="500",
     *         description="Internal Server Error",
     *     ),
     *    @SWG\Response(
     *         response="503",
     *         description="Service unavailable",
     *     )
     *
     * ),
     */
    public function monthlyListCdr(Request $request)
    {
        //FIXME: Determine the maximum amount of memory to be allocated for serving the CDR requests.
        ini_set('memory_limit', '-1');
        
        $this->validate($request, [
                'lastSyncDate' => 'date_format:Y-m-d H:i:s|regex:/[0-9]{4}\-/|required',
        ]);
    
        $monthlyCallDataQuery=MonthlyCallDataRecord::query()->where('Updated_On', '>', $request->input('lastSyncDate'));
    
        return $monthlyCallDataQuery->paginate(intval($request->input('page_size', 50)));
    }
    
    /**
     * @SWG\Get(
     *     path="/export/godirect-access",
     *     summary="/GoDirect Access resource",
     *     tags={"export"},
     *     description="This resource is dedicated to export Go Direct Access Records with refrence to lastSyncDate for SIMS.",
     *     operationId="goDirectAccess",
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
     *         description="LastSyncDate is used to export data later than the date given e.g: 2017-03-05 13:10:10, SIMS is reading the DBMan data with LastSyncDate as reference",
     *         required=true,
     *         type="string",
     *         format="date-time",
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
     *         description="Not Found",
     *     ),
     *     @SWG\Response(
     *         response="405",
     *         description="Method Not Allowed",
     *     ),
     *    @SWG\Response(
     *          response="500",
     *         description="Internal Server Error",
     *     ),
     *    @SWG\Response(
     *         response="503",
     *         description="Service unavailable",
     *     )
     *
     * ),
     */
    public function goDirectAccess(Request $request)
    {
        //FIXME: Determine the maximum amount of memory to be allocated for serving the CDR requests.
        ini_set('memory_limit', '-1');
    
        $this->validate($request, [
                'lastSyncDate' => 'date_format:Y-m-d H:i:s|regex:/[0-9]{4}\-/|required',
        ]);
    
        $goDirectAccessQuery=GoDirectAccess::query()->where('Updated_On', '>', $request->input('lastSyncDate'));
    
        return $goDirectAccessQuery->paginate(intval($request->input('page_size', 50)));
    }
    /**
     * @SWG\Get(
     *     path="/export/gd-access-activation-logs",
     *     summary="/GoDirect Activation Deactivation resource",
     *     tags={"export"},
     *     description="This resource is dedicated to export GoDirect Activation Deactivation Records with refrence to lastSyncDate for SIMS.",
     *     operationId="goDirectActivationLogs",
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
     *         description="LastSyncDate is used to export data later than the date given e.g: 2017-03-05 13:10:10, SIMS is reading the DBMan data with LastSyncDate as reference",
     *         required=true,
     *         type="string",
     *         format="date-time",
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
     *         description="Not Found",
     *     ),
     *     @SWG\Response(
     *         response="405",
     *         description="Method Not Allowed",
     *     ),
     *    @SWG\Response(
     *          response="500",
     *         description="Internal Server Error",
     *     ),
     *    @SWG\Response(
     *         response="503",
     *         description="Service unavailable",
     *     )
     *
     * ),
     */
    public function goDirectActivationLogs(Request $request)
    {
        //FIXME: Determine the maximum amount of memory to be allocated for serving the CDR requests.
        ini_set('memory_limit', '-1');
    
        $this->validate($request, [
                'lastSyncDate' => 'date_format:Y-m-d H:i:s|regex:/[0-9]{4}\-/|required',
        ]);
    
        $goDirectActivationQuery=GoDirectActivation::query()->where('Updated_On', '>', $request->input('lastSyncDate'));
    
        return $goDirectActivationQuery->paginate(intval($request->input('page_size', 50)));
    }
    /**
     * @SWG\Get(
     *     path="/export/services-master",
     *     summary="/Services Master resource",
     *     tags={"export"},
     *     description="This resource is dedicated to export Services Master Records with refrence to lastSyncDate for SIMS.",
     *     operationId="servicesMaster",
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
     *         description="LastSyncDate is used to export data later than the date given e.g: 2017-03-05 13:10:10, SIMS is reading the DBMan data with LastSyncDate as reference",
     *         required=true,
     *         type="string",
     *         format="date-time",
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
     *         description="Not Found",
     *     ),
     *     @SWG\Response(
     *         response="405",
     *         description="Method Not Allowed",
     *     ),
     *    @SWG\Response(
     *          response="500",
     *         description="Internal Server Error",
     *     ),
     *    @SWG\Response(
     *         response="503",
     *         description="Service unavailable",
     *     )
     *
     * ),
     */
    public function servicesMaster(Request $request)
    {
        $this->validate($request, [
                'lastSyncDate' => 'date_format:Y-m-d H:i:s|regex:/[0-9]{4}\-/|required',
        ]);
    
        $servviceMasterQuery=ServicesMaster::query()->where('Updated_On', '>', $request->input('lastSyncDate'));
    
        return $servviceMasterQuery->paginate(intval($request->input('page_size', 50)));
    }

    /**
     * @SWG\Get(
     *     path="/export/daily-call-logs",
     *     summary="/Daily call logs resource",
     *     tags={"export"},
     *     description="This resource is dedicated to export Daily Call Data Records with refrence to lastSyncDate for SIMS.",
     *     operationId="dailyCallLogs",
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
     *         description="LastSyncDate is used to export data later than the date given e.g: 2017-03-05 13:10:10, SIMS is reading the DBMan data with LastSyncDate as reference",
     *         required=true,
     *         type="string",
     *         format="date-time",
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
     *         description="Not Found",
     *     ),
     *     @SWG\Response(
     *         response="405",
     *         description="Method Not Allowed",
     *     ),
     *    @SWG\Response(
     *          response="500",
     *         description="Internal Server Error",
     *     ),
     *    @SWG\Response(
     *         response="503",
     *         description="Service unavailable",
     *     )
     *
     * ),
     */
    public function dailyCallLogs(Request $request)
    {
        //FIXME: Determine the maximum amount of memory to be allocated for serving the CDR requests.
        ini_set('memory_limit', '-1');
    
        $this->validate($request, [
            'lastSyncDate' => 'date_format:Y-m-d H:i:s|regex:/[0-9]{4}\-/|required',
        ]);
    
        $dailyCallDataQuery=DailyCallLogs::query()->where('Updated_On', '>', $request->input('lastSyncDate'));
    
        return $dailyCallDataQuery->paginate(intval($request->input('page_size', 50)));
    }

    /**
     * @SWG\Get(
     *     path="/export/management-company",
     *     summary="/Management Company resource",
     *     tags={"export"},
     *     description="This resource is dedicated to export Management Company Records with refrence to lastSyncDate for SIMS.",
     *     operationId="managementCompany",
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
     *         description="LastSyncDate is used to export data later than the date given e.g: 2017-03-05 13:10:10, SIMS is reading the DBMan data with LastSyncDate as reference",
     *         required=true,
     *         type="string",
     *         format="date-time",
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
     *         description="Not Found",
     *     ),
     *     @SWG\Response(
     *         response="405",
     *         description="Method Not Allowed",
     *     ),
     *    @SWG\Response(
     *          response="500",
     *         description="Internal Server Error",
     *     ),
     *    @SWG\Response(
     *         response="503",
     *         description="Service unavailable",
     *     )
     * ),
     */
    public function managementCompany(Request $request)
    {
        $this->validate($request, [
            'lastSyncDate' => 'date_format:Y-m-d H:i:s|regex:/[0-9]{4}\-/|required',
        ]);

        $managementCompanyQuery=ManagementLocations::query()->where('Updated_On', '>', $request->input('lastSyncDate'));

        return $managementCompanyQuery->paginate(intval($request->input('page_size', 50)));
    }
}