<?php
namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Models\CabinBillingHistory;
use App\Http\Models\Customer;
use App\Http\Models\Location;
use App\Http\Models\LocationSuperTableMapping;
use App\Http\Models\Network;
use App\Http\Models\OndTollFreeMapping;
use App\Http\Models\Task;
use App\Http\Services\DBManService;
use App\Http\Services\RadminService;
use App\Http\Services\Util;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\ServiceRequest;
use App\Http\Models\VAS;
use App\Http\Models\LocationVAS;
use App\Http\Models\CustomerLocation;

class LocationController extends Controller
{
    /**
     * @SWG\Get(
     *     path="/locations",
     *     summary="Get Locations List",
     *     tags={"locations"},
     *     description="This resource is dedicated to querying data around Locations (Aircraft, Ship etc)",
     *     operationId="listLocations",
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
     *         name="customerId",
     *         in="query",
     *         description="Filter Locations by Customer Id",
     *         required=false,
     *         type="integer",
     *         @SWG\Items(type="integer"),
     *         collectionFormat="csv"
     *     ),
     *     @SWG\Parameter(
     *         name="hwContactId",
     *         in="query",
     *         description="Filter Locations by Honeywell Contact Id",
     *         required=false,
     *         type="integer",
     *         @SWG\Items(type="string"),
     *         collectionFormat="csv"
     *     ),
     *     @SWG\Parameter(
     *         name="subscriptionId",
     *         in="query",
     *         description="Filter Locations by Subscription Id",
     *         required=false,
     *         type="integer",
     *         collectionFormat="csv"
     *     ),
     *     @SWG\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filter Locations by Type, e.g. AIRCRAFT",
     *         required=false,
     *         type="string",
     *         enum={"AIRCRAFT", "SHIP", "FIXED", "PORTABLE", "VEHICLE", "STOCK"},
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Success",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(ref="#/definitions/Location")
     *         ),
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
    public function listLocations(Request $request)
    {
        $locationsQuery = Location::with(['aircraftModel', 'aircraftModel.make']);
        $locationsQuery->select(DB::raw('location.*'));

        $locationsQuery->join('system_location_mapping as slm', 'slm.Location_Idx', '=', 'location.Idx')
            ->join('system', 'system.Idx', '=', 'slm.System_Idx')
            ->join('system_customer_mapping as scm', 'system.Idx', '=', 'scm.System_Idx')
            ->join('customer', 'customer.Idx', '=', 'scm.Customer_Idx')
            ->whereRaw('(scm.Start_Date < now() AND ( scm.End_Date IS NULL OR scm.End_Date > now() ))')
            ->whereRaw('(slm.Start_Date < now() AND ( slm.End_Date IS NULL OR slm.End_Date > now() ))')
            ->orderBy('location.Idx');

        if ($request->has('customerId')) {
            $customer = Customer::find($request->input('customerId'));
            if( $customer->Is_Management_Company == 'Y' )
            {
                $locationsQuery->whereIn('customer.Idx', function($query) use($request) {
                    $query->select('Customer_Idx')
                        ->from('mgmt_managed_companies')
                        ->where('Management_Customer_Idx', $request->input('customerId'));
                });

                $locationsQuery->whereIn('location.Idx', function($query) use($request) {
                    $query->select('Location_Idx')
                        ->from('mgmt_managed_locations')
                        ->where('Management_Customer_Idx', $request->input('customerId'));
                });
            }
            else
            {
                $locationsQuery->where('customer.Idx', '=', $request->input('customerId'));
            }
        }

        if ($request->has('type')) {
            $locationsQuery->where('location.Location_Type', 'LIKE', $request->input('type'));
        }

        if ($request->has('subscriptionId')) {
            $locationsQuery->join('all_subscription_locations as sl', 'sl.Location_Idx', '=', 'location.Idx')
                ->join('all_subscriptions', 'all_subscriptions.Subscription_Idx', '=', 'sl.Subscription_Idx')
                ->where('all_subscriptions.Subscription_Idx', '=', $request->input('subscriptionId'))
                ->whereRaw('(NOW() BETWEEN IFNULL(sl.Start_Date, NOW()) AND IFNULL(sl.End_Date, NOW()) )');
        }

        if ($request->has('hwContactId')) {
            $locationsQuery->join('group_locations', 'group_locations.Location_Idx', '=', 'location.Idx')
                ->join('groups', 'groups.Group_Idx', '=', 'group_locations.Group_Idx')
                ->join('contact_groups', 'contact_groups.Group_Idx', '=', 'groups.Group_Idx')
                ->join('contact', 'contact_groups.Contact_Idx', '=', 'contact.Idx')
                ->where('contact.Honeywell_Id', 'like', $request->input('hwContactId'))
                ->whereRaw('( NOW() BETWEEN IFNULL(contact_groups.Start_Date, NOW()) AND IFNULL(contact_groups.End_Date, NOW()) )')
                ->whereRaw('( NOW() BETWEEN IFNULL(group_locations.Start_Date, NOW()) AND IFNULL(group_locations.End_Date, NOW()) )');
        }

        return $locationsQuery->groupBy('location.Idx')->paginate(intval($request->input('page_size', 50)));
    }
    /**
     * @SWG\Get(
     *     path="/locations/{locationId}",
     *     summary="Get Location Details",
     *     tags={"locations"},
     *     description="Returns the Location Information based on the Location Id.",
     *     operationId="listLocationWithId",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         name="locationId",
     *         in="path",
     *         description="Location ID",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="customerId",
     *         in="query",
     *         description="Filter by Customer Id",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Success",
     *         @SWG\Schema(
     *          @SWG\Property(
     *          property="System",
     *          type="array",
     *           @SWG\Items(ref="#/definitions/Location"),
     *          ),
     *         ),
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Invalid tag value",
     *     ),
     *     @SWG\Response(
     *         response="401",
     *         description="Unauthorized access",
     *     )
     * ),
     */
    public function listLocationWithId(Request $request, $locationId)
    {
        $locationQuery = Location::with(['aircraftModel.make', 'routers']);
        $locationQuery->distinct()
            ->select(DB::raw('location.*'))->with(['systems', 'systems.terminals'])
            ->join('system_location_mapping as slm', 'slm.Location_Idx', '=', 'location.Idx')
            ->join('system', 'system.Idx', '=', 'slm.System_Idx')
            ->join('system_customer_mapping as scm', 'system.Idx', '=', 'scm.System_Idx')
            ->join('customer', 'customer.Idx', '=', 'scm.Customer_Idx')
            ->leftJoin('aircraft_model', 'location.Model_Idx', '=', 'aircraft_model.Model_Idx')
            ->leftJoin('aircraft_make', 'aircraft_model.Make_Idx', '=', 'aircraft_make.Make_Idx')
            ->whereRaw('(scm.Start_Date < now() AND ( scm.End_Date IS NULL OR scm.End_Date > now() ))')
            ->whereRaw('(slm.Start_Date < now() AND ( slm.End_Date IS NULL OR slm.End_Date > now() ))')
            ->where('location.Idx', '=', $locationId);

        if ($request->has('customerId')) {
            $customer = Customer::find($request->input('customerId'));
            if( $customer->Is_Management_Company == 'Y' )
            {
                $locationQuery->whereIn('customer.Idx', function($query) use($request) {
                    $query->select('Customer_Idx')
                        ->from('mgmt_managed_companies')
                        ->where('Management_Customer_Idx', $request->input('customerId'));
                });

                $locationQuery->whereIn('location.Idx', function($query) use($request) {
                    $query->select('Location_Idx')
                        ->from('mgmt_managed_locations')
                        ->where('Management_Customer_Idx', $request->input('customerId'));
                });
            }
            else
            {
                $locationQuery->where('customer.Idx', '=', $request->input('customerId'));
            }
        }

        $location = $locationQuery->first();

        if ($location == null) {
            return response('Invalid Location ID', 404);
        }

        $location = $location->toArray();

        $locationServicesQuery = DB::table('location')
        ->select(DB::raw("distinct (case when st.Idx is not null then 'ACTIVE' else 'INACTIVE' end) as oneNumberDial, " .
                "( CASE WHEN cabinbilling.Status = 'Error_Enabling' THEN 'Disabled' " .
                "   WHEN cabinbilling.Status = 'Error_Disabling' THEN 'Enabled' ELSE cabinbilling.Status END) AS sbbCabinBilling "))
                ->join('system_location_mapping as slm', 'slm.Location_Idx', '=', 'location.Idx')
                ->join('system', 'system.Idx', '=', 'slm.System_Idx')
                ->leftJoin('terminal as t', function($join){
                    $join->on('system.Idx', '=', 't.System_Idx')
                    ->on('t.Category', '=', DB::raw("'SBB'"))
                    ->on('t.Activation_Date', '<=', DB::raw("NOW()"))
                    ->on(DB::raw('IFNULL(t.Deactivation_Date, NOW())'), '>=', DB::raw("NOW()"));
                })
                ->leftJoin('cabinbilling', 't.Idx', '=', 'cabinbilling.Terminal_Idx')

                ->leftJoin('location_superTable_mapping as lsm', function($join){
                    $join->on('location.Idx', '=', 'lsm.Location_Idx')
                    ->on(DB::raw('IFNULL(lsm.End_Date, NOW())'), '>=', DB::raw("NOW()"));
                })
                ->leftJoin('supertable as st', function($join){
                    $join->on('lsm.SuperTable_Idx', '=', 'st.Idx')
                    ->on('st.Type', '=', DB::raw("'OneNumberDial'"));
                })
                ->whereRaw('(slm.Start_Date < now() AND ( slm.End_Date IS NULL OR slm.End_Date > now() ))')
                ->where('location.Idx', '=', $locationId)
                ->orderBy('sbbCabinBilling', 'desc');
                

                $services = $locationServicesQuery->first();

                $locationJxCBQuery = DB::table( 'location' )->select( 
                        DB::raw( 
                                "( CASE WHEN cabinbilling.Status = 'Error_Enabling' THEN 'Disabled' " .
                                         "   WHEN cabinbilling.Status = 'Error_Disabling' THEN 'Enabled' ELSE cabinbilling.Status END) AS jxCabinBilling " ) )
                    ->join( 'system_location_mapping as slm', 'slm.Location_Idx', '=', 'location.Idx' )
                    ->join( 'system', 'system.Idx', '=', 'slm.System_Idx' )
                    ->leftJoin( 'terminal as t', 
                        function ($join)
                        {
                            $join->on( 'system.Idx', '=', 't.System_Idx' )
                                ->on( 't.Category', '=', DB::raw( "'JX'" ) )
                                ->on( 't.Activation_Date', '<=', DB::raw( "NOW()" ) )
                                ->on( DB::raw( 'IFNULL(t.Deactivation_Date, NOW())' ), '>=', DB::raw( "NOW()" ) );
                        } )
                    ->leftJoin( 'cabinbilling', 't.Idx', '=', 'cabinbilling.Terminal_Idx' )
                    ->whereRaw( '(slm.Start_Date < now() AND ( slm.End_Date IS NULL OR slm.End_Date > now() ))' )
                    ->where( 'location.Idx', '=', $locationId )
                    ->orderBy( 'jxCabinBilling', 'desc' );
                
				
				$locationJxXiplinkQuery = DB::table( 'location' )->select(
                        DB::raw(
                                "( CASE WHEN t.Xiplink = 'false' THEN 'Off' " .
                                "   WHEN t.Xiplink = 'true' THEN 'On' ELSE t.Xiplink END) AS jxXiplink " ) )
                                ->join( 'system_location_mapping as slm', 'slm.Location_Idx', '=', 'location.Idx' )
                                ->join( 'system', 'system.Idx', '=', 'slm.System_Idx' )
                                ->leftJoin( 'terminal as t',
                                        function ($join)
                                        {
                                            $join->on( 'system.Idx', '=', 't.System_Idx' )
                                            ->on( 't.Category', '=', DB::raw( "'JX'" ) )
                                            ->on( 't.Activation_Date', '<=', DB::raw( "NOW()" ) )
                                            ->on( DB::raw( 'IFNULL(t.Deactivation_Date, NOW())' ), '>=', DB::raw( "NOW()" ) );
                    } )
                    ->whereRaw( '(slm.Start_Date < now() AND ( slm.End_Date IS NULL OR slm.End_Date > now() ))' )
                    ->where( 'location.Idx', '=', $locationId )
                    ->orderBy( 'jxXiplink', 'desc' );

                
                //FIXME: Move this to a single view in DB
                $locationFilteringQuery = DB::table('location')
                ->select(DB::raw('IF(IFNULL(MIN(rff.Default_Firewall_Filter_YN_Flag), "Y") = "N", "ACTIVE", "INACTIVE") as goDirectFilters'))
                ->join('system_location_mapping as slm', 'slm.Location_Idx', '=', 'location.Idx')
                ->join('system', 'system.Idx', '=', 'slm.System_Idx')
                ->leftJoin('terminal as t', function($join){
                    $join->on('system.Idx', '=', 't.System_Idx')
                    ->whereIn('t.Category', ['SBB', 'JX'])
                    //->on('t.Category', '=', DB::raw('"SBB"'))
                    ->on('t.Activation_Date', '<=', DB::raw("NOW()"))
                    ->on(DB::raw('IFNULL(t.Deactivation_Date, NOW())'), '>=', DB::raw("NOW()"));
                })
                ->leftJoin('radmin_firewall_filter as rff', 'rff.Firewall_Filter_Idx', '=', 't.Firewall_Filter_Idx')
                ->whereRaw('(slm.Start_Date < now() AND ( slm.End_Date IS NULL OR slm.End_Date > now() ))')
                ->where('location.Idx', '=', $locationId)
                //->whereIn('t.Category', ['SBB', 'JX'])
                ->groupBy('location.Idx');
			
				
                $location['services'] = [
                    'goDirectFiltering' => $locationFilteringQuery->first()->goDirectFilters,
                    'oneNumberDial' => $services->oneNumberDial,
                    'sbbCabinBilling' => $services->sbbCabinBilling,
                    'jxCabinBilling' => $locationJxCBQuery->first()->jxCabinBilling,
					'jxXiplink' => $locationJxXiplinkQuery->first()->jxXiplink
                ];
                
                return $location;
    }

