<?php
namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Models\Contact;
use App\Http\Models\User;
use Illuminate\Http\Request;

class AcsmUserController extends Controller
{
    /**
    * @SWG\Get(
    *     path="/acsm-users",
    *     summary="/acsm users resource",
    *     tags={"acsm-users"},
    *     description="This resource is dedicated to querying the Role of the User.",
    *     operationId="getUserRoles",
    *     consumes={"application/json"},
    *     produces={"application/json"},
    *     @SWG\Parameter(
    *         name="honeywellId",
    *         in="query",
    *         description="Find Role by Honeywell Id",
    *         required=true,
    *         type="string",
    *     ),
    *     @SWG\Response(
    *         response=200,
    *         description="Success",
    *         @SWG\Schema(
    *             type="array",
    *             @SWG\Items(ref="#/definitions/ContactRoleMaster")
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
    public function getUserRoles(Request $request)
    {
        if(!$request->has('honeywellId')) {
            return response('\'honeywellId\' parameter is required.', 400);
        }

        $userId = $request->input('honeywellId');

        $user = User::with('roles')->where('Honeywell_Id', 'LIKE', $userId)->get();

        if( $user->count() == 0 ) {
            $user = Contact::with(['roles' => function($query) {
                $query->whereRaw('Active_YN_Flag = "Y"');
            }])->where('Honeywell_Id', 'LIKE', $userId)->get();
        }

        if( $user->count() != 1 ) {
            return response('User not found', 404);
        }

        return array(
            'email' => $user[0]->email,
            'customerId' => $user[0]->customerId,
            'roles' => array_pluck($user[0]->roles, 'name')
        );
    }
}
