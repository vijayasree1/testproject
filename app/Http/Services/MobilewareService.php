<?php
namespace App\Http\Services;
use GuzzleHttp\Client;
use App\Http\Models\JxMetric;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Monolog\Logger;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Response;
ini_set('MAX_EXECUTION_TIME', 10000);
Class MobilewareService
{
    var $client = null;
    
    var $requestOptions = [];
    public function __construct()
    {
        $this->requestOptions = config('airtimelink.requestDefaultOptions');
        $clientConfig = [
            'base_uri' => config('airtimelink.baseUri')
        ];
        if( config('app.debug') )
        {
            $stack = HandlerStack::create();

            $logger = with(new \Monolog\Logger('api-consumer'))->pushHandler(
                new \Monolog\Handler\StreamHandler(storage_path('/logs/mobileware-call-log.log'), Logger::DEBUG, true)
            );

            $stack->push(
                Middleware::log( $logger, new MessageFormatter('{req_headers} {req_body} - {res_headers} {res_body}') )
            );

            $clientConfig['handler'] = $stack;
        }
        $this->client = new Client($clientConfig);
    }
    
    public function getTrafficData($request)
    {
        try
        {
            $packageId = $request['packageId'];
            $startDateTime = $request['start'];
            $monthWiseDates = $this->monthWiseDates($startDateTime,'1 month');
            $response = [];
            foreach($monthWiseDates as $dates)
            {
                $startDate = $dates['start'];
                $endDate = $dates['end'];
                $responseData = $this->client->request('GET','igx/assurance/traffic?package-id='.$packageId.'&start='.$startDate.'&end='.$endDate, $this->requestOptions);
                $responseData->getBody()->rewind();
                $response[] = $responseData->getBody()->getContents();
            }
            return $response;
        } 
        catch (\Exception $ex) 
        {
            throw $ex;
        }
    }
    
    public function getRealTimeData($request)
    {
        try
        {
            $packageId = $request['packageId'];
            $metricIds = $this->getMetricData();
            $monthWiseDates = $this->monthWiseDates( $request['start'], '1 day' );
            $response = [];
            foreach($monthWiseDates as $dates)
            {
                $startDateTime = $dates['start'];
                $endDateTime = $dates['end'];
                $Url = 'igx/assurance/real-time?package-ids=' . $packageId . '&metric-ids=' . implode( ',', $metricIds ) . '&start=' . $startDateTime . '&end=' . $endDateTime;
                $responseData = $this->client->request( 'GET', $Url, $this->requestOptions );
                $responseData->getBody()->rewind();
                $response[] = $responseData->getBody()->getContents();
            }

            return $response;
        }
        catch(\Exception $ex)
        {
            throw $ex;
        }
    }
    
    public function getCDRData($request)
    {
        try
        {
            $packageId = $request['packageId'];
            $metricIds = $this->getMetricData();
            $monthWiseDates = $this->monthWiseDates($request['start'],'3 day');
            $response = [];
            foreach($monthWiseDates as $dates)
            {
                $startDate = $dates['start'];
                $endDate = $dates['end'];
                $Url = 'igx/assurance/cdr?package-ids='.$packageId.'&metric-ids='. implode( ',', $metricIds).'&start='.$startDate.'&end='.$endDate;
                $responseData = $this->client->request('GET', $Url, $this->requestOptions);
                $responseData->getBody()->rewind();
                $response[] = $responseData->getBody()->getContents();
            }
            
            return $response;
        } 
        catch (\Exception $ex) 
        {
            throw $ex;
        }
    }
    
    public function getSubscriptionData($packageId)
    {
        try
        {
            $Url = 'list/subscription?package-id='.$packageId;
            $response = $this->client->request('GET', $Url, $this->requestOptions);
            $response->getBody()->rewind();
            return $response->getBody()->getContents();
        } 
        catch (\Exception $ex) 
        {
            throw $ex;
        }
    }
    
    public function getSubscriptionDataByPackageId($packageId)
    {
        try
        {
            $Url = 'list/subscription?package-id='.$packageId;
            $response = $this->client->request('GET', $Url, $this->requestOptions);
            $response->getBody()->rewind();
            return $response->getBody()->getContents();
        }
        catch (\Exception $ex)
        {
            throw $ex;
        }
    }
    
    public function getPackageIds()
    {
        $allPackages = DB::table('jx_subscription_raw as jsr')->select('jsr.package-id')->join('jx_packages as jp','jp.templateid','=','jsr.template-id')
                                                           ->whereRaw("(jsr.status='active' OR DATE(jsr.`disconnected-at`) > DATE_SUB(DATE(NOW()), INTERVAL 1 DAY))")
                                                           ->where('jp.type', '=','commercial')
                                                           ->distinct()->get();
        $packageIds = [];
        foreach($allPackages as $packages)
        {
            $packages =(array)$packages;
            $packageIds[] = $packages['package-id'];
        }
        return $packageIds;
    }
    
    private function getMetricData()
    {
        $activeMetrics = JxMetric::select('metric_id')->where('status','=','Active')->get()->toArray();
        $metricIds = [];
        foreach($activeMetrics as $metrics)
        {
            $metricIds[] = $metrics['metric_id'];
        }
        return $metricIds;
    }
    
    public function monthWiseDates($date, $interval='1 month')
    {
        $startDate = date('Y-m-d', strtotime($date));
        $begin = new \DateTime(!is_null($startDate)?$startDate:'2016-08-01');
        $end = new \DateTime();
        $intervalValue = \DateInterval::createFromDateString($interval);
        $period = new \DatePeriod($begin, $intervalValue, $end);
        $dates = [];
        $periodRow = [];
        $count =iterator_count($period);
        //arraty of dates created between $begin and $end with the given $intervalValue
        foreach($period as $key=>$start)
        {
            //take 1 date as start(if only one date assume end date as today)
            if($key==0)
            {
                $periodRow['start'] = $start->format('Ymd');
                $periodRow['end'] = date('Ymd');
            }
            //overide end date with second array value
            elseif($key==1)
            {
                $periodRow['end'] = ($interval == '1 day') ? $periodRow['start'] : $start->format( 'Ymd' );
            }
            //for consecutive start & end, take start as end of previous value plus 1day & end as array next value
            else
            {
                $periodRow['start'] = date('Ymd',strtotime("+1 day", strtotime($periodRow['end'])));
                $periodRow['end'] = ($interval == '1 day') ? $periodRow['start'] : $start->format( 'Ymd' );
            }
            //save dates when start & end is set or save only one period is present
            if($key>=1 || $count == 1)
            {
                $dates[] = $periodRow;
            }
        }
        //include one more record of current month
        $lastDate = last($dates);
        if(count($dates) > 0 && $lastDate['end'] < date('Ymd'))
        {
            $dates[] = ['start'=> date('Ymd',strtotime("+1 day", strtotime($lastDate['end']))), 'end' => date('Ymd')];
        }
        return $dates;
    }
    
    /**
     * Insert or update data to avoid duplications
     */
    public function insertOrUpdate($uniqueColumns, $batch, $model)
    {
        foreach($batch as $values)
        { 
            foreach($values as $metrics => $metricValues)
            {
                if(in_array( $metrics, $uniqueColumns))
                {
                    $attributes[$metrics] = $metricValues;
                }
            }
            $model::updateOrInsert($attributes, $values);
        }
    }
    public function listSubscriptions()
    {
        try
        {
            $ipAddresses = $this->client->request( 'GET', 'list/subscription?status=ACTIVE', $this->requestOptions);
    
            if ($ipAddresses->getStatusCode() == Response::HTTP_OK)
            {
                return json_decode($ipAddresses->getBody(),true)['subscriptions'];;
            }
            throw new \Exception($ipAddresses->getBody()->getContents());
        }
        catch (\Exception $e)
        {
            throw new \Exception($e);
        }
    }
    public function subscriptionDetails($subscriptionId)
    {
        try
        {
            $subscriptionDetails = $this->client->request( 'GET', 'subscription/'.$subscriptionId, $this->requestOptions);
            if ($subscriptionDetails->getStatusCode() == Response::HTTP_OK)
            {
                return json_decode($subscriptionDetails->getBody(),true);
            }
            throw new \Exception($subscriptionDetails->getBody()->getContents());
        }
        catch (\Exception $e)
        {
            throw new \Exception($e);
        }
    }
}

