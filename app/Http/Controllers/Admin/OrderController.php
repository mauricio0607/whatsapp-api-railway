<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Plan;
use App\Models\Order;
use App\Models\User;
use App\Models\Gateway;
use App\Traits\Notifications;
use DB;
use Auth;
class OrderController extends Controller
{
    use Notifications;

    public function __construct()
    {
          $this->middleware('permission:order'); 
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $orders= Order::query();

        if (!empty($request->search)) {
            if ($request->type == 'email') {
                $orders = $orders->whereHas('user',function($q) use ($request){
                    return $q->where('email',$request->search);
                });
            }
            else{
                $orders = $orders->where($request->type,'LIKE','%'.$request->search.'%');
            }
           
        }

        $orders = $orders->with('user','plan','gateway')->latest()->paginate(20);

        $totalOrders= Order::count();
        $totalPendingOrders= Order::where('status',2)->count();
        $totalCompleteOrders= Order::where('status',1)->count();
        $totalDeclinedOrders= Order::where('status',0)->count();
        $type = $request->type ?? '';

        $invoice = get_option('invoice_data',true);
        $currency = get_option('base_currency',true);
        $tax = get_option('tax');

        $plans=Plan::where('status',1)->latest()->get();
        $gateways=Gateway::where('status',1)->latest()->get();

        return view('admin.orders.index',compact('orders','request','totalOrders','totalPendingOrders','totalCompleteOrders','totalDeclinedOrders','type','invoice','currency','tax','plans','gateways'));
    }

    public function store(Request $request)
    {
       $user = User::where('email',$request->email)->first();

       if (empty($user)) {
          
           return response()->json([
                'message'  => __('Sorry user not found')
            ], 401);
       }

       $plan = Plan::findorFail($request->plan);


       

       DB::beginTransaction();
        try {
            
           $order = new Order;
           $order->payment_id = $request->pay_id;
           $order->plan_id = $request->plan;
           $order->gateway_id = $request->gateway;
           $order->amount = $request->amount;
           $order->tax = $request->tax;
           $order->will_expire = now()->addDays($plan->days);
           $order->user_id = $user->id;
           $order->status = 1;
           $order->save();

           $user->will_expire = now()->addDays($plan->days);
           $user->plan = json_encode($plan->data);
           $user->plan_id = $plan->id;
           $user->save();

           $notification['user_id'] = $order->user_id;
           $notification['title']   = 'You have been assigned to a new plan ('.$plan->title.')';
           $notification['url'] = '/user/subscription-history';

           $this->createNotification($notification);

            DB::commit();

            return response()->json([
                'message' => __("Order Created Successfully"),
                'redirect' => route('admin.order.index')
            ]);
        } catch (Throwable $th) {
            DB::rollback();

            return response()->json([
                'message' => $th->getMessage()
            ], 500);
        }

    }

  

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $order  = Order::with('user','plan','gateway')->findorFail($id);
        $invoice_data = get_option('invoice_data',true);

        return view('admin.orders.show',compact('order','invoice_data'));
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
        $order  = Order::with('user','plan')->findorFail($id);
        $order->status = $request->status;
        $order->save();

        if ($request->assign_order == 'yes') {
            $order->user()->update([
                'plan_id'     => $order->plan_id,
                'will_expire' => $order->will_expire,
                'plan'        =>json_encode( $order->plan->data ?? ''),
            ]);
        }

        
        $status = $order->status == 2 ? 'pending' : ($order->status == 1 ? 'approved' : 'declined');
        $title = '('.$order->invoice_no.') Subscription order is '.$status;
        
        $notification['user_id'] = $order->user_id;
        $notification['title']   = $title;
        $notification['url'] = '/user/subscription-history';

        $this->createNotification($notification);

        return response()->json(['message' => __('Order status updated')],200);

    }
}