    /**
     * @SWG\Put(
     *     path="/locations/{locationId}/cabinbilling",
     *     summary="Enable/Disable Cabin Billing",
     *     tags={"locations"},
     *     description="Enable/Disable a location's Cabin Billing status.",
     *     operationId="setCabinBilling",
     *     consumes={"form/x-www-form-urlencoded"},
     *     produces={"application/json"},
     *
     *   @SWG\Parameter(
     *         name="action",
     *         in="query",
     *         description="Cabin Billing Action to be performed.",
     *         required=true,
     *         type="string",
     *         enum={"Enable", "Disable"}
     *     ),
     *     @SWG\Parameter(
     *         name="updatedBy",
     *         in="query",
     *         description="Honeywell ID of the user, who is updating Cabin Billing.",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="locationId",
     *         in="path",
     *         description="Location Id, for which Cabin billing to be updated",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="category",
     *         in="query",
     *         description="Terminal Category",
     *         required=true,
     *         type="string",
     *        enum={"SBB", "JX"},
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Success",
     *         @SWG\Schema(
     *           @SWG\Property(
     *              property="status",
     *              type="string",
     *              enum={"success", "failed"}
     *           ),
     *           @SWG\Property(
     *             property="message",
     *             type="string"
     *           )
     *         ),
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Bad request",
     *     ),
     *     @SWG\Response(
     *         response="401",
     *         description="Unauthorized access",
     *     ),
     *     @SWG\Response(
     *         response="422",
     *         description="Missing required parameter",
     *     ),
     * ),
     */
    public function setCabinBilling(Request $request, $locationId)
    {
        $action = $request->input('action');

        $category = strtoupper($request->input('category'));

        if ( $category != 'JX' && $category != 'SBB' )
        {
            return response()->json([
                'status' => 'failed',
                'message' => 'Invalid terminal category'
            ], 422);
        }

        $parameterNames = ['action', 'updatedBy'];
        $params = [$locationId];

        foreach($parameterNames as $parameter) {
            if( $request->has($parameter) ) {
                $params[] = $request->input($parameter);
            } else {
                return response()->json([
                    'status' => 'failed',
                    'message' => "Invalid request. Parameter $parameter is missing in request."
                ], 422);
            }
        }

        $cabinBilling = DB::table('cabin_billing_locations')->select('Status')
                            ->where('Location_Idx', '=', $locationId)
                            ->where('Category', '=', $category)
                            ->orderBy('Status', 'desc')
                            ->first();

        if(is_null($cabinBilling)) {
            return response()->json([
                'status' => 'failed',
                'message' => "Cabin billing is not activated for this aircraft. Please contact support."
            ], 400);
        }

        $cabinBillingStatus = $cabinBilling->Status;

        //Action will be Enable/Disable where as current status will be Enabled/Disabled.
        if($cabinBillingStatus == $request->input('action') . 'd') {
            return response()->json([
                'status' => 'failed',
                'message' => 'Cabin billing is already ' . strtolower($cabinBillingStatus)
            ], 400);
        }

        $activeNetworks = Network::where('Active_YN_Flag', '=', 'Yes')->get()->toArray();

        foreach( $activeNetworks as $network )
        {
            Log::info($network['name'] . ' is active. Updating cabin billing...');

            if( $network['name'] == 'S1 Legacy Network' )
            {
                if($category=='SBB')
                {
                    $sbbTerminals = DB::table('terminal')
                                        ->join('system', 'system.Idx', '=', 'terminal.System_Idx')
                                        ->join('system_location_mapping as slm', 'slm.System_Idx', '=', 'system.Idx')
                                        ->join('cabinbilling as cb', 'terminal.Idx', '=', 'cb.Terminal_Idx')
                                        ->whereRaw('(slm.Start_Date < now() AND ( slm.End_Date IS NULL OR slm.End_Date > now() ))')
                                        ->whereRaw('IFNULL(terminal.Deactivation_Date, NOW()) >= NOW()')
                                        ->where('slm.Location_Idx', '=', $locationId)
                                        ->where('terminal.Category', '=', 'SBB')
                                        ->where('terminal.Status', '=', 'ACTIVE')
                                        ->get();

                    if(count($sbbTerminals) > 0)
                    {  
                       $this->setCabinBillingInLegacyAPN( $params );
                    }
                }
            }

            if( $network['name'] == 'GoDirect Network' )
            {
                $radminService = new RadminService;

                try
                {
                    if( strcasecmp($action, 'enable') === 0 )
                    {
                        $radminService->enableCabinBilling($locationId, $request->input('updatedBy'), $category);
                    }

                    if( strcasecmp($action, 'disable') === 0 )
                    {
                        $radminService->disableCabinBilling($locationId, $request->input('updatedBy'), $category);
                    }
                }
                catch(\Exception $e)
                {
                    if( $network['isPreferredNetwork'] == 'Y' )
                    {
                        Log::error('GoDirect Network is active but cabin billing udpate failed.');
                    }
                }
            }
        }

        $cabinBillingStatus = DB::table('cabin_billing_locations')->select('Status')
                                ->where('Location_Idx', '=', $locationId)
                                ->where('Category', '=', $category)
                                ->orderBy('Status', 'desc')
                                ->first()->Status;

        if($cabinBillingStatus == $request->input('action') . 'd') {
            return response()->json([
                'status' => 'success',
                'message' => 'Cabin billing is now ' . strtolower($cabinBillingStatus)
            ], 200);
        } else {
            return response()->json([
                'status' => 'failed',
                'message' => 'Error occured while changing Cabin billing.'
            ], 500);
        }
    }

