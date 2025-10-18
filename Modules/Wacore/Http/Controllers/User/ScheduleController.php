<?php

namespace Modules\Wacore\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Schedulemessage;
use App\Models\Schedulecontact;
use App\Models\Template;
use App\Models\Contact;
use App\Models\Device;
use App\Models\Group;
use Carbon\Carbon;
use DateTimeZone;
use Storage;
use Http;
use Auth;
use DB;
class ScheduleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $posts=Schedulemessage::where('user_id',Auth::id())->with('device')->latest()->paginate(20);
        $totalSchedule=Schedulemessage::where('user_id',Auth::id())->count();
        $pendingSchedule=Schedulemessage::where('user_id',Auth::id())->where('status','pending')->count();
        $deliveredSchedule=Schedulemessage::where('user_id',Auth::id())->where('status','delivered')->count();
        $failedSchedule=Schedulemessage::where('user_id',Auth::id())->where('status','rejected')->count();

        return view('wacore::user.schedule.index',compact('posts','totalSchedule','pendingSchedule','deliveredSchedule','failedSchedule'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
       
        $devices=Device::where('user_id',Auth::id())->where('status',1)->latest()->get();
        $templates=Template::where('user_id',Auth::id())->where('status',1)->latest()->get();
        $groups=Group::where('user_id',Auth::id())->whereHas('contacts')->withCount('contacts')->latest()->get();

        return view('wacore::user.schedule.create',compact('devices','templates','groups'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (getUserPlanData('schedule_message') == false) {
            return response()->json([
                 'message'=>__('Schedule message modules is not available your plan')
            ],401);  
        }

          $date = Carbon::parse($request->date);
         // $time= $date->format('g:i A');
         // $date= $date->toDate();
         $dateTime = Carbon::createFromFormat('Y-m-d H:i:s', $date, $request->timezone);
         $dateTime = $dateTime->copy()->tz(env('TIME_ZONE','UTC'));
        $validated = $request->validate([
            'device' => 'required|integer',
            'date' => 'required',
            'timezone' => 'required',
            'title' => 'required|max:100',
            'group' => 'required',
            'message_type' => 'required'
        ]);
        if ($request->message_type != 'template') {
           $validated = $request->validate([
                'message' => 'required|max:1000',
            ]);
        }

       $receivers = Group::where('user_id',Auth::id())->with('groupcontacts')->whereHas('groupcontacts')->findorFail($request->group);

        if ($request->message_type == 'template') {
            $validated = $request->validate([
                'template' => 'required',
            ]);
            $template=Template::where('user_id',Auth::id())->where('status',1)->findorFail($request->template);
        }

        $device=Device::where('user_id',Auth::id())->where('status',1)->findorFail($request->device);

        if ($device->user_id == Auth::id()) {

           
           $receivers_arr=[];

           foreach ($receivers->groupcontacts ?? [] as $key => $receiver) {
               
               array_push($receivers_arr, $receiver->contact_id);
           }
           abort_if(count($receivers_arr) == 0,404);

           

            DB::beginTransaction();
            try {
               

           $schedulemessage=new Schedulemessage;
           $schedulemessage->user_id=Auth::id();
           $schedulemessage->device_id=$request->device;
           $schedulemessage->title=$request->title;
           $schedulemessage->schedule_at=$dateTime;
           $schedulemessage->body= $request->message_type != 'template' ? $request->message : null;
          
           $schedulemessage->zone=$request->timezone;
           $schedulemessage->template_id= $request->message_type == 'template' ? $request->template : null;
           $schedulemessage->save();

           $receivers_arrs=[];
           foreach ($receivers_arr as $key => $receiverr) {
              $arr['contact_id']=$receiverr;
              $arr['schedulemessage_id']=$schedulemessage->id;
              array_push($receivers_arrs, $arr);
           }

           $schedulemessage->schedulecontacts()->insert($receivers_arrs);
            DB::commit();
         
           return response()->json(__('Schedule message created successfully...!!'),200);

           } catch (\Throwable $th) {
                DB::rollback();

                return throw($th);
    
                return response()->json([
                    'message' =>  __('Something was wrong, Please contact with Support.'),
                ], 404);
            }
           
        }
        abort(404);

        
        

    }

    public function filter_body($context)
    {
        $data=str_replace("\\r",'\r',$context);
        $data=str_replace("\\n",'\n',$data);

        return $data;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $info=Schedulemessage::with('device')->where('user_id',Auth::id())->withCount('schedulecontacts')->findorFail($id);
        $contacts=Schedulecontact::where('schedulemessage_id',$id)->whereHas('contact')->with('contact')->paginate(50);
        return view('wacore::user.schedule.show',compact('info','contacts'));
    }

    

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $schedulemessage=Schedulemessage::where('user_id',Auth::id())->findorFail($id);
        
        
        $schedulemessage->delete();
        return response()->json([
            'message' => __('Schedule Deleted Successfully'),
            'redirect' => route('user.schedule-message.index')
        ]);

    }
}
