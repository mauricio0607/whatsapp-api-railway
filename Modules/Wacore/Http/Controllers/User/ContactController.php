<?php

namespace Modules\Wacore\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Contact;
use App\Models\Template;
use App\Models\Device;
use App\Models\User;
use App\Rules\Phone;
use App\Traits\Whatsapp;
use App\Models\Group;
use App\Models\Groupcontact;
use Auth;
use DB;
class ContactController extends Controller
{
    use Whatsapp;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $total_contacts=Contact::where('user_id',Auth::id())->count();
        $contacts=Contact::where('user_id',Auth::id())->with('groupcontact')->latest()->paginate(20);
        $templates=Template::where('user_id',Auth::id())->where('status',1)->latest()->get();
        $devices=Device::where('user_id',Auth::id())->where('status',1)->latest()->get();
        $limit  = json_decode(Auth::user()->plan);
        $limit = $limit->contact_limit ?? 0;

        if ($limit == '-1') {
            $limit = number_format($total_contacts);
        }
        else{
            $limit = $total_contacts.' / '.$limit;
        }
        
        $groups = Group::where('user_id',Auth::id())->latest()->get();

        return view('wacore::user.contact.index',compact('contacts','total_contacts','templates','devices','limit','groups'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $groups = Group::where('user_id',Auth::id())->latest()->get();
        return view('wacore::user.contact.create',compact('groups'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (getUserPlanData('contact_limit') == false) {
            return response()->json([
                'message'=>__('Maximum Contacts Limit Exceeded')
            ],401);  
        }
        

        $validated = $request->validate([
            'phone'   => ['required', new Phone],
            'name'=>['required','max:20'], 
        ]);

        $is_exist=Contact::where('user_id',Auth::id())->where('phone',$request->phone)->first();
        if (!empty($is_exist)) {
           return response()->json([
                'message'  => __('Contact already exist..!!'),
                'redirect' =>  route('user.contact.index')
            ], 401);
        }

        if ($request->group) {
            $group = Group::where('user_id',Auth::id())->findorFail($request->group);
        }

        $contact= new Contact;
        $contact->name = $request->name;
        $contact->phone = $request->phone;
        $contact->user_id = Auth::id();
        $contact->save();

        if ($request->group) {
        $contact->groupcontacts()->insert(['group_id'=>$request->group,'contact_id'=>$contact->id]);
        }

        return response()->json([
                    'message' => __('New Contact Created Successfully'),
               ], 200);
    }

    

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $info=Contact::where('user_id',Auth::id())->findorFail($id);
        return view('wacore::user.contact.edit',compact('info'));
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
        $validated = $request->validate([
            'phone'   => ['required', new Phone],
            'name'=>['required','max:20'], 
        ]);

        $is_exist=Contact::where('user_id',Auth::id())->where('phone',$request->phone)->where('id','!=',$id)->first();
        if (!empty($is_exist)) {
           return response()->json([
                'message'  => __('Opps this contact number you have already added')
            ], 401);
        }

        $contact=  Contact::where('user_id',Auth::id())->findorFail($id);
        $contact->name = $request->name;
        $contact->phone = $request->phone;
        $contact->user_id = Auth::id();
        $contact->save();

        if ($request->group) {
            $group = Group::where('user_id',Auth::id())->findorFail($request->group);
            $contact->groupcontact()->sync([$request->group]);
        }


        return response()->json([
                    'message' => __('Contact update Successfully'),
                    'redirect' =>  route('user.contact.index')
               ], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $contact=Contact::where('user_id',Auth::id())->findorFail($id);
        $contact->delete();

        return response()->json([
            'message'  => __('Contact deleted successfully..!!'),
            'redirect' =>  route('user.contact.index')
        ], 200);
    }

    public function sendtemplateBulk(Request $request)
    {
       
        if (getUserPlanData('messages_limit') == false) {
            return response()->json([
                'message'=>__('Maximum Monthly Messages Limit Exceeded')
            ],401);  
        }

            $validated = $request->validate([
                'template'=>['required'], 
                'group'   => ['required'],
            ]);

            $group = Group::where('user_id',Auth::id())->whereHas('groupcontacts')->findorFail($request->group);
            $device=Device::where('user_id',Auth::id())->where('status',1)->findorFail($request->device);

            return response()->json([
                'message'  => __('Redirecting to bulk sending page'),
                'redirect' =>  url('user/sent-bulk-with-template/'.$request->template.'/'.$group->id.'/'.$device->uuid)
            ], 200);

            
           // $validated = $request->validate([
           //  'group'   => ['required'],
           //  'template'   => ['required'], 
           // ]);

            // $template=Template::where('user_id',Auth::id())->where('status',1)->findorFail($request->template);
            // $device=Device::where('user_id',Auth::id())->where('status',1)->findorFail($request->device);
            // $contacts=Contact::where('user_id',Auth::id())->whereHas('groupcontacts',function($q) use ($request){
            //     return $q->where('group_id',$request->group);
            // })->get();

            // $user=User::where('id',Auth::id())->first();

            // $logs['template_id']=$template->id;

            // foreach ($contacts as $key => $contact) {
            //   if (isset($template->body['text'])) {

            //     $formatText=$this->formatText($template->body['text'],$contact,$user);
            //     $body=$template->body;
            //     $body['text']=$formatText;
            //     $logs['template_id']=$template->id;

            //   }
            //   else{
            //     $body=$template->body;
            //   }


              
            //   $response = $this->messageSend($body,$device->id,$contact->phone,$template->type,true);

            //   if ($response['status'] == 200) {
            //      $logs['user_id']=$user->id;
            //      $logs['device_id']=$device->id;
            //      $logs['from']=$device->phone ?? null;
            //      $logs['to']=$contact->phone;
            //      $logs['type']='bulk-message';

            //      $this->saveLog($logs);
            //   }
            // }
            
            // return response()->json([
            //     'message'  => __('Message Send Successfully'),
            // ], 200);
        
    }


    public function import(Request $request)
    {

        $validated = $request->validate([
            'file'   => 'required|mimes:csv,txt|max:10',
            
        ]);

        if (getUserPlanData('contact_limit') == false) {
            return response()->json([
                'message'=>__('Maximum Contacts Limit Exceeded')
            ],401);  
        }
        else{
            $contact_limit=json_decode(Auth::user()->plan);
            $contact_limit=$contact_limit->contact_limit;
        }


        if ($request->group) {
            $group = Group::where('user_id',Auth::id())->findorFail($request->group);
        }
        

        $file = $request->file('file');

        $insertable=[];

        // Open the CSV file
        if (($handle = fopen($file, 'r')) !== false) {
        // Read the header row
            $header = fgetcsv($handle);

        // Loop through the remaining rows
            while (($data = fgetcsv($handle)) !== false) {
            // Process the row data
            // ...

            // Example: Create a new record in the database
                $row=array(
                    'name'=>$data[0],
                    'phone'=>$data[1]
                ); 
                array_push($insertable, $row);
                
            }

        // Close the CSV file
            fclose($handle);
           

        }

        $count_contacts=count($insertable);

        if ($contact_limit != -1) {
           $old_rows = Contact::where('user_id',Auth::id())->count();

           $available_rows = $contact_limit-$old_rows;
           


           if ($count_contacts > $available_rows) {
                return response()->json([
                    'message'=>__('Maximum '.$available_rows.' records are available only for create contact')
                ],401);  
           }
        }

        DB::beginTransaction();
        try {
            
            $insertableGroups=[];

              foreach ($insertable as $key => $row) {
                 $contact= new Contact;
                 $contact->name=$row['name'];
                 $contact->phone=$row['phone'];
                 $contact->user_id=Auth::id();
                 $contact->save();

                 $contactRow=array(
                    'contact_id'=>$contact->id,
                    'group_id'=>$request->group ?? null
                ); 
                 array_push($insertableGroups, $contactRow);

             }


             if ($request->group) {
                Groupcontact::insert($insertableGroups);
            }

            DB::commit();

        } catch (Throwable $th) {
            DB::rollback();

            return response()->json([
                'message' => $th->getMessage()
            ], 500);
        }

       
        
        return response()->json([
                'message'  => __('Contact list imported successfully'),
            ], 200);


    }

    
}
