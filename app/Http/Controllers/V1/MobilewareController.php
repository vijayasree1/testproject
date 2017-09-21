<?php
namespace App\Http\Controllers\V1;

use App\Jobs\Mobileware;
use App\Http\Models\Terminal;
use App\Http\Models\Task;
use Illuminate\Http\Request;
use App\Http\Services\MobilewareService;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;

class MobilewareController extends Controller
{

    private $mobilewareService;

    public function __construct ()
    {
        $this->mobilewareService = new MobilewareService();
    }

     /**
     * @SWG\Get(
     *     path="/list-jx-subscriptions",
     *     summary="listSubscriptions",
     *     tags={"mobileware"},
     *     description="This resource is dedicated to list Jx subscriptions from mobileware",
     *     operationId="listJxSubscriptions",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search",
     *         required=true,
     *         type="string",
     *        enum={"ALL_JX_TERMINALS", "JX_TERMINALS_NOT_IN_DBMAN"},
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Success",
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
    public function listJxSubscriptions (Request $request)
    {
        try
        {
            $mobilewareService = new MobilewareService();
            $response = [];
            $subscriptionsList = $mobilewareService->listSubscriptions();
            
            if ($request->has( 'search' ) && $request->input( 'search' ) == "ALL_JX_TERMINALS")
            {
                foreach ($subscriptionsList as $subscription)
                {
                    $response[] = [
                        'templateId' => $subscription['template-id'],
                        'subscriptionNumber' => $subscription['subscription-number'],
                        'imsi' => $subscription['imsi'],
                        'iccId' => $subscription['icc-id'],
                        'tpkId' => $subscription['msisdn'],
                        'packageId' => $subscription['package-id'],
                        'packageType' => $subscription['package-type'],
                        'companyId' => $subscription['company-id'],
                        'companyName' => $subscription['company-name'],
                        'tail' => $subscription['tail'],
                        'icao' => $subscription['icao'],
                        'model' => $subscription['model'],
                        'activatedAt' => $subscription['activated-at'],
                        'status' => $subscription['status']
                    ];
                }
            }
            else
            {
                foreach ($subscriptionsList as $subscription)
                {
                    $terminalData = Terminal::query()->where( 'TPK_DID', '=', $subscription['msisdn'] )
                        ->whereRaw( '(Activation_Date < now() AND ( Deactivation_Date IS NULL OR Deactivation_Date > now() ))' )
                        ->get();
                    
                    if (count( $terminalData ) == 0)
                    {
                        $response[] = [
                            'templateId' => $subscription['template-id'],
                            'subscriptionNumber' => $subscription['subscription-number'],
                            'imsi' => $subscription['imsi'],
                            'iccId' => $subscription['icc-id'],
                            'tpkId' => $subscription['msisdn'],
                            'packageId' => $subscription['package-id'],
                            'packageType' => $subscription['package-type'],
                            'companyId' => $subscription['company-id'],
                            'companyName' => $subscription['company-name'],
                            'tail' => $subscription['tail'],
                            'icao' => $subscription['icao'],
                            'model' => $subscription['model'],
                            'status' => $subscription['status']
                        ];
                    }
                }
            }
            
            return response( $response, Response::HTTP_OK );
        }
        catch (\Exception $e)
        {
            return response( [
                'message' => $e->getMessage() . $e->getFile() . $e->getLine()
            ], Response::HTTP_INTERNAL_SERVER_ERROR );
        }
    }
}