    private function setCabinBillingInLegacyAPN( $parameters )
    {
        //Data at which this request is being executed, in the format: "2016-11-02 08:58:09"
        $parameters[] = date("Y-m-d H:i:s");
        $output = '';
        exec(env('PYTHON_COMMAND') . ' ' . env('DBMAN_SCRIPTS_PATH') . DIRECTORY_SEPARATOR . 'CB_masterswitch.py "' . join($parameters, '" "') . '" 2>&1', $output);
        Log::info('Cabin billing output for Location ' . $parameters[0] . ': ' . join( PHP_EOL, $output ) );
    }

    /**
     * @SWG\Get(
     *     path="/locations/{locationId}/cabinbilling/history",
     *     summary="Location cabin billing history",
     *     tags={"locations"},
     *     description="This resource is dedicated to querying location cabin billing history.",
     *     operationId="listCabinBillingHistory",
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
     *         name="locationId",
     *         in="path",
     *         description="Filter by Location Id",
     *         required=true,
     *         type="integer",
     *         @SWG\Items(type="integer"),
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Success",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(ref="#/definitions/CabinBillingHistory")
     *         ),
     *     ),
     *     @SWG\Response(
     *         response="401",
     *         description="Unauthorized access",
     *     )
     *
     * ),
     */
    public function listCabinBillingHistory($locationId, Request $request)
    {
        $location = Location::find($locationId);

        if ($location == null) {
            return response('Location Not Found', 404);
        }

        return CabinBillingHistory::where('Location_Idx', '=', $locationId)->paginate(intval($request->input('page_size', 50)));
    }
    /**
     * @SWG\Delete(
     *     path="/location/{locationId}",
     *     summary="Deactivate Location",
     *     tags={"locations"},
     *     description="This resource is dedicated to deactivate location",
     *     operationId="deactivateLocation",
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
     *         name="deactivatedBy",
     *         in="query",
     *         description="decativated by - name",
     *         required=true,
     *         type="string"
     *     ),
     *    @SWG\Parameter(
     *         name="locationId",
     *         in="path",
     *         description="Location Id",
     *         required=true,
     *         type="integer",
     *         @SWG\Items(type="integer"),
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Success",
     *
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Bad request",
     *     ),
     *     @SWG\Response(
     *         response="401",
     *         description="Unauthorized access",
     *     )
     *
     * ),
     */
    public function deactivateLocation($locationId, Request $request)
    {
        try
        {
            $DBManService = new DBManService;
            $deactivatedBy = $request->input('deactivatedBy');
			$deactivationComment = $request->input('comment');
            $systems = DB::table('system')->select('system.Idx')
                ->join('system_location_mapping', function ($join) {
                    $join->on('system.Idx', '=', 'system_location_mapping.System_Idx');
                })
                ->join('location', function ($join) {
                    $join->on('location.Idx', '=', 'system_location_mapping.Location_Idx');
                })
                ->where('location.Idx', '=', $locationId)
                ->whereRaw('(system_location_mapping.End_Date > NOW() OR system_location_mapping.End_Date is NULL)')
                ->get();

            if (count($systems) > 0)
            {
                $task1 = new Task([
                    'task' => 'TASK_DEACTIVATE_LOCATION',
                    'data1' => $locationId,
                    'data2' => '',
                    'data3' => $deactivatedBy,
                    'createdOn' => \Carbon::now(),
                    'firstValidOn' => \Carbon::now(),
                    'status' => 'STATUS_NEW'
                ]);

                $task1->save();

                foreach ($systems as $system)
                {
                    $response = $DBManService->deactivateSystem($system->Idx, $deactivatedBy);
			/*if($response['message'] == 'System is not ACTIVE or not found')
			{
				throw new \Exception('System is not ACTIVE or not found');
			}*/
                }

                $ondServices = DB::table( 'location_superTable_mapping' )->select(
                        DB::raw(
                                'IF(((ond_toll_free_mapping.End_Date IS NULL AND ond_toll_free_mapping.Start_Date IS NOT NULL)
													  OR ond_toll_free_mapping.End_Date >= DATE_SUB(location_superTable_mapping.End_Date, INTERVAL 20 SECOND)), toll_free_phone_number.Toll_Free_Phone_Number, phone_pool.Phone_Number) AS Primary_Number,location_superTable_mapping.Idx as location_superTabl_id,ond_toll_free_mapping.Idx as ond_toll_free_mapping_id' ) )
				  ->join( 'supertable', 'location_superTable_mapping.SuperTable_Idx', '=', 'supertable.Idx' )
				  ->join( 'ond', 'supertable.Idx', '=', 'ond.SuperTable_Idx' )
				  ->join( 'phone_pool', 'ond.Phone_Pool_Idx', '=', 'phone_pool.Idx' )
				  ->leftJoin( 'ond_toll_free_mapping', 'ond.Idx', '=', 'ond_toll_free_mapping.Ond_Idx' )
				  ->leftJoin( 'toll_free_phone_number', 'ond_toll_free_mapping.Toll_Free_Phone_Number_Idx', '=', 'toll_free_phone_number.Idx' )
				  ->where( 'supertable.Type', '=', 'OneNumberDial' )
				  ->where( 'location_superTable_mapping.Location_Idx', '=', $locationId )
				  ->get();
                
                if (count($ondServices) > 0) {
                    $task2 = new Task([
                        'task' => 'TASK_DEACTIVATE_OND',
                        'data1' => $locationId,
                        'data2' => '',
                        'data3' => $deactivatedBy,
                        'createdOn' => \Carbon::now(),
                        'firstValidOn' => \Carbon::now(),
                        'status' => 'STATUS_NEW'
                    ]);

                    $task2->save();

                    Util::callTaskProcessor();
			for($i=0;$i<10;$i++)
			{
				if($task2->Status == "STATUS_DONE_OK")
				{
					break;
				}
				else
				{
					sleep(2);
				}
			}
		if($task2->Status == "STATUS_DONE_OK")
		{
			DB::beginTransaction();

		          /*DB::table('location_superTable_mapping')
		           ->join('supertable', 'location_superTable_mapping.SuperTable_Idx', '=', 'supertable.Idx')
		           ->join('ond', 'supertable.Idx', '=', 'ond.SuperTable_Idx')
		           ->leftJoin('ond_toll_free_mapping', 'ond.Idx', '=', 'ond_toll_free_mapping.Ond_Idx')
		           ->where('supertable.Type', '=', 'OneNumberDial')
		           ->whereRaw('location_superTable_mapping.End_Date IS NULL')
		           ->whereRaw('ond_toll_free_mapping.End_Date IS NULL')
		           ->where('location_superTable_mapping.Location_Idx', '=', $locationId)
		           ->update(['location_superTable_mapping.End_Date' => \Carbon::now(),
		           'ond_toll_free_mapping.End_Date' => \Carbon::now()
		           ]);*/
				
		          $locationSuperTableMapping = LocationSuperTableMapping::where( 'Location_Idx', '=', $locationId )->whereRaw( 'End_Date IS NULL' )->first();
		          if(empty($locationSuperTableMapping)==false)
		          {
		              $locationSuperTableMapping->End_Date = \Carbon::today();
		              $locationSuperTableMapping->save();
		          }

		          if ($ondServices[0]->ond_toll_free_mapping_id > 0)
		          {
		              $ondTollFreeMapping = OndTollFreeMapping::where( 'Idx', '=', $ondServices[0]->ond_toll_free_mapping_id )->whereRaw( 'End_Date IS NULL' )->first();
		              if(empty($ondTollFreeMapping)==false)
		              {
		                  $ondTollFreeMapping->End_Date = \Carbon::today();
		                  $ondTollFreeMapping->save();
		              }
		          }

		          DB::commit();

			  }
	
			  }
			  $task1->Status = 'STATUS_DONE_OK';
			  $task1->Finish_Date = \Carbon::now();
			  $task1->Message = 'Location deactivated successfully';
			  $task1->save();

			  $location_delete = Location::where( 'Idx', '=', $locationId )->first();
			  $location_delete->Deactivation_Comments =  $deactivationComment;
			  $location_delete->Updated_On = \Carbon::now();
			  $location_delete->save();	

			  return response([
			      'message' => 'Location deactivated successfully.'
			  ], Response::HTTP_OK);

	            }
	            else
	            {
	                return response([
	                    'message' => 'Location is not ACTIVE or not found'
	                ], Response::HTTP_NOT_FOUND);
	            }

        }
        catch (\Exception $e)
        {
            
	    DB::rollBack();
	    $task1->Status = 'STATUS_DONE_FAIL';
            $task1->Finish_Date = \Carbon::now();
            $task1->Message = $e->getMessage();
            $task1->save();
           

            return response([
                'message' => 'Error occurred while deactivating Location. Check the task log.',
                'error' => $e->getMessage() 
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    /**
     * @SWG\Post(
     *     path="/location/{locationId}/service",
     *     summary="Add new service to location",
     *     tags={"locations"},
     *     description="Add new service to location",
     *     operationId="addService",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *
     *     @SWG\Parameter(
     *         name="locationId",
     *         in="path",
     *         description="Location Id, for which new VAS need to be added",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="serviceId[]",
     *         in="formData",
     *         description="Service name that needs to be added",
     *         required=true,
     *         type="array",
     *         @SWG\Items(type="integer")
     *     ),
     *     @SWG\Parameter(
     *         name="startDate[]",
     *         in="formData",
     *         description="start date of the service",
     *         required=true,
     *         type="array",
     *         @SWG\Items(type="string")
     *     ),
     *     @SWG\Parameter(
     *         name="endDate[]",
     *         in="formData",
     *         description="end date of the service",
     *         required=true,
     *         type="array",
     *         @SWG\Items(type="string")
     *     ),
     *     @SWG\Parameter(
     *         name="comments[]",
     *         in="formData",
     *         description="comments",
     *         required=false,
     *         type="array",
     *         @SWG\Items(type="string")
     *     ),
     *     @SWG\Parameter(
     *         name="createdBy",
     *         in="formData",
     *         description="Name of the user adding this VAS",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Success",
     *         @SWG\Schema(
     *           @SWG\Property(
     *             property="message",
     *             type="string"
     *           )
     *         ),
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Bad request",
     *     ),
     *     @SWG\Response(
     *         response="401",
     *         description="Unauthorized access",
     *     ),
     *     @SWG\Response(
     *         response="422",
     *         description="Missing required parameter",
     *     ),
     * ),
     */
    public function addService($locationId, ServiceRequest $request)
    {
        $serviceIds = $request->serviceId;
        //check for every service id start date and end date are available
        if(count( array_diff_key( $serviceIds, $request->startDate)) > 0 || count( array_diff_key( $serviceIds, $request->endDate)) > 0)
        {
            return response(['message' => "service id, start date, end date keys doesnt match"],400);
        }
        //check if end date greater than or equal to start date
        foreach($request->startDate as $key=>$startDate)
        {
            
            if(!empty($request->endDate[$key]) && strtotime($startDate) > strtotime( $request->endDate[$key]))
            {
                return response(['message' => "End date['$key'] should be greater than start date['$key']"],400);
            }
        }
        
        // fetch details of given service ids
        $vasData = VAS::whereIn('id',$serviceIds)->where('status','=','ACTIVE')->get()->toArray();
        foreach($vasData as $value)
        {
            $vasData[$value['id']] = $value;
        }
        
        //throw error if not available service id's given
        $missingServiceIds = array_diff($serviceIds, array_keys($vasData));
        if(count($missingServiceIds) > 0)
        {
            return response(['message' => implode( ',', $missingServiceIds)." are invalid service id's"],400);
        }
        
        // get the customer of given location
        $customerData = CustomerLocation::where('Location_Idx', '=', $locationId)->get()->toArray();
        if(count($customerData) > 0)
        {
            $customerId = $customerData[0]['customerId'];
        }
        else
        {
            return response(['message' => "Customer not found for the given location"],400);
        }
        //check whether two dates are getting conflicted in given input
        foreach($serviceIds as $key=>$serviceId)
        {
            foreach($serviceIds as $key1=>$serviceId1)
            {
                if($key != $key1 && $serviceId == $serviceId1 && strtotime($request->startDate[$key]) <= strtotime($request->startDate[$key1]) && ((! empty($request->endDate[$key]) && ! empty($request->endDate[$key1]) && strtotime($request->endDate[$key]) >= strtotime($request->endDate[$key1])) || empty($request->endDate[$key] || empty($request->endDate[$key1]))))
                {
                    return response(['message' => "Invalid dates for '".$vasData[$serviceId]["name"]."' with id '$serviceId'"],400);
                }
            }
        }
        //throw error if service already exists for given location for the given date range
        foreach($serviceIds as $key=>$serviceId)
        {
            $endDateCond = "date(Start_Date) <= '".$request->startDate[$key]."'";
            //append enddate only when not empty
            if(!empty($request->endDate[$key]))
            {
                $endDateCond.=" and (date(End_Date) >= '".$request->endDate[$key]."' or End_Date is null)";
            }
            else
            {
                $endDateCond.=" and End_Date is null";
            }
            $vasLocationData = LocationVAS::where('locationId','=',$locationId)
                                ->where('serviceId','=', $serviceId)
                                ->whereRaw($endDateCond)
                                ->get()->toArray();
            if(count($vasLocationData) > 0)
            {
                return response(['message' => "Service '".$vasData[$serviceId]["name"]."' with id '$serviceId' already exists for this location for the given date range."],400);   
            }
        }
        
        //save service's to DB
        foreach($serviceIds as $key=>$serviceId)
        {

            $locationVAS = new LocationVAS([
                                'customerId' => $customerId, 
                                'serviceId' => $serviceId,
                                'locationId' => $locationId,
                                'startDate' => $request->startDate[$key],
                                'endDate' => !empty($request->endDate[$key])?$request->endDate[$key]:null,
                                'comments' => isset($request->comments[$key])?$request->comments[$key]:'',
                                'createdBy' => isset($request->createdBy)?$request->createdBy:null
                        ]);
            $locationVAS->save();
        }
        return response(['message' => 'Location Service details saved successfully!!!']);
    }
    
    /**

     * @SWG\Put(
     *     path="/location/{locationId}/serviceMapping/{serviceMappingId}",
     *     summary="Update service of the location",
     *     tags={"locations"},
     *     description="Update service of the location",
     *     operationId="updateService",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *
     *     @SWG\Parameter(
     *         name="locationId",
     *         in="path",
     *         description="Location Id, for which VAS to be updated",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="serviceMappingId",
     *         in="path",
     *         description="VAS Location mapping Id of the given location",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="startDate",
     *         in="formData",
     *         description="start date of the service",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="endDate",
     *         in="formData",
     *         description="end date of the service",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="comments",
     *         in="formData",
     *         description="comments",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="updatedBy",
     *         in="formData",
     *         description="Name of the user updating this VAS",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Success",
     *         @SWG\Schema(
     *           @SWG\Property(
     *             property="message",
     *             type="string"
     *           )
     *         ),
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Bad request",
     *     ),
     *     @SWG\Response(
     *         response="401",
     *         description="Unauthorized access",
     *     ),
     *     @SWG\Response(
     *         response="422",
     *         description="Missing required parameter",
     *     ),
     * ),
     */
    public function updateService($locationId, $serviceMappingId, ServiceRequest $request)
    {
        //check if serviceMappingId is valid
        $vasLocationData = LocationVAS::find($serviceMappingId);
        if(is_null($vasLocationData))
        {
            return response(['message' => "Invalid service mapping id"],400);
        }
        //check the given locationId and actual locationId present in database
        if($locationId != $vasLocationData->Location_Idx)
        {
            return response(['message' => "Given service mapping id doesnot belong to specified location"],400);
        }
        
        //fetch customer of given location
        $customerData = CustomerLocation::where('Location_Idx', '=', $locationId)->get()->toArray();
        if(count($customerData) > 0)
        {
            $customerId = $customerData[0]['customerId'];
        }
        else
        {
            return response(['message' => "Customer not found for the given location"],400);
        }
        //check if end date is greater than or equal to start date
        if(!empty($request->endDate) && strtotime($request->startDate) > strtotime( $request->endDate ))
        {
            return response(['message' => "End date should be greater than start date"],400);
        }
        //update values to database
        $vasLocationData->Start_Date = $request->startDate;
        $vasLocationData->End_Date = !empty($request->endDate)?$request->endDate:null;
        $vasLocationData->Comments = $request->comments;
        $vasLocationData->Customer_Idx = $customerId;
        $vasLocationData->Updated_On = date('Y-m-d H:i:s');
        $vasLocationData->Updated_By = $request->updatedBy;
        $vasLocationData->save();
        return response(['message' => 'Location Service details updated successfully!!!']);     
    }
    
    /**

     * @SWG\delete(
     *     path="/location/{locationId}/serviceMapping/{serviceMappingId}",
     *     summary="delete service of location",
     *     tags={"locations"},
     *     description="delete service of location",
     *     operationId="deleteService",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *
     *     @SWG\Parameter(
     *         name="locationId",
     *         in="path",
     *         description="Location Id, for which VAS to be deleted",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="serviceMappingId",
     *         in="path",
     *         description="VAS Location mapping Id which needs to be deleted",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Success",
     *         @SWG\Schema(
     *           @SWG\Property(
     *             property="message",
     *             type="string"
     *           )
     *         ),
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Bad request",
     *     ),
     *     @SWG\Response(
     *         response="401",
     *         description="Unauthorized access",
     *     ),
     *     @SWG\Response(
     *         response="422",
     *         description="Missing required parameter",
     *     ),
     * ),
     */
    public function deleteService($locationId, $serviceMappingId)
    {
        //fetch row by serviceMappingId
        $vasLocationData = LocationVAS::find($serviceMappingId);
        if(is_null($vasLocationData))
        {
            return response(['message' => "Invalid service mapping id"],400);
        }
        //check if given locationId is same as locationId present in database 
        if($vasLocationData->Location_Idx != $locationId)
        {
            return response(['message' => "Given service mapping id doesnot belong to specified location"],400);
        }
        //delete row from database
        $vasLocationData->delete();
        return response(['message' => 'Location Service deleted successfully!!!']);      
    }
    
    /**
     * @SWG\get(
     *     path="/location/{locationId}/services",
     *     summary="get all services of location",
     *     tags={"locations"},
     *     description="get services of location",
     *     operationId="getService",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *
     *     @SWG\Parameter(
     *         name="locationId",
     *         in="path",
     *         description="Location Id, for which VAS to be deleted",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="startDate",
     *         in="query",
     *         description="start date of the service",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="endDate",
     *         in="query",
     *         description="end date of the service",
     *         required=false,
     *         type="string"
     *     ),
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
     *         @SWG\Schema(
     *           type="array",
     *             @SWG\Items(ref="#/definitions/LocationVAS")
     *         ),
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Bad request",
     *     ),
     *     @SWG\Response(
     *         response="401",
     *         description="Unauthorized access",
     *     ),
     *     @SWG\Response(
     *         response="422",
     *         description="Missing required parameter",
     *     ),
     * ),
     */
    public function getServices($locationId, ServiceRequest $request)
    {
        $query = LocationVAS::where("Location_Idx","=",$locationId);
        if($request->has('startDate'))
        {
            $query->whereRaw("date(Start_Date) >='".$request->input('startDate')."'");
        }
        if($request->has('endDate'))
        {
            $query->whereRaw("date(End_Date) <='".$request->input('endDate')."'");
        }
        return $query->paginate(intval($request->input('page_size', 50)));
        
    }
}
