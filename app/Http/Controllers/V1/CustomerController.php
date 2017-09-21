<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Models\Customer;
use App\Http\Models\CabinBillingPurchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    /**
     * @SWG\Get(
     *     path="/customers",
     *     summary="/customers resource",
     *     tags={"customers"},
     *     description="This resource is dedicated to querying data around Customers.",
     *     operationId="listCustomers",
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
     *         name="hwContactId",
     *         in="query",
     *         description="Filter Customer by Honeywell Id",
     *         required=false,
     *         type="integer",
     *         @SWG\Items(type="string"),
     *     ),
     *     @SWG\Parameter(
     *         name="lastSyncDate",
     *         in="query",
     *         description="Filter by Last Updated Date",
     *         required=false,
     *         type="string",
     *         format="date-time",
     *     ),
     * @SWG\Response(
     *         response=200,
     *         description="Success",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(ref="#/definitions/Customer")
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
    public function listCustomers(Request $request)
    {
        $this->validate($request, [
            'lastSyncDate' => 'date_format:Y-m-d H:i:s|regex:/[0-9]{4}\-/',
        ]);
        
        $customersQuery = Customer::with('email_recipients');

        if ($request->has('hwContactId')) {
            $customersQuery->join('contact', 'contact.Customer_Idx', '=', 'customer.Idx');
            $customersQuery->where('contact.Honeywell_Id', 'LIKE', $request->input('hwContactId'));

            if ($customersQuery->count() != 1) {
                return response('Honeywell ID is not associated with any customer.', 404); //TODO: Update error message here.
            }
        }
        
        if ($request->has('lastSyncDate')) {
             $customersQuery->where('Updated_On', '>', $request->input('lastSyncDate'));
        }
        
        if ($request->has('sortByAsc')) {
            $sortByAsc = $request->input('sortByAsc');
            $sortByColumns = ['Idx', 'Company'];
            $customersQuery->orderBy($sortByAsc, 'ASC');
        }
        if ($request->has('sortByDesc')) {
            $sortByDesc = $request->input('sortByDesc');
            $sortByColumns = ['Idx', 'Company'];
            $customersQuery->orderBy($sortByDesc, 'DESC');
        }

        return $customersQuery->groupBy('customer.Idx')->paginate(intval($request->input('page_size', 50)));
    }

    /**
     * @SWG\Get(
     *     path="/customers/{customerId}",
     *     summary="/customers with Id resource",
     *     tags={"customers"},
     *     description="This resource is dedicated to querying data around Customers.",
     *     operationId="listCustomersWithId",
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
     *         name="customerId",
     *         in="path",
     *         description="Filter by Customer Id",
     *         required=true,
     *         type="integer",
     *         @SWG\Items(type="integer"),
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Success",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(ref="#/definitions/Customer")
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
    public function listCustomersWithId($customerId)
    {
        $customersQuery = Customer::query();
        $customersQuery->distinct()
            ->select(DB::raw('customer.*'))
            ->where('customer.Idx', '=', $customerId)
            ->whereNull('customer.Customer_Ends');

        $customer = $customersQuery->with('sales_rep')->first();

        if ($customer == null) {
            return response('Customer Not Found', 404);
        }

        return $customer;
    }

    /**
     * @SWG\Get(
     *     path="/customers/{customerId}/cabinbilling/purchases",
     *     summary="Cabin billing purchases of a customer",
     *     tags={"customers"},
     *     description="This resource is dedicated to querying cabin billing purchases of a customers.",
     *     operationId="listCabinBillingPurchases",
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
     *         name="customerId",
     *         in="path",
     *         description="Filter by Customer Id",
     *         required=true,
     *         type="integer",
     *         @SWG\Items(type="integer"),
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Success",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(ref="#/definitions/CabinBillingPurchases")
     *         ),
     *     ),
     *     @SWG\Response(
     *         response="401",
     *         description="Unauthorized access",
     *     )
     *
     * ),
     */
    public function listCabinBillingPurchases($customerId, Request $request)
    {
        $customer = Customer::find($customerId);

        if ($customer == null) {
            return response('Customer Not Found', 404);
        }

        return CabinBillingPurchase::where('Customer_Idx', '=', $customerId)->paginate(intval($request->input('page_size', 50)));
    }
}

