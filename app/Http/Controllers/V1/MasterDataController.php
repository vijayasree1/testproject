<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Models\AircraftMake;
use App\Http\Models\AircraftModel;
use App\Http\Models\APN;
use App\Http\Models\Country;
use App\Http\Models\Leso;
use App\Http\Models\Network;
use App\Http\Models\RadminFirewallTier;
use App\Http\Models\Service;
use App\Http\Models\ServicesMaster;
use App\Http\Services\RadminService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use App\Http\Models\VAS;

class MasterDataController extends Controller
{

    /**
     * @SWG\Get(
     *     path="/countries",
     *     summary="/countries resource",
     *     tags={"master data"},
     *     description="This resource is dedicated to querying Countries.",
     *     operationId="listCountries",
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
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(ref="#/definitions/Country")
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
    public function listCountries()
    {
        return Country::query()->orderBy('Name', 'ASC')->get();
    }

    /**
     * @SWG\Get(
     *     path="/aircraft-makes",
     *     summary="/aircraft-makes resource",
     *     tags={"master data"},
     *     description="This resource is dedicated to querying Aircraft Makes(Manufacturers).",
     *     operationId="listAircraftMakes",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         name="lastSyncDate",
     *         in="query",
     *         description="Filter by Last Updated Date",
     *         required=false,
     *         type="string",
     *         format="date-time",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Success",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(ref="#/definitions/AircraftMake")
     *         ),
     *     ),
     *     @SWG\Response(
     *         response="401",
     *         description="Unauthorized access",
     *     )
     *
     * ),
     */
    
    public function listAircraftMakes(Request $request)
    {
        $this->validate($request, [
                'lastSyncDate' => 'date_format:Y-m-d H:i:s|regex:/[0-9]{4}\-/',
        ]);
        
        $airCraftMakeQuery=AircraftMake::query();
        
        if ($request->has('lastSyncDate')) {
            $airCraftMakeQuery->where('Updated_On', '>', $request->input('lastSyncDate'));
        }
        
        return $airCraftMakeQuery->orderBy('Make', 'ASC')->get();
    }
    
    /**
     * @SWG\Get(
     *     path="/aircraft-makes/{makeId}/models",
     *     summary="/aircraft-makes/{makeId}/models resource",
     *     tags={"master data"},
     *     description="This resource is dedicated to querying Aircraft Models.",
     *     operationId="listAircraftModels",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         name="makeId",
     *         in="path",
     *         description="Aircraft Make ID",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Success",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(ref="#/definitions/AircraftModel")
     *         ),
     *     ),
     *     @SWG\Response(
     *         response="401",
     *         description="Unauthorized access",
     *     )
     * ),
     */
    public function listAircraftModels($makeId)
    {
        return AircraftModel::where('Make_Idx', '=', $makeId)->orderBy('Model', 'ASC')->get();
    }

    /**
     * @SWG\Get(
     *     path="/apn",
     *     summary="APNs List Resource",
     *     tags={"master data"},
     *     description="This resource is dedicated to querying active APN List.",
     *     operationId="listApn",
     *     produces={"application/json"},
     *     @SWG\Response(
     *         response=200,
     *         description="Success",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(ref="#/definitions/APN")
     *         ),
     *     ),
     *     @SWG\Response(
     *         response="401",
     *         description="Unauthorized access",
     *     )
     * ),
     */
    public function listApn()
    {
        return APN::where('Active_YN_Flag', '=', 'Yes')->orderBy('APN_Name', 'ASC')->get();
    }

    /**
     * @SWG\Get(
     *     path="/networks",
     *     summary="Networks List Resource",
     *     tags={"master data"},
     *     description="This resource is dedicated to querying active Network List.",
     *     operationId="listNetworks",
     *     produces={"application/json"},
     *     @SWG\Response(
     *         response=200,
     *         description="Success",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(ref="#/definitions/Network")
     *         ),
     *     ),
     *     @SWG\Response(
     *         response="401",
     *         description="Unauthorized access",
     *     )
     * ),
     */
    public function listNetworks()
    {
        return Network::where('Active_YN_Flag', '=', 'Yes')->orderBy('Network_Name', 'ASC')->get();
    }

    /**
     * @SWG\Get(
     *     path="/leso",
     *     summary="LESO Resource",
     *     tags={"master data"},
     *     description="This resource is dedicated to querying LESO.",
     *     operationId="listLeso",
     *     produces={"application/json"},
     *     @SWG\Response(
     *         response=200,
     *         description="Success",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(ref="#/definitions/LESO")
     *         ),
     *     ),
     *     @SWG\Response(
     *         response="401",
     *         description="Unauthorized access",
     *     )
     * ),
     */
    public function listLeso()
    {
        return Leso::query()->orderBy('Name', 'ASC')->get();
    }

    /**
     * @SWG\Get(
     *     path="/streaming-modes",
     *     summary="Streaming Resource",
     *     tags={"master data"},
     *     description="This resource is dedicated to querying available Streaming modes.",
     *     operationId="listAvailableStreamingModes",
     *     produces={"application/json"},
     *     @SWG\Response(
     *         response=200,
     *         description="Success",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(ref="#/definitions/ServicesMaster")
     *         ),
     *     ),
     *     @SWG\Response(
     *         response="401",
     *         description="Unauthorized access",
     *     )
     * ),
     */
    public function listAvailableStreamingModes()
    {
        return ServicesMaster::join('service_category as sc', function($join) {
                    $join->on('services_master.Service_Category_Idx', '=', 'sc.Service_Category_Idx')
                            ->on('sc.Service_Category_Name', '=', DB::raw("'Streaming'"));
                })
                ->where('services_master.Active_YN_Flag', '=', 'Yes')
                ->orderBy('Streaming_Order', 'ASC')->get();
    }

