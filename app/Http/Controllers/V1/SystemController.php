<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Models\System;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Services\DBManService;

class SystemController extends Controller
{
    /**
     * @SWG\Get(
     *     path="/systems",
     *     summary="/systems resource",
     *     tags={"systems"},
     *     description="Returns the Systems/Terminals",
     *     operationId="listSystems",
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
     *         name="customerId",
     *         in="query",
     *         description="Filter systems by Customer Id",
     *         required=false,
     *         type="integer",
     *         collectionFormat="csv"
     *     ),
     *     @SWG\Parameter(
     *         name="locationId",
     *         in="query",
     *         description="Filter systems by Location Id",
     *         required=false,
     *         type="integer",
     *         collectionFormat="csv"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Success",
     *         @SWG\Schema(
     *          @SWG\Property(
     *          property="System",
     *          type="array",
     *           @SWG\Items(ref="#/definitions/System"),
     *          ),
     *         ),
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Invalid request",
     *     ),
     *     @SWG\Response(
     *         response="401",
     *         description="Unauthorized access",
     *     )
     * ),
     */
    public function listSystems( Request $request )
    {
        $systemsQuery = System::query();
        $systemsQuery->select('system.*')->with('terminals');

        $systemsQuery->join('system_location_mapping as slm', 'slm.System_Idx', '=', 'system.Idx')
        ->join('location', 'location.Idx', '=', 'slm.Location_Idx')
        ->join('system_customer_mapping as scm', 'system.Idx', '=', 'scm.System_Idx')
        ->join('customer', 'customer.Idx', '=', 'scm.Customer_Idx')
        ->whereRaw('scm.Start_Date < now() and ( scm.End_Date IS NULL OR scm.End_Date > now() )')
        ->whereRaw('slm.Start_Date < now() and ( slm.End_Date IS NULL OR slm.End_Date > now() )')
        ->orderBy('location.Idx');

        $searchParameters = [
                'customerId' => 'customer.Idx',
                'locationId' => 'location.Idx',
        ];

        foreach( $searchParameters as $parameterName => $columnName ) {
            if( $request->has($parameterName) ) {
                $systemsQuery->where($columnName, '=', $request->input($parameterName));
            }
        }

        return $systemsQuery->groupBy('system.Idx')->paginate(intval($request->input('page_size', 50)));
    }
    /**
     * @SWG\Delete(
     *     path="/system/{systemId}",
     *     summary="Deactivate System",
     *     tags={"systems"},
     *     description="This resource is dedicated to deactivate system",
     *     operationId="deactivatesystem",
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
     *         name="systemId",
     *         in="path",
     *         description="System Id",
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
    public function deactivateSystem($systemId, Request $request)
    {
        try {
            $DBManService = new DBManService;
            $deactivatedBy = $request->input('deactivatedBy');
	    $deactivationComment = $request->input('comment');
            $response = $DBManService->deactivateSystem($systemId, $deactivatedBy,$deactivationComment);
            return $response;
        } catch (\Exception $e) {
            return response([
                'message' => 'Error occurred while deactivating system. Check the task log.',
                'error' => $e->getMessage() 
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}