<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\Dotenv;
use Http;
use File;
use Session;
class UpdateController extends Controller
{
    use Dotenv;

    public function __construct(){
      $this->middleware('permission:developer-settings'); 
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('admin.update.index');
    }

    

    /**
     * check new update.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $site_key=env('SITE_KEY');
        $body['purchase_key'] = $site_key;
        $body['url'] = url('/');
        $body['current_version'] = env('APP_VERSION',1);

        $response =  \Http::post('https://devapi.lpress.xyz/api/check-update',$body);
        $body = json_decode($response->body());
        
        if ($response->status() != 200) {
            \Session::flash('error',$body->message);

            return response()->json([
                'redirect'=>url('/admin/update'),
                'message'=>$body->message
            ],200);
        }

        \Session::put('update-data',[
                'message'=>$body->message,
                'version'=>$body->version
        ]);
        return response()->json([
                'redirect'=>url('/admin/update'),
            ],200);

    }

    public function update($version)
    {

        $site_key=env('SITE_KEY');
        $body['purchase_key'] = $site_key;
        $body['url'] = url('/');
        $body['version'] = $version;

        $response =  \Http::post('https://devapi.lpress.xyz/api/pull-update',$body);
        $response = json_decode($response->body());
       
        foreach ($response->updates ?? [] as $key => $row) {
            if ($row->type == 'file') {
                $fileData = Http::get($row->file);
                $fileData = $fileData->body();

                File::put(base_path($row->path),$fileData);
            }
            elseif ($row->type == 'folder') {
                $path = $row->path.'/'.$row->name;

                if(!File::exists(base_path($path))) {                    
                    File::makeDirectory(base_path($path), 0777, true, true);
                }
            }
            elseif ($row->type == 'command') {
                \Artisan::call($row->command);
            }
            elseif ($row->type == 'query') {
                \DB::statement($row->name);
            }
            else{
                $row->name;
            }

            
        }

        $this->editEnv('APP_VERSION', $version);

        Session::forget('update-data');
        Session::flash('success','Successfully updated to '.$version);

        return response()->json([
                'redirect'=>url('/admin/update'),
            ],200);



    }


    

    
}
