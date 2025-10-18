<?php

namespace Modules\Wacore\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Webhook;
use Auth;
class WebhookController extends Controller
{
    public function index()
    {
        $hooks = Webhook::where('user_id',Auth::id())->with('device')->latest()->paginate(50);
        $total= Webhook::where('user_id',Auth::id())->count();
        $sent_hooks= Webhook::where('user_id',Auth::id())->where('status',1)->count();
        $failed= Webhook::where('user_id',Auth::id())->where('status',0)->count();

        return view('wacore::user.webhook.index',compact('hooks','total','sent_hooks','failed'));
    }
}
