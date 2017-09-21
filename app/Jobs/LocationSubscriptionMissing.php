<?php

namespace App\Jobs;

use App\Jobs\Job;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Http\Models\DBManNotification;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class LocationSubscriptionMissing extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        $Query_Data = DB::select(DB::raw( 'select * from locations_missing_subscription where 1' ));

        $Query_Data = array_map(function ($value) {
            return (array)$value;
        }, $Query_Data);

            if (count( $Query_Data ) > 0)
            {
                	
                Mail::send(
                        'emails.sbb-subscription-location-missing-notification', [
                            'expiryDetails' => $Query_Data,
                        ], function ($message) {
                            	
                            $usersToNotify = DBManNotification::with(['user'])
                            ->where('Notification_Type', '=', 'SUBSCRIPTION_ORPHAN')
                            ->whereRaw('Start_Date < NOW() AND IFNULL(End_Date, NOW()) >= NOW()')
                            ->get()->toArray();


                            $message->from('noreply@honeywell.com', 'DBMan')
                            ->subject('DBMan ' . (App::environment('Production') ? '':
                                    '(' . strtoupper( App::environment() ) . ')' ) .
                                    ' - SBB Subscription Location Missing Notification');
                            $message->to(array_pluck($usersToNotify, 'email'));
                            $message->priority(2);
                        });
            }
    }


}
