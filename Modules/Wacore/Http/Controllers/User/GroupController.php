<?php

namespace Modules\Wacore\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Group;
use App\Models\Contact;
use Auth;
use DB;
class GroupController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $groups =  Group::where('user_id',Auth::id())->withCount('groupcontacts')->latest()->paginate(20);
        $total_groups =  Group::where('user_id',Auth::id())->count();

        $limit  = json_decode(Auth::user()->plan);
        $limit = $limit->group_limit ?? 0;

        return view('wacore::user.group.index',compact('groups','total_groups','limit'));
    }

   

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|max:200',
        ]);

        $group = new Group;
        $group->user_id = Auth::id();
        $group->name = $request->name;
        $group->save();

        return response()->json([
                'message'  => __('Group Created Successfully'),
                'redirect' => route('user.group.index')
            ], 200);

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
            'name' => 'required|max:200',
        ]);

        $group =  Group::where('user_id',Auth::id())->findorFail($id);
        $group->name = $request->name;
        $group->save();

        return response()->json([
                'message'  => __('Group Update Successfully'),
                'redirect' => route('user.group.index')
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
        
        DB::beginTransaction();
        try {
            
            $group =  Group::where('user_id',Auth::id())->with('groupcontacts')->findorFail($id);

            $contacts=[];

            foreach ($group->groupcontacts as $key => $row) {
                array_push($contacts, $row->contact_id);
            }

            $group->delete();

            Contact::whereIn('id',$contacts)->where('user_id',Auth::id())->delete();

            DB::commit();

        } catch (Throwable $th) {
            DB::rollback();

            return response()->json([
                'message' => $th->getMessage()
            ], 500);
        }


        return response()->json([
                'message'  => __('Group Deleted Successfully'),
                'redirect' => route('user.group.index')
            ], 200);
    }
}
