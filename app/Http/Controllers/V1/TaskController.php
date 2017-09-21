<?php

namespace App\Http\Controllers\V1;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Http\Models\Task;

class TaskController extends Controller
{
    /**
     * @SWG\Get(
     *     path="/tasks",
     *     summary="/tasks resource",
     *     tags={"tasks"},
     *     description="This resource is dedicated to querying Tasks.",
     *     operationId="listTasks",
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
     *         description="Filter tasks by status",
     *         required=false,
     *         type="integer",
     *         @SWG\Items(type="string")
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
     *         response="401",
     *         description="Unauthorized access",
     *     )
     * ),
     */
    public function listTasks(Request $request) 
    {
        $taskQuery = Task::query();

        if( $request->has('status') ) {
            $taskQuery->where('status', '=', $request->input('status'));
        }

        return $taskQuery->orderBy('createdOn', 'DESC')->paginate(intval($request->input('page_size', 50)));
    }
}
