<?php

namespace App\Http\Requests;
use App\Http\Models\Terminal;

class TerminalRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    protected function getValidatorInstance()
    {
        $validator = parent::getValidatorInstance();
        return $validator;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        switch( $this->method() )
        {
            case 'DELETE':
			{
				return [];
			}
            case 'POST':
            {
                return $this->createSBBRules();
            }
            case 'PUT':
            {
                return $this->udpateSBBRules();
            }
            case 'PATCH':
            case 'GET':
            default:break;
        }
    }

    private function createSBBRules()
    {
        return [
            'imsi' => 'required|digits:20|regex:/9011[0-9]{11}/',
            'icc_id' => 'digits:18|regex:/898[0-9]{15}/',
            'billingEntity' => 'numeric',
            'psa' => 'numeric'
        ];
    }

    private function udpateSBBRules()
    {
        $terminalId =  $this->route('terminalId');
        
        $terminal = Terminal::findOrFail($terminalId);
        $terminalCategory = ucfirst(strtolower($terminal->Category));
        
        if($terminal->Category=="SBB")
        {
            return [
                'leso' => 'required|numeric',
                'userGroupId' => 'required|numeric',
                'qos' => 'numeric',
                'goDirectFilter' => 'numeric',
                'simUserId' => 'required|numeric',
                'bssOrderNumber' => 'min:10|max:10|regex:/^BSS[0-9]{7}$/|unique:bss_order_number,bss_order_number,'.$terminalId.',terminal_idx',
                'services' => 'array',
                'services.data64' => 'digits:12|regex:/^87078[0-9]{7}$/|unique:service,number,'.$terminalId.',Terminal_Idx,Deactivation_Date,NULL',
                'services.data56' => 'digits:12|regex:/^87078[0-9]{7}$/|unique:service,number,'.$terminalId.',Terminal_Idx,Deactivation_Date,NULL',
                'services.voice' => 'digits:12|regex:/^87077[0-9]{7}$/|unique:service,number,'.$terminalId.',Terminal_Idx,Deactivation_Date,NULL',
                'services.fax' => 'digits:12|regex:/^87078[0-9]{7}$/|unique:service,number,'.$terminalId.',Terminal_Idx,Deactivation_Date,NULL',
                'services.staticIp' => 'ip',
                'services.gdnIp' => 'ip',
                'apn' => 'array',
                'networks' => 'array',
                'networks.*.ipAllocationType' => 'in:Static,Dynamic',
                'networks.*.pdpEnabled' => 'boolean',
                'activatedBy' => 'required',
                'goDirectAccess' => 'in:Activate,Deactivate',
                'imsi' => 'required|digits:15|regex:/9011[0-9]{11}/',
                'iccId' => 'digits:18|regex:/898[0-9]{15}/|unique:terminal,ICC_ID,'.$terminalId.',Idx,Deactivation_Date,NULL',
                'billingEntity' => 'numeric',
                'comments' => 'max:250',
                'psa' => 'numeric'
            ];
        }
        else 
        {
            return [];
        }
    }

    public function response(array $errors)
    {
        foreach ($errors as $message)
        {
            return response( array(
                    'message' => $message[0]
            ), 400 );
        }
    }
}
