<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RentPlans;
use App\Models\LetOutPlans;
use App\Models\plansOrders;
use DB;
use App\Models\planCredit;
use Carbon\Carbon;
use App\Models\invoices;
use App\Models\product;

class PlansController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function get_rent_plans() {
        return $rent_plans = DB::table('rent_plans')->where('status', 'enabled')->get();
    }

    public function get_letout_plans() {
        return $letout_plans = DB::table('let_out_plans')->get();
    }

    public function get_rent_features() {
        //return $rent_features = DB::table('rent_features')->groupBy('feature_name')->get();
        //return $rent_features = DB::table('rent_features')->select(DB::raw('feature_name, group_concat(feature_details) as feature_details'))->groupBy('feature_name')->get();
        //return $rent_features = DB::table('rent_features')->orderBy('feature_id')->select(DB::raw('feature_name, group_concat(feature_details) as feature_details'))->groupBy('feature_name')->get();
        return $rent_features = DB::table('rent_plans')->where('status', 'enabled')->join('rent_features', 'rent_plans.plan_ID','=','rent_features.plan_id')->orderBy('feature_id')->select(DB::raw('feature_name, group_concat(feature_details) as feature_details'))->groupBy('feature_name')->get();
    }

    public function get_letout_features() {
        return $letout_features = DB::table('let_out_features')->orderBy('id')->select(DB::raw('feature_name, group_concat(feature_details) as feature_details'))->groupBy('feature_name')->get();
    }

    public function post_selected_plan(Request $request) {
        $request->validate([
            'user_id' => 'required',
            'user_email' => 'required',
            'plan_type' => 'required',
			'plan_name' => 'required',
            'expected_rent' => 'required',
            'plan_id' => 'required',
            'payment_type' => 'required',
            'plan_price' => 'required'
        ]);

        $order_id = 'OR'.rand (10,100).time();

            $plan = new plansOrders([
                'user_id' => $request->user_id,
                'user_email' => $request->user_email,
                'order_id' => $order_id,
                'plan_type' => $request->plan_type,
                'plan_name' => $request->plan_name,
                'plan_id' => $request->plan_id,
                'expected_rent' => $request->expected_rent,
                'plan_price' => $request->plan_price,
                'payment_type' => $request->payment_type
            ]);

            $plan->save();

            return response()->json([
                'data' => $plan,
                'message' => 'Successfully created Plan Order'
            ], 201);
    }

    public function get_order_details($orderID) {
        return $order_details = DB::table('plans_orders')->where('order_id', $orderID)->get();
    }

    public function get_invoice_details($invoiceID) {
        return $order_details = DB::table('invoices')->where('invoice_no', $invoiceID)->get();
    }

    public function get_user_invoices($emailID) {
        return $invoice_details = DB::table('invoices')->where([
            ['user_email', $emailID],
            ['plan_status', 'available']
        ])->get();
    }

    public function get_credit_details($email) {
        return $credit_details = DB::table('plan_credits')->where('user_email', $email)->get();
    }

    public function get_total_credit($email) {
        return $credit_details = DB::table('total_credits')->where('user_email', $email)->get();
    }

    public function get_property_details($productID) {
        return $property_details = DB::table('products')->where('product_uid', $productID)->get();
    }

    public function update_property_details($productID) {
        product::where('product_uid', $productID)->update(['enabled' => 'yes']);

        return response()->json([
            'message' => 'Product details updated'
        ], 201);
    }

    public function update_invoice_details(Request $request) {
        
        invoices::where('invoice_no', $request->invoice_no)->update(['plan_status' => 'used', 'property_uid' => $request->product_id, 'property_amount' => $request->product_price]);

        return response()->json([
            'message' => 'Invoice details updated'
        ], 201);
    }

    public function generate_invoice(Request $request) {
        try {

            $order_details = DB::table('plans_orders')->where('order_id', $request->orderID)->get();
            $payment_type = $order_details[0]->payment_type;
    
            if($order_details[0]->invoice_no == NULL)
            {
                $year = Carbon::now()->format('y');
                $month = Carbon::now()->format('m');
                $day = Carbon::now()->format('d');
                $hour = Carbon::now()->format('h');
                $minute = Carbon::now()->format('i');
                $second = Carbon::now()->format('s');
                $invoice_id = 'INV' . $year . $month . $day . $hour . $minute . $second;
                plansOrders::where('order_id', $request->orderID)->update(['invoice_no' => $invoice_id, 'payment_status' => 'UNPAID']);
            }
            else {
                $invoice_id = $order_details[0]->invoice_no;
            }

            $credit = new planCredit([
                'user_id' => $order_details[0]->user_id,
                'user_email' => $order_details[0]->user_email,
                'payment_status' => 'UNPAID',
                'credits' => $order_details[0]->expected_rent,
                'invoice_no' => $invoice_id
            ]);

            $credit->save();

            if($payment_type == 'Post') {

                $todayDate = Carbon::now()->format('Y-m-d');

                $invoice = new invoices([
                    'invoice_no' => $invoice_id,
                    'plan_name' => $order_details[0]->plan_name,
                    'plan_id' => $order_details[0]->plan_id,
                    'plan_type' => $order_details[0]->plan_type,
                    'payment_type' => $order_details[0]->payment_type,
                    'order_id' => $order_details[0]->order_id,
                    'expected_rent' => $order_details[0]->expected_rent,
                    'plan_price' => $order_details[0]->plan_price,
                    'payment_status' => 'UNPAID',
                    'user_email' => $order_details[0]->user_email,
                    'user_id' => $order_details[0]->user_id,
                    'invoice_generated_date' => $todayDate,
                    'payment_received' => 'No'  
                ]);

                $invoice->save();
                /*$total_credit = DB::table('total_credits')->where('user_email', $order_details[0]->user_email)->get();
            
                if($total_credit->isEmpty()) {
                    
                    $tot_credit = new totalCredits([
                        'user_id' => $order_details[0]->user_id,
                        'user_email' => $order_details[0]->user_email,
                        'total_credits' => $order_details[0]->expected_rent
                    ]);

                    $tot_credit->save();
                }
                else {
                    $existing_credit = $total_credit[0]->total_credits;
                    $new_credit = $existing_credit + $order_details[0]->expected_rent;
                    totalCredits::where('user_email', $order_details[0]->user_email)->update(['total_credits' => $new_credit]);
                } */
            }
            else if ($payment_type == 'Advance') {
                $todayDate = Carbon::now()->format('Y-m-d');

                $invoice = new invoices([
                    'invoice_no' => $invoice_id,
                    'plan_name' => $order_details[0]->plan_name,
                    'plan_id' => $order_details[0]->plan_id,
                    'plan_type' => $order_details[0]->plan_type,
                    'payment_type' => $order_details[0]->payment_type,
                    'order_id' => $order_details[0]->order_id,
                    'expected_rent' => $order_details[0]->expected_rent,
                    'plan_price' => $order_details[0]->plan_price,
                    'payment_status' => 'UNPAID',
                    'user_email' => $order_details[0]->user_email,
                    'user_id' => $order_details[0]->user_id,
                    'invoice_generated_date' => $todayDate,
                    'payment_mode' => 'Cash',
                    'payment_received' => 'Pending'  
                ]);

                $invoice->save();
            }
            
            return response()->json([
                'data' => $invoice_id
            ], 201);
            
            }
        catch (\Exception $e) {
            return $this->getExceptionResponse($e);
        }
        

    }
}
