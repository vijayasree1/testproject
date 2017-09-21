<?php
namespace App\Jobs;
use App\Jobs\Job;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use App\Http\Models\Task;
use App\Http\Models\Terminal;
use Illuminate\Support\Facades\DB;

class UpdateSIM extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    private $client;

    protected $task;

    public function __construct (Task $task)
    {
        $this->task = $task;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle ()
    {
        $headers = [
                'Authorization' => 'Token token=UJapyYi4i44LXu788bDdyQ, email=aeroiss@honeywell.com',
                'Content-Type' => 'application/json',
                'accept' => 'application/json'
        ];

        $client = new Client(
                [
                        'base_uri' => env( 'RADMIN_BASE_URI' ),
                        'verify' => false,
                        'headers' => $headers,
                        'http_errors' => false
                ] );

        $radminRequest = array();
        $attributes = array();

        $terminalID = $this->task->Data1;
        $terminal_Data = Terminal::query()->where( 'Idx', '=', $terminalID )->get();
        $terminalIMSI = $terminal_Data[0]->IMSI; // "901111111111111";
        try
        {
            $request = new \GuzzleHttp\Psr7\Request( 'GET', 'sims?imsi=' . $terminalIMSI );
            $response = $client->send( $request );
            if ($response->getStatusCode() == env( 'STATUS_OK' ))
            {
                $formattedResponse = array();
                $response = json_decode( $response->getBody()->getContents() );
                foreach ($response as $rdata)
                {
                    if (! empty( $rdata ))
                    {
                        $simID = $rdata[0]->id;
                        $response = $client->send( new \GuzzleHttp\Psr7\Request( 'GET', 'sims/' . $simID ) );
                        if ($response->getStatusCode() == env( 'STATUS_OK' ))
                        {
                            $response = json_decode( $response->getBody()->getContents() );
                            foreach ($response as $data)
                            {
                                $company = $data->attributes->company_name;
                                $provisionID = "dsgg";
                                $data->relationships->provisionings->data['id'];
                                // "568";
                            }
                        }
                        else
                        {
                            return response( 'ERROR', $response->getStatusCode() );
                        }
                    }
                    else
                    {
                        return response( 'Sim ID not found', 404 );
                    }
                }

                $Query_Data = DB::table( 'terminal' )->select( 'terminal.Status as Stat' )->where( 'terminal.Idx', '=', $terminalID );
                $Query_Data = $Query_Data->get();
                if (count( $Query_Data ) > 0)
                {
                    $attributes = array(
                            'active' => ($Query_Data[0]->Stat == 'ACTIVE' ? true : false),
                            'demo' => true,
                            'company_name' => $company
                    );
                    $radminRequest['data'] = array(
                            'attributes' => $attributes
                    );
                    $json_data = array();
                    $json_data = json_encode( $radminRequest );
                    try
                    {
                        $request = new \GuzzleHttp\Psr7\Request( 'PUT', 'sims/' . $simID, $headers, $json_data );
                        $response = $client->send( $request );
                        if ($response->getStatusCode() == env( 'STATUS_OK' ))
                        {

                            $Query_Data = DB::table( 'terminal' );
                            $Query_Data->select( 'terminal.Idx as TerminalIdx', 'terminal_network_mapping.PDP_Allowed_YN_Flag as standardip', 'terminal.QoService_Idx as highstreeming','service.Number as fixedipv4' );
                            $Query_Data->leftJoin( 'terminal_network_mapping', 'terminal_network_mapping.Terminal_Idx', '=', 'terminal.Idx' );
                            $Query_Data->leftJoin( 'service', 'service.Terminal_Idx', '=', 'terminal.Idx' );
                            $Query_Data->whereRaw( '( service.Deactivation_Date IS NULL OR service.Deactivation_Date > now() )' );
                            $Query_Data->where( 'service.Service', '=', 'STATIC_IP' );
                            $Query_Data->where( 'terminal.Idx', '=', $terminalID )
                            ->whereRaw( '( terminal.Deactivation_Date IS NULL OR terminal.Deactivation_Date > now() )' )
                            ->first();


                            $Query_Data = $Query_Data->get();

                            if (count( $Query_Data ) > 0)
                            {
                                $attributes = array(
                                        'fixedipv4' => $Query_Data[0]->fixedipv4,
                                        'standardip' => $Query_Data[0]->standardip,
                                        'highest-streaming-option-enabled' => $Query_Data[0]->highstreeming,
                                        'streaminghdrhalfasymmetric' => $Query_Data[0]->highstreeming,
                                        'streaminghdrhalfsymmetric' => null,
                                        'streaminghdrfullasymmetric' => null,
                                        'streaminghdrfullsymmetric' => null
                                );
                                $radminRequest['data'] = array(
                                        'type' => 'provisionings',
                                        'attributes' => $attributes
                                );

                                $json_data = array();
                                $json_data = json_encode( $radminRequest );

                                try
                                {
                                    $response = $client->request( 'POST', 'sims/' . $simID . '/provisionings/' . $provisionID, $radminRequest );
                                    if ($response->getStatusCode() == env( 'STATUS_OK' ))
                                    {
                                        return response( "SUCCESS", env( 'STATUS_OK' ) );
                                    }
                                    else
                                    {
                                        return response( 'ERROR', env( 'STATUS_BAD_REQUEST' ) );
                                    }
                                }
                                catch (RequestException $re)
                                {
                                    return response( $re->getMessage(), env( 'STATUS_BAD_REQUEST' ) );
                                }
                            }
                        }
                        return response( 'ERROR', env( 'STATUS_BAD_REQUEST' ) );
                    }
                    catch (RequestException $re)
                    {
                        return response( $re->getMessage(), env( 'STATUS_BAD_REQUEST' ) );
                    }
                }
            }
            else
            {
                return response( 'ERROR', $response->getStatusCode() );
            }
        }
        catch (RequestException $re)
        {
            return response( $re->getMessage(), $response->getStatusCode() );
        }
    }
}
?>