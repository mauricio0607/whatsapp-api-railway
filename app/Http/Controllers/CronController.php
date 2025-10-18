<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Schedulemessage;
use Carbon\Carbon;
use App\Models\Schedulecontact;
use App\Models\User;
use App\Models\Device;
use App\Models\Webhook;
use App\Traits\Whatsapp;
use App\Traits\Notifications;
use Http;
class CronController extends Controller
{
    use Whatsapp;
    use Notifications;

     /**
     * execute webhook
     *
     * @return \Illuminate\Http\Response
     */
    public function ExecuteWebhook()
    {
       $hooks = Webhook::where('status',2)->latest()->get();

       foreach ($hooks as $key => $row) {
         try {
             $response =  Http::post($row->hook, json_decode($row->payload));
             $responseStatus = $response->status();

              $hook = Webhook::where('id',$row->id)->first();
              $hook->status = $responseStatus == 200 ? 1 : 0;
              $hook->status_code = $responseStatus;
              $hook->save();

         } catch (Exception $e) {
              $hook = Webhook::where('id',$row->id)->first();
              $hook->status = 0;
              $hook->status_code = 500;
              $hook->save();
         }

        
       }

       return "Webhook executed";

    }
     /**
     * execute shedule
     *
     * @return \Illuminate\Http\Response
     */
    public function ExecuteSchedule()
    {
        
      $today=Carbon::now();
      $now = Carbon::parse($today)->tz(env('TIME_ZONE','UTC'));

      $schedulemessages=Schedulemessage::whereHas('contacts')->whereHas('device')->whereHas('user',function($q){
        return $q->where('will_expire','>',now());
      })->with('contacts','device','user','template')->where('schedule_at','<=',$now)->where('status','pending')->get();
      
      $schedulemessagesIds= $schedulemessages->pluck('id')->toArray();
      
      Schedulemessage::where('id',$schedulemessagesIds)->update(['status'=>'processing']);


      foreach ($schedulemessages as $key => $schedulemessage) {

            $schedule=Schedulemessage::where('id',$schedulemessage->id)->with('user','contacts')->first();
           
            $response = $this->sentRequest($schedulemessage);
            if ($response == 200) {
                $schedule->status='delivered';
            }
            else{
                $schedule->status='rejected';
            }

            $schedule->save();       
      }

       return "Cron job executed";
    }


     /**
     * notify to subscribers before expire the subscription
     */
    public function sentRequest($data)
    {
        if (getUserPlanData('messages_limit',$data->user_id) == false) {
            return '';
        }

        if (!empty($data->template)) {
           $template = $data->template;

           if (isset($template->body['text'])) {
            $body = $template->body;
            $user=$data->user;

            $reciverContact['name'] = $data->contacts[0]->name;
            $reciverContact['phone'] = $data->contacts[0]->phone;

            $text=$this->formatText($template->body['text'],$reciverContact,$user);
            $body['text'] = $text;
           }
           else{
            
            $body = $template->body;
            
           }

           $type = $template->type;
           $logs['template_id']=$data->template_id;
        }
        else{
            $user=$data->user;
            $reciverContact['name'] = $data->contacts[0]->name;
            $reciverContact['phone'] = $data->contacts[0]->phone;

            $text=$this->formatText($data->body,$reciverContact,$user);
            
            $body = array('text'=>$text);
            $type = 'plain-text';

            
        }


        
        $device_id=$data->device_id;
        $from = $data->device->phone;
        $status=null;

        foreach ($data->contacts as $key => $contact) {

            try {

                if ($type == 'plain-text') {
                    $response= $this->messageSend($body,$device_id,$contact->phone,$type,true);
                }
                else{
                    if (isset($body['text'])) {
                        $text=$this->formatText($body['text'],$contact);
                        $body['text'] = $text;
                        $message = $body;
                    }
                    else{
                        $message = $body;
                    }

                    $response= $this->messageSend($message,$device_id,$contact->phone,$type,true);
                }

                if ($response['status'] == 200) {
                    $logs['user_id']=$data->user_id;
                    $logs['device_id']=$device_id;
                    $logs['from']=$from;
                    $logs['to']=$contact->phone;
                    $logs['type']='schedule-message';
                    $this->saveLog($logs);
                }


                $status=200;
            } catch (Exception $e) {
                $status=500;
            }
        }

        return $status;
    }


    /**
     * notify to subscribers before expire the subscription
     *
     * @return \Illuminate\Http\Response
     */
    public function notifyToUser()
    {
       $willExpire = today()->addDays(7)->format('Y-m-d');
       $users = User::whereHas('subscription')->with('subscription')->where('will_expire',$willExpire)->latest()->get();

       foreach ($users as $key => $user) {
           $this->sentWillExpireEmail($user);
       }

       return "Cron job executed";
    }

    /**
     * remove junk devices
     *
     * @return \Illuminate\Http\Response
     */
    public function removeJunkDevice()
    {
        $subdays = \Carbon\Carbon::today()->subDays(7);
        $devices = Device::where('phone',null)->whereDate('created_at',$subdays)->delete();

        Webhook::whereDate('created_at',$subdays)->delete();

        return "Cron job executed";
    }

}
