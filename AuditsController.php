<?php
namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Models\Auditing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Response;
use Validator;

class AuditsController extends Controller
{
    /**
     * @SWG\Get(
     *     path="/audits",
     *     summary="/audits resource",
     *     tags={"audits"},
     *     description="This resource is dedicated to querying data around Audits.",
     *     operationId="getAudits",
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
    
    public function getAudits(Request $request)
    {
        
        $audits = Auditing::query()->orderBy('Idx','DESC');
        
        if( $audits->count() < 1 ) {
            return response('Audits not found', 200);
        }
        
        return $audits->paginate(intval(Input::input('page_size', 50))); //->toSql();
    }
    
    /**
     * @SWG\Get(
     *     path="/audits/{transactionId}",
     *     summary="/audits with Transaction Id resource",
     *     tags={"audits"},
     *     description="This resource is dedicated to querying data around Audit Id.",
     *     operationId="getAuditDetails",
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
     *         name="transactionId",
     *         in="path",
     *         description="Filter Audits by Transaction Id",
     *         required=true,
     *         type="string",
     *         @SWG\Items(type="string"),
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Success",
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
    
    public function getAuditDetails($transactionId, Request $request)
    {
        $audits = Auditing::where('uniqu', '=', $transactionId)->orderBy('Idx','DESC');
        
        if( $audits->count() < 1 ) {
            return response('Audits not found', 200);
        }
        
        return $audits->paginate(intval(Input::input('page_size', 50))); //->toSql();
       
    }
}
