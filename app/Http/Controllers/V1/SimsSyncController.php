<?php
namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Models\SimsSync;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Services\SimsService;
use Illuminate\Support\Facades\Auth;
class SimsSyncController extends Controller
{
    /**
     * @SWG\Get(
     *     path="/sims-sync",
     *     summary="Sync SIMS Subscription data with DBMan",
     *     tags={"sync"},
     *     description="This resource is for Syncing SIMS Subscriptions,Subscription Locations,Plan Category,Plan,Subscription Status,DBMan-SIMS Customer Mapping  information with SIMS,initiating SIMS - DBMan sync process.",
     *     operationId="sync",
     *     consumes={"application/json"},
     *     produces={"application/json"},
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
    public function syncSimsData(Request $request)
    {
        try
        {

            $simsSync = new SimsSync();
            $simsSync->Created_By = Auth::user()->username;
            $simsSync->Created_At = \Carbon::now();
            $simsSync->save();
            
            $simsService = new SimsService();
            
            $action=array("syncStatus","syncCustomerMapping","syncPackagePlanTypes","syncPlanNew","syncSubscriptions","syncSubscriptionLocations","updateDailyCallLogs","updateMonthlyCallLogs");
            //$action=array("syncStatus","syncCustomerMapping","syncPackagePlanTypes","syncPlanNew","syncSubscriptions","syncSubscriptionLocations");
            
            for ($i=0;$i<count($action);$i++)
            {
                call_user_func_array(array( $simsService, $action[$i]),array());
            }
            
            $simsSync->Completed_At = \Carbon::now();
            $simsSync->save();
            
            return response([
                'status' => 'SUCCESS',
                'message' => 'Sync Done successfully.'
            ], 200);
        }
        catch (\Exception $e)
        {
            return response([
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR );
        }
    }
}
