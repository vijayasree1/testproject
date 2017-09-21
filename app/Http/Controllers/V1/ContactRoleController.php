<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Models\ContactRoleMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Response;

class ContactRoleController extends Controller
{
    /**
    * @SWG\Get(
    *     path="/roles",
    *     summary="/roles resource",
    *     tags={"roles"},
    *     description="This resource returns a list of available contact Roles.",
    *     operationId="listRoles",
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
    *             @SWG\Items(ref="#/definitions/Role")
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
    public function listRoles(Request $request)
    {
        $rolesQuery = ContactRoleMaster::query();
        $rolesQuery->distinct()->select('contact_roles_master.*');

        if( $rolesQuery->count() < 1 ) {
            return response('Role not found', 404);
        }

        return $rolesQuery->paginate(intval(Input::input('page_size', 50))); //->toSql()
    }
}

