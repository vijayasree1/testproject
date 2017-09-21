<?php

namespace App\Jobs;

use App\Http\Models\DBManNotification;
use App\Http\Models\JxTraffic;
use App\Http\Models\Subscription;
use App\Http\Services\MobilewareService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class TrafficData extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;
    
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try
        {
            $mobilewareService = new MobilewareService;
            $packageIds = $mobilewareService->getPackageIds();
            foreach($packageIds as $packageId)
            {
                $startDate = $this->getStartDateTime($packageId);
                $request = ['packageId'=> $packageId,'start' => $startDate];
                $trafficData = $mobilewareService->getTrafficData($request);
                foreach($trafficData as $data)
                {
                    $response = json_decode($data, true);
                    if(strcasecmp( $response['status-code'], 'AIRTIME-P4000' ) === 0)
                    {
                        $this->saveTrafficData($response);
                    }
                }
                
            }
            
        }
        catch (\Exception $ex) 
        {
            echo $ex->getMessage().' '.$ex->getLine()." ".$ex->getFile();
            
            $exception = $ex->getMessage();
            Mail::send(
                'emails.jx-traffic-notification', [
                'exception' => $exception,
            ], function ($message) {
                $usersToNotify = DBManNotification::with(['user'])
                ->where('Notification_Type', '=', 'JX_DATA_SYNC')
                ->whereRaw('Start_Date < NOW() AND IFNULL(End_Date, NOW()) >= NOW()')
                ->get()->toArray();
                
                $message->from('noreply@honeywell.com', 'DBMan')
                        ->subject('DBMan ' . (App::environment('production') ? '':
                            '(' . strtoupper( App::environment() ) . ')' )  .
                            ' - JX Subscription Traffic data sync notification');
                $message->to(array_pluck($usersToNotify, 'email'));
                $message->priority(2);
            });
        }
    }
    private function getStartDateTime($packageId)
    {
        $dateTime = JxTraffic::where('package-id','=',$packageId)->orderBy('traffic-start', 'desc')->first();
        $maxAvailableDateTime = null;
        if(!is_null($dateTime))
        {
            $dateTimeArr = $dateTime->toArray();
            $maxAvailableDateTime = $dateTimeArr['traffic-start'];
        }
        if(is_null($maxAvailableDateTime))
        {
            $maxAvailableDateTime = Subscription::join('subscription_package as sp','Subscription_Idx','=','sp.subscription_id')->where('sp.package_id','=',$packageId)->min('Subscription_Start_Date');
        }
        return !is_null($maxAvailableDateTime)?$maxAvailableDateTime:'2017-01-01';
    }
    private function saveTrafficData($trafficData)
    {
        $insertData = [];
        $metricNames = ['operator-id','operator-customer-name','site-id','site-customer-name','package-id','traffic-up','traffic-down','traffic-start','traffic-up-unit','traffic-down-unit']; //,'geo-latitude','geo-longitude','latitude-unit','longitude-unit','altitude','altitude-unit' 
        $uniqueColumns = ['operator-id', 'package-id', 'site-id', 'traffic-start'];
        foreach($trafficData['igx-assurance'] as $row)
        {
            $dbrow = null; 
            foreach($row as $name => $value)
            {
                if(in_array( $name, $metricNames))
                {
                    $dbrow[$name] = $value;
                }
            }
            $insertData[] = $dbrow;
        }
        
        if(count($insertData) > 0)
        {
            $mobilewareService = new MobilewareService;
            $mobilewareService->insertOrUpdate($uniqueColumns, $insertData, '\App\Http\Models\JxTraffic');
        }
        else
        {
            echo 'Error: No data found\n';
        }
    }
}
