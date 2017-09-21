<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Models\CallDataRecord;
use App\Http\Models\Customer;
use Illuminate\Http\Request;

class CdrController extends Controller
{
    /**
     * @SWG\Get(
     *     path="/logs",
     *     summary="/logs resource",
     *     tags={"logs"},
     *     description="This resource is dedicated to Call Data Records (CDRs).",
     *     operationId="listCdr",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     * @SWG\Parameter(
     *         name="page",
     *         in="query",
     *         description="Filter by Page Number",
     *         required=false,
     *         type="integer",
     *         @SWG\Items(type="integer"),
     *     ),
     * @SWG\Parameter(
     *         name="page_size",
     *         in="query",
     *         description="Number of results Per Page",
     *         required=false,
     *         type="integer",
     *         @SWG\Items(type="integer"),
     *     ),
     * @SWG\Parameter(
     *         name="customerId",
     *         in="query",
     *         description="Filter CDRs by Customer Id",
     *         required=false,
     *         type="integer",
     *     ),
     * @SWG\Parameter(
     *         name="locationId",
     *         in="query",
     *         description="Filter CDRs by Location Id",
     *         required=false,
     *         type="integer",
     *     ),
     * @SWG\Parameter(
     *         name="systemId",
     *         in="query",
     *         description="Filter CDRs by System Id",
     *         required=false,
     *         type="integer",
     *     ),
     * @SWG\Parameter(
     *         name="terminalId",
     *         in="query",
     *         description="Filter CDRs by Terminal Id",
     *         required=false,
     *         type="integer",
     *     ),
     * @SWG\Parameter(
     *         name="startDate",
     *         in="query",
     *         description="Filter CDRs by Start Date",
     *         required=false,
     *         type="string",
     *     ),
     * @SWG\Parameter(
     *         name="endDate",
     *         in="query",
     *         description="Filter CDRs by End Date",
     *         required=false,
     *         type="string",
     *     ),
     * @SWG\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search",
     *         required=false,
     *         type="string",
     *     ),
     * @SWG\Response(
     *         response=200,
     *         description="Success",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(ref="#/definitions/CallDataRecord")
     *         ),
     *     ),
     * @SWG\Response(
     *         response="400",
     *         description="Invalid tag value",
     *     ),
     * @SWG\Response(
     *         response="401",
     *         description="Unauthorized access",
     *     )
     *
     * ),
     */
    
    public function listCdr(Request $request)
    {
        //FIXME: Determine the maximum amount of memory to be allocated for serving the CDR requests.
        ini_set('memory_limit', '-1');

        $cdrQuery = CallDataRecord::query(); //where('Orig_Tech', '=', 'INM-SBB');

        $cdrQuery->orderBy('Date', 'DESC');

        $searchParams = [
            'locationId' => [
                'columnName' => 'call_logs_all.Location_Idx',
                'operator' => '='
            ],
            'systemId' => [
                'columnName' => 'call_logs_all.System_Idx',
                'operator' => '='
            ],
            'terminalId' => [
                'columnName' => 'call_logs_all.Terminal_Idx',
                'operator' => '='
            ],
            'startDate' => [
                'columnName' => 'call_logs_all.Date',
                'operator' => '>='
            ]
        ];

        foreach ($searchParams as $searchParam => $column) {
            if ($request->has($searchParam)) {
                $cdrQuery->where($column['columnName'], $column['operator'], $request->input($searchParam));
            }
        }

        if ($request->has('customerId')) {
            $customer = Customer::find($request->input('customerId'));

            if( $customer->Is_Management_Company == 'Y' )
            {
                $cdrQuery->whereIn('call_logs_all.Customer_Idx', function($query) use($request) {
                    $query->select('Customer_Idx')
                        ->from('mgmt_managed_companies')
                        ->where('Management_Customer_Idx', $request->input('customerId'));
                });

                $cdrQuery->whereIn('call_logs_all.Location_Idx', function($query) use($request) {
                    $query->select('Location_Idx')
                        ->from('mgmt_managed_locations')
                        ->where('Management_Customer_Idx', $request->input('customerId'));
                });
            }
            else
            {
                $cdrQuery->where('call_logs_all.Customer_Idx', '=', $request->input('customerId'));
            }
        }

        if ($request->has('hwContactId')) {
            $cdrQuery->join('group_locations', 'group_locations.Location_Idx', '=', 'call_logs_all.Location_Idx')
                ->join('groups', 'groups.Group_Idx', '=', 'group_locations.Group_Idx')
                ->join('contact_groups', 'contact_groups.Group_Idx', '=', 'groups.Group_Idx')
                ->join('contact', 'contact_groups.Contact_Idx', '=', 'contact.Idx')
                ->where('contact.Honeywell_Id', 'LIKE', $request->input('hwContactId'))
                ->whereRaw('( NOW() BETWEEN IFNULL(contact_groups.Start_Date, NOW()) AND IFNULL(contact_groups.End_Date, NOW()) )')
                ->whereRaw('( NOW() BETWEEN IFNULL(group_locations.Start_Date, NOW()) AND IFNULL(group_locations.End_Date, NOW()) )');
        }

        if ($request->has('endDate')) {
            if (intval(date('His', strtotime($request->input('endDate'))), 10) > 0) {
                $cdrQuery->where('Date', '<=', $request->input('endDate'));
            } else {
                $cdrQuery->where('Date', '<=', $request->input('endDate') . ' 23:59:59');
            }
        }

        //TODO: Might not be the best approach. Revisit this. MyISAM Fulltext index? Current version of InnoDB doesn't support fulltext index.
        if ($request->has('search')) {
            $searchText = $request->input('search');
            $searchableColumns = ['Date', 'Location', 'Terminal_Identity', 'Orig_ID', 'Orig_Tech', 'Orig_Service',
                'Dest_ID', 'Dest_Tech', 'Dest_Service', 'Leso', 'Unit', 'Volume', 'Billed_Status'];

            $cdrQuery->where(function($query) use($searchableColumns, $searchText){
                foreach ($searchableColumns as $searchableColumn) {
                    $query->orWhere($searchableColumn, 'LIKE', $searchText);
                }
            });
        }

        return $cdrQuery->paginate(intval($request->input('page_size', 50)));
    }
}

