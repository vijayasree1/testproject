<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Models\Invoice;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    /**
    * @SWG\Get(
    *     path="/invoices",
    *     summary="/invoices resource",
    *     tags={"invoices"},
    *     description="This resource is dedicated to querying Customer Invoices.",
    *     operationId="listInvoices",
    *     consumes={"application/json"},
    *     produces={"application/json"},
          @SWG\Parameter(
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
    *         in="query",
    *         description="Filter Invoices by Customer Id",
    *         required=false,
    *         type="integer",
    *     ),
    *     @SWG\Parameter(
    *         name="paidStatus",
    *         in="query",
    *         description="Filter Invoices by Paid Status",
    *         required=false,
    *         type="string",
              enum={"PAID", "NOT_PAID", "PAID_TOO_MUCH", "NOT_PAID_IN_FULL", "IN_PROGRESS", "PRELIMINARY",  "DISCARDED", "CREDITED"},
    *     ),
    *     @SWG\Response(
    *         response=200,
    *         description="Success",
    *         @SWG\Schema(
    *             type="array",
    *             @SWG\Items(ref="#/definitions/Invoice")
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
    public function listInvoices(Request $request)
    {
        $invoicesQuery = Invoice::query();
        $invoicesQuery->distinct();

        $invoicesQuery->join('customer', 'customer.Idx', '=', 'Customer_Idx')
            ->orderBy('Invoice_Date', 'DESC');

        if ($request->has('customerId')) {
            $invoicesQuery->where('Customer_Idx', '=', $request->input('customerId'));
        }
        if ($request->has('paidStatus')) {
            $invoicesQuery->where('Paid_Status', '=', $request->input('paidStatus'));
        }

        return $invoicesQuery->paginate(intval($request->input('page_size', 50))); //->toSql()
    }

    /**
        * @SWG\Get(
        *     path="/invoices/{invoiceNumber}/download",
        *     summary="/invoices download resource",
        *     tags={"invoices"},
        *     description="This resource is dedicated to downloading Customer Invoices.",
        *     operationId="downloadInvoice",
        *     consumes={"application/json"},
        *     produces={"application/json"},
              @SWG\Parameter(
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
        *         name="invoiceNumber",
        *         in="path",
        *         description="Invoice Number",
        *         required=true,
        *         type="string",
        *         @SWG\Items(type="string"),
        *     ),
        *     @SWG\Parameter(
        *         name="fileType",
        *         in="query",
        *         description="Download Pdf or Xls",
        *         required=true,
                  type="string",
                  enum={"pdf", "xls"},
        *         @SWG\Items(type="string"),
        *     ),
        *     @SWG\Response(
        *         response=200,
        *         description="Success",
        *         @SWG\Schema(
        *             type="array",
        *             @SWG\Items(ref="#/definitions/Invoice")
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
    public function downloadInvoice(Request $request, $invoiceNumber)
    {
        $invoicesQuery = Invoice::where('Invoice_No', '=', $invoiceNumber);

        if ($request->has('customerId')) {
            $invoicesQuery->where('Customer_Idx', '=', $request->input('customerId'));
        }

        if( $invoicesQuery->count() == 0 ) {
            return response('Incorrect invoice number', 404);
        }

        if ($request->has('fileType')) {
            $fileType = strtolower($request->input('fileType'));
        }
        else
        {
            $fileType = 'pdf';
        }

        if ( $fileType != 'pdf' && $fileType != 'xls' )
        {
            return response('Invalid file type', 400);
        }

        if( $fileType == 'pdf' )
        {
            return response()->download(env('INVOICES_LOCATION') . DIRECTORY_SEPARATOR . $invoiceNumber . '.pdf', $invoiceNumber . '.pdf');
        }
        else if ( $fileType == 'xls' )
        {
            return response()->download(env('INVOICES_LOCATION') . DIRECTORY_SEPARATOR . $invoiceNumber . '.xlsx', $invoiceNumber . '.xlsx');
        }

        return $invoiceNumber;
    }
}

