<?php

namespace App\Jobs;

use App\Http\Models\DBManNotification;
use App\Http\Models\JxCDR;
use App\Http\Models\Subscription;
use App\Http\Services\MobilewareService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class CDRData extends Job implements ShouldQueue
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
            set_time_limit(600);
            $mobilewareService = new MobilewareService;
            $packageIds = $mobilewareService->getPackageIds();
            foreach($packageIds as $packageId)
            {
                $startDate = $this->getStartDateTime($packageId);
                $request = ['packageId'=> $packageId,'start' => $startDate];
                $cdrData = $mobilewareService->getCDRData($request);
                foreach($cdrData as $data)
                {
                    $response = json_decode($data, true);
                    if(strcasecmp( $response['status-code'], 'AIRTIME-P4000' ) === 0 && ! is_null( $response['igx-assurance-cdr'] ))
                    {
                        $this->saveCDRData($response);
                    }
                }
            }
        }
        catch (\Exception $ex) 
        {
            echo $ex->getMessage().' '.$ex->getLine()." ".$ex->getFile();
            
            $exception = $ex->getMessage();
            Mail::send(
                'emails.jx-cdrdata-notification', [
                'exception' => $exception,
            ], function ($message) {
                $usersToNotify = DBManNotification::with(['user'])
                ->where('Notification_Type', '=', 'JX_DATA_SYNC')
                ->whereRaw('Start_Date < NOW() AND IFNULL(End_Date, NOW()) >= NOW()')
                ->get()->toArray();
                
                $message->from('noreply@honeywell.com', 'DBMan')
                        ->subject('DBMan ' . (App::environment('production') ? '':
                            '(' . strtoupper( App::environment() ) . ')' )  .
                            ' - JX Subscription CDR data sync notification');
                $message->to(array_pluck($usersToNotify, 'email'));
                $message->priority(2);
            });
        }
    }

    private function getStartDateTime($packageId)
    {
        $dateTime = JxCDR::where('package-id','=',$packageId)->orderBy('timestamp', 'desc')->first();
        $maxAvailableDateTime = !is_null($dateTime)?$dateTime->timestamp:null;
        if(is_null($maxAvailableDateTime))
        {
            $maxAvailableDateTime = Subscription::join('subscription_package as sp','Subscription_Idx','=','sp.subscription_id')->where('sp.package_id','=',$packageId)->min('Subscription_Start_Date');
        }
        return !is_null($maxAvailableDateTime)?$maxAvailableDateTime:'2016-08-01';
    }

    private function saveCDRData($cdrData)
    {
        $metricNames = ['metric-id', 'unit', 'terminal-provisioning-key', 'customer-name', 'value', 'element-type', 'element-id', 'element-name', 'timestamp','package-id']; 
        //$uniqueColumns = ['metric-id', 'terminal-provisioning-key', 'element-id', 'package-id', 'timestamp'];
        $updateColumns = ['unit', 'customer-name', 'value', 'element-type', 'element-name'];
        foreach($cdrData['igx-assurance-cdr'] as $row)
        {
            if(strpos($row['element-name'], 'RegLatory') !== false)
            {
                continue;
            }
            $values = $updateValues = '';
            $partialQuery = "insert into jx_cdr_raw(";
            foreach($row as $name => $value)
            {
                if(in_array( $name, $metricNames))
                {
                    $partialQuery .="`".$name."`,";
                    $values .= "'".$value."',";
                }
                if(in_array( $name, $updateColumns ))
                {
                    $updateValues .="`".$name."`='".$value."',"; 
                }
            }
            $updateValues .= 'mts=now()';
            $query = rtrim($partialQuery,',');
            $query .=') '.' values('.rtrim($values,',').') on duplicate key update '.$updateValues;
            DB::unprepared($query);
        }
        if(count($cdrData) == 0)
        {
            echo 'Error: No data found\n';
        }
    }
    
}
