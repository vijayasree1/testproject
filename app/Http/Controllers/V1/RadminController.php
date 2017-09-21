<?php
namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Models\RadminSimUser;
use App\Http\Models\RadminUserGroup;
use App\Http\Services\RadminService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class RadminController extends Controller
{
    private $radminService;

    public function __construct ()
    {
        $this->radminService = new RadminService;
    }

    /**
     * @SWG\Get(
     *     path="/sims",
     *     summary="Get all available SIMS",
     *     tags={"radmin"},
     *     description="This resource is dedicated to querying data around SIMS from radmin",
     *     operationId="listSIMS",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         name="imsi",
     *         in="query",
     *         description="filter SIMs with imsi",
     *         required=false,
     *         type="number"
     *     ),
     *     @SWG\Parameter(
     *         name="demo",
     *         in="query",
     *         description="filter SIMs with demo.",
     *         required=false,
     *         type="boolean"
     *     ),
     *     @SWG\Parameter(
     *         name="cabinbilling",
     *         in="query",
     *         description="filter SIMs with cabinbilling.",
     *         required=false,
     *         type="boolean"
     *     ),
     *     @SWG\Parameter(
     *         name="msisdn",
     *         in="query",
     *         description="filter SIMs with msisdn.",
     *         required=false,
     *         type="number"
     *     ),
     *     @SWG\Parameter(
     *         name="iccid",
     *         in="query",
     *         description="filter SIMs with iccid.",
     *         required=false,
     *         type="number"
     *     ),
     *     @SWG\Parameter(
     *         name="active",
     *         in="query",
     *         description="filter SIMs with active.",
     *         required=false,
     *         type="boolean"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Success",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(ref="#/definitions/sims")
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
    public function listSIMS (Request $request)
    {
        try
        {
            $radminService = new RadminService;
            $response = [];
            $simList = $radminService->getSimList($request->all());

            foreach( $simList as $sim )
            {
                $response[] = [
                    'id' => $sim['id'],
                    'imsi' => $sim['attributes']['imsi']
                ];
            }

            return response( $response, Response::HTTP_OK );
        }
        catch (\Exception $e)
        {
            return response([
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR );
        }
    }

    /**
     * @SWG\Get(
     *     path="/showsim/{simId}",
     *     summary="Get detailed sim information",
     *     tags={"radmin"},
     *     description="This resource is dedicated to querying data around SIM from radmin",
     *     operationId="showSim",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         name="simId",
     *         in="path",
     *         description="Provide simId to get details",
     *         required=true,
     *         type="number"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Success",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(ref="#/definitions/showsim")
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
    public function showSim ($simId)
    {
        try
        {
            $simDetail = $this->radminService->showSIM($simId);
            
            $response = array(
                'id' => $simDetail['id'],
                'imsi' => $simDetail['attributes']['imsi'],
                'iccId' => $simDetail['attributes']['iccid'],
                'msisdn' => $simDetail['attributes']['msisdn'],
                'active' => $simDetail['attributes']['active'],
                'cabinBillingEnabled' => $simDetail['attributes']['cabinbilling'],
                'companyName' => $simDetail['attributes']['company_name'],
                'avioipEnabled' => $simDetail['attributes']['avioip'],
                'demo' => $simDetail['attributes']['demo'],
                'tailNumber' => $simDetail['attributes']['tailnumber'],
                'sapId' => $simDetail['attributes']['sap_id'],
                'remarks' => $simDetail['attributes']['remarks']
            );
            return response( $response, Response::HTTP_OK );
        }
        catch (\Exception $e)
        {
            return response([
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR );
        }
    }
    
    /**
     * @SWG\Get(
     *     path="/sim-provisioning/{simId}",
     *     summary="Get all available SIM Provisioning of particular SIM",
     *     tags={"radmin"},
     *     description="This resource is dedicated to querying data around SIM Provisioning from radmin",
     *     operationId="listSIMProvisioning",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         name="simId",
     *         in="path",
     *         description="Provide simId to get details",
     *         required=true,
     *         type="number"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Success",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(ref="#/definitions/simprovisioning")
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
    public function listSIMProvisioning ($simId)
    {
        try
        {
            $simProvisionings = $this->radminService->listSIMProvisioning($simId);
            $response = [];

            foreach ($simProvisionings as $simProvisioning)
            {
                $response[] = [
                    'id' => $simProvisioning['id'],
                    'fixedIpV4' => $simProvisioning['attributes']['fixedipv4']['addr'],
                    'standardIp' => $simProvisioning['attributes']['standardip'],
                    'highestStreamingOptionEnabled' => $simProvisioning['attributes']['highest_streaming_option_enabled'],
                    'streamingHDRHalfAsymmetric' => $simProvisioning['attributes']['streaminghdrhalfasymmetric'],
                    'streamingHDRHalfSymmetric' => $simProvisioning['attributes']['streaminghdrhalfsymmetric'],
                    'streamingHDRFullAsymmetric' => $simProvisioning['attributes']['streaminghdrfullasymmetric'],
                    'streamingHDRFullSymmetric' => $simProvisioning['attributes']['streaminghdrfullsymmetric'],
                    'simId' => $simProvisioning['relationships']['sim']['data']['id'],
                    'userGroupId' => $simProvisioning['relationships']['usergroup']['data']['id'],
                    'simUserId' => $simProvisioning['relationships']['sim_user']['data']['id'],
                ];
            }

            return response( $response, Response::HTTP_OK );
        }
        catch (\Exception $e)
        {
            return response([
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR );
        }
    }
    
    /**
     * @SWG\Get(
     *     path="/companies",
     *     summary="Get all available companies",
     *     tags={"radmin"},
     *     description="This resource is dedicated to querying data around companies from radmin",
     *     operationId="listCompanies",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *
     *     @SWG\Response(
     *         response=200,
     *         description="Success",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(ref="#/definitions/companies")
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
    public function listCompanies ()
    {
        try
        {
            $companies = $this->radminService->listCompanies();
            $response = [];

            foreach ($companies as $company)
            {
                $response[] = [
                    'id' => $company['id'],
                    'name' => $company['attributes']['name'],
                    'relationType' => $company['attributes']['relation_type'],
                    'apnIds' => array_column($company['relationships']['apns']['data'], 'id'),
                    'simIds' => array_column($company['relationships']['sims']['data'], 'id'),
                    'userIds' => array_column($company['relationships']['users']['data'], 'id')
                ];
            }

            return response( $response, Response::HTTP_OK);
        }
        catch (\Exception $e)
        {
            return response([
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR );
        }
    }

    /**
     * @SWG\Get(
     *     path="/usergroups",
     *     summary="Get all available usergroups",
     *     tags={"radmin"},
     *     description="This resource is dedicated to querying data around usergroups from radmin",
     *     operationId="listUsergroups",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *
     *     @SWG\Response(
     *         response=200,
     *         description="Success",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(ref="#/definitions/usergroups")
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
    public function listUsergroups ()
    {
        return RadminUserGroup::all();
    }

    /**
     * @SWG\Get(
     *     path="/simusers/{usergroupId}",
     *     summary="Get all available simusers of particular usergroup",
     *     tags={"radmin"},
     *     description="This resource is dedicated to querying data around simusers from radmin",
     *     operationId="listAvailableSimUsers",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         name="userGroupId",
     *         in="path",
     *         description="get simusers of this usergroup id.",
     *         required=true,
     *         type="number"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Success",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(ref="#/definitions/simusers")
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
    public function listAvailableSimUsers ($userGroupId)
    {
        return RadminSimUser::where('User_Group_Idx', '=', $userGroupId)->get();
    }

    /**
     * @SWG\Get(
     *     path="/cabinbilling",
     *     summary="Get all cabinbilling usage information from radmin",
     *     tags={"radmin"},
     *     description="This resource is dedicated to querying data around cabinbilling uasage from radmin",
     *     operationId="cabinBillingUsage",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *     
     *     @SWG\Response(
     *         response=200,
     *         description="Success",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(ref="#/definitions/cabinbilling")
     *         ),
     *     ),
     *     @SWG\Parameter(
     *         name="class",
     *         in="query",
     *         description="Filter Cabin billing usage by Class.",
     *         required=false,
     *         type="number"
     *     ),
     *     @SWG\Parameter(
     *         name="days",
     *         in="query",
     *         description="Limit the logs by number of days from today",
     *         required=false,
     *         type="number"
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
    public function cabinBillingUsage(Request $request)
    {
        try
        {
            $cabinBillingUsages = $this->radminService->cabinBillingUsage( $request->all() );
            $response = [];

            foreach ($cabinBillingUsages as $usageDetail)
            {
                $response[] = [
                    'id' => $usageDetail['id'],
                    'operation' => $usageDetail['attributes']['operation'],
                    'objectClass' => $usageDetail['attributes']['object_class'],
                    'imsi' => $usageDetail['attributes']['imsi'],
                    'tailNumber' => $usageDetail['attributes']['tailnumber'],
                    'objectId' => $usageDetail['attributes']['object_id'],
                    'time' => $usageDetail['attributes']['time']
                ];
            }

            return response( $response, Response::HTTP_OK );
        }
        catch (\Exception $e)
        {
            return response([
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR );
        }
    }

    /**
     * @SWG\Get(
     *     path="/radmin-sync",
     *     summary="Sync DBMan Terminals information with Radmin",
     *     tags={"sync"},
     *     description="This resource is for initiating DBMan - Radmin sync process.",
     *     operationId="sync",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *
     *     @SWG\Response(
     *         response=200,
     *         description="Success"
     *     ),
     *     @SWG\Parameter(
     *         name="imsi[]",
     *         in="query",
     *         description="List of IMSI numbers to be synchronized with Radmin.",
     *         required=false,
     *         items="string",
     *         type="array",
     *         collectionFormat="multi"
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
    public function sync(Request $request)
    {
        try
        {
            $this->radminService->sync( Auth::user()->username, $request->input('imsi'));
            
            return response([
                'status' => 'SUCCESS',
                'message' => 'Sync done successfully.'
            ], 200);
        }
        catch (\Exception $e)
        {
            return response([
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR );
        }
    }

    /**
     * @SWG\Get(
     *     path="/radmin-sync-masterdata",
     *     summary="Sync Radmin master data with DBMan",
     *     tags={"sync"},
     *     description="This resource is for initiating Radmin - DBMan master data sync process.",
     *     operationId="syncMasterData",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *
     *     @SWG\Response(
     *         response=200,
     *         description="Success"
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
    public function syncMasterData()
    {
        try
        {
            $this->radminService->syncMasterData();
        }
        catch (\Exception $e)
        {
            return response([
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR );
        }
    }
}
