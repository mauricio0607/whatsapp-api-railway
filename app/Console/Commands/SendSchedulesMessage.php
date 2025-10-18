<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Schedulemessage;
use Carbon\Carbon;
use App\Models\Schedulecontact;
use App\Models\User;
use App\Models\Device;
use App\Traits\Whatsapp;
use App\Traits\Notifications;
class SendSchedulesMessage extends Command
{
    use Whatsapp;
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schedule:send-message';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'this command will execute every minute for sending schedule messages';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
       
        $today=Carbon::now();
          $now = Carbon::parse($today)->tz(env('TIME_ZONE','UTC'));

          $schedulemessages=Schedulemessage::whereHas('contacts')->whereHas('device')->whereHas('user')->with('contacts','device','user','template')->where('schedule_at','<=',$now)->where('status','pending')->get();
          
        

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

            return Command::SUCCESS;
    }
}