    /**
     * @SWG\Get(
     *     path="/godirect-filter-levels",
     *     summary="GoDirect Filtering Levels Resource",
     *     tags={"master data"},
     *     description="This resource is dedicated to querying available filtering levels.",
     *     operationId="listFilteringLevels",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         name="lastSyncDate",
     *         in="query",
     *         description="Filter by Last Updated Date",
     *         required=false,
     *         type="string",
     *         format="date-time",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Success",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(ref="#/definitions/ServicesMaster")
     *         ),
     *     ),
     *     @SWG\Response(
     *         response="401",
     *         description="Unauthorized access",
     *     )
     * ),
     */
    public function listFilteringLevels(Request $request)
    {
        $this->validate($request, [
                'lastSyncDate' => 'date_format:Y-m-d H:i:s|regex:/[0-9]{4}\-/',
        ]);
        
        $radminFirewallQuery=RadminFirewallTier::query();
        
        if ($request->has('lastSyncDate')) {
            $radminFirewallQuery->where('Updated_On', '>', $request->input('lastSyncDate'));
        }
        
        return $radminFirewallQuery->get(); 
    }

    /**
     * @SWG\Get(
     *     path="/ip-addresses",
     *     summary="Get Ips List",
     *     tags={"master data"},
     *     description="This resource is dedicated to querying data around IPs from Radmin & DBMan",
     *     operationId="listIPS",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         name="source",
     *         in="query",
     *         description="Source of IPs",
     *         required=true,
     *         type="string"
     *     ),
	 *	   @SWG\Parameter(
     *         name="networkType",
     *         in="query",
     *         description="Network type (i4 or i5).",
     *         required=false,
     *         type="string",
	 *		   enum={"i4", "i5"}
     *     ),
	 *     @SWG\Parameter(
     *         name="userGroupId",
     *         in="query",
     *         description="When source is radmin, userGroupId is required.",
     *         required=false,
     *         type="number"
     *     ),
	 *	   @SWG\Parameter(
     *         name="static",
     *         in="query",
     *         description="Static can be true or false.",
     *         required=false,
     *         type="string",
	 *		   enum={"true", "false"}
     *     ),
	 *	   @SWG\Parameter(
     *         name="reserved",
     *         in="query",
     *         description="reserved can be true or false.",
     *         required=false,
     *         type="string",
	 *		   enum={"true", "false"}
     *     ),
	 *	   @SWG\Parameter(
     *         name="free",
     *         in="query",
     *         description="free can be true or false.",
     *         required=false,
     *         type="string",
	 *		   enum={"true", "false"}
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Success",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(ref="#/definitions/IPs")
     *         ),
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="No data found",
     *     ),
     *     @SWG\Response(
     *         response="401",
     *         description="Unauthorized access",
     *     )
     *
     * ),
     */
    public function listIPS(Request $request)
    {
        try 
        {
            $validTypes = array('dbman', 'radmin');

            if( !$request->has('source') )
            {
                return response('source is required', Response::HTTP_BAD_REQUEST);
            }
            else if( !in_array(strtolower($request->input('source')), $validTypes) )
            {
                return response("source can take 'dbman' or 'radmin'", Response::HTTP_BAD_REQUEST);
            }

            $source = $request->input('source');
									
            if( $source == 'dbman' )
            {
               $services = Service::where('Service','=','STATIC_IP')
                                ->where('Terminal_Idx','=','0')
                                ->where('Activation_Date','=','0000-00-00 00:00:00')
                                ->take(1)->get();

				$response = [];
                foreach( $services as $service )
                {
                    $response[] = [ 'address' => $service->Number ];
                }
                return response($response, Response::HTTP_OK);
            }
			
			$paramData = array(
				'networktype' => $request->input('networkType'),
				'usergroup' => $request->input('userGroupId'),
				'static' => $request->input('static'),
				'reserved' => $request->input('reserved'),
				'free' => $request->input('free'),
			);

			$queryParams = http_build_query($paramData);
			
			$radminService = new RadminService();
			$ipAddresses =  $radminService->listIPS($queryParams);
            $response = [];

            foreach ($ipAddresses as $ipAddress)
            {
                $response[] = [
                    'id' => $ipAddress['id'],
                    'address' => $ipAddress['attributes']['address'],
                    'reserved' => $ipAddress['attributes']['reserved'],
                    'static' => $ipAddress['attributes']['static']
                ];
            }

            return response($response, Response::HTTP_OK);
        } 
        catch (\Exception $e) 
        {
            return response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
    
    /**
     * @SWG\Get(
     *     path="/services",
     *     summary="Value Added Services resource",
     *     tags={"master data"},
     *     description="This resource is dedicated to querying Value Added Services.",
     *     operationId="listVAS",
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
     *         name="status",
     *         in="query",
     *         description="Number of results Per Page",
     *         required=false,
     *         type="string",
     *         enum={"ACTIVE","INACTIVE"}
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Success",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(ref="#/definitions/VAS")
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
    public function listVAS(Request $request)
    {
        $query = VAS::query();
        if($request->has('status'))
        {
            $query->where('status','=',$request->input('status'));
        }
        return $query->orderBy('Service_Name', 'ASC')->paginate(intval($request->input('page_size', 50)));
    }
}
