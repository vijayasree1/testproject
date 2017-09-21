<?php

namespace App\Jobs;

use App\Jobs\Job;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Http\Services\MobilewareService;
use App\Http\Models\JxRealTime;
use App\Http\Models\DBManNotification;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;

class RealTimeData extends Job implements ShouldQueue
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
                $request = ['packageId'=> $packageId,'start' =>$startDate];
                $realTimeDataResponse = $mobilewareService->getRealTimeData($request);
                foreach($realTimeDataResponse as $data)
                {
                    $response = json_decode($data, true);
                    if(strcasecmp( $response['status-code'], 'AIRTIME-P4000' ) === 0)
                    {
                        $this->saveRealTimeData($response,$packageId);
                    }
                }
            }
        } catch (\Exception $ex) {
            echo $ex->getMessage()." ".$ex->getLine()." ".$ex->getFile();
            
            $exception = $ex->getMessage();
            Mail::send(
                'emails.jx-realtime-notification', [
                'exception' => $exception,
            ], function ($message) {
                $usersToNotify = DBManNotification::with(['user'])
                ->where('Notification_Type', '=', 'JX_DATA_SYNC')
                ->whereRaw('Start_Date < NOW() AND IFNULL(End_Date, NOW()) >= NOW()')
                ->get()->toArray();
                
                $message->from('noreply@honeywell.com', 'DBMan')
                        ->subject('DBMan ' . (App::environment('production') ? '':
                            '(' . strtoupper( App::environment() ) . ')' )  .
                            ' - JX Subscription Realtime data sync notification');
                $message->to(array_pluck($usersToNotify, 'email'));
                $message->priority(2);
            });
        }
        
    }

    private function getStartDateTime($packageId)
    {
        $dateTime = JxRealTime::where('package-id','=',$packageId)->orderBy('time', 'desc')->first();
        $maxAvailableDateTime = !is_null($dateTime)?$dateTime->time:null;
        if(!is_null($maxAvailableDateTime))
        {
            $startDate = strtotime($maxAvailableDateTime);
            $currentDate = strtotime(date('Y-m-d H:i:s'));
            $diff = $currentDate - $startDate;
            $hours = round($diff / ( 60 * 60 ));
            if($hours > 48)
            {
                $maxAvailableDateTime = date('YmdHis', strtotime('-2 days', strtotime(date('YmdHis'))));
            }
            else
            {
                $maxAvailableDateTime = date('YmdHis', strtotime($maxAvailableDateTime));
            }
        }
        else
        {
            $maxAvailableDateTime = date('YmdHis', strtotime('-2 days', strtotime(date('YmdHis'))));
        }
        
        return $maxAvailableDateTime;
    }

    private function saveRealTimeData($data,$packageId)
    {
        $insertRows = [];
        $requiredParamNames = ['element-id', 'element-name', 'metric-id', 'metric-unit','time', 'value'];
        $uniqueColumns = ['element-id', 'package-id', 'metric-id', 'time'];
        if(isset($data['sspc-statistic']))
        {
            foreach($data['sspc-statistic'] as $value)
            {
                if(strpos($value['element-name'], 'RegLatory') !== false)
                {
                    continue;
                }
                $rowData = [];
                $relTimeDetails = ! is_null($value['details'])?$value['details']:[];
                unset($value['details']);
                $rowData['package-id'] = $packageId;
                foreach($value as $columnName => $columnValue)
                {
                    if(! is_null( $columnValue ) && in_array( $columnName, $requiredParamNames))
                    {
                        $rowData[$columnName] = $columnValue;
                    }
                }
                foreach($relTimeDetails as $detail)
                {
                    foreach($detail as $columnName => $columnValue)
                    {
                        $rowData[$columnName] = $columnValue;
                    }
                    $insertRows[] = $rowData;
                }    
            }
        }
        if(isset($data['terminal-status']))
        {
            foreach($data['terminal-status'] as $value)
            {
                $rowData = [];
                $relTimeDetails = ! is_null($value['details'])?$value['details']:[];
                unset($value['details']);
                $rowData['package-id'] = $packageId;
                foreach($value as $columnName => $columnValue)
                {
                    if(! is_null( $columnValue ) && in_array( $columnName, $requiredParamNames))
                    {
                        $rowData[$columnName] = $columnValue;
                    }
                }
                foreach($relTimeDetails as $detail)
                {
                    foreach($detail as $columnName => $columnValue)
                    {
                        $rowData[$columnName] = $columnValue;
                    }
                    $insertRows[] = $rowData;
                }    
            }
        }
        if(count($insertRows) > 0)
        {
            $mobilewareService = new MobilewareService;
            $mobilewareService->insertOrUpdate($uniqueColumns, $insertRows, '\App\Http\Models\JxRealTime');
        }
        else
        {
            echo "Error: No data found\n";
        }
    }
}
