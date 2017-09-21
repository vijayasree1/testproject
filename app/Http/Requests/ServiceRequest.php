<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class ServiceRequest extends Request
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

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        switch($this->method())
        {
            case 'POST': return $this->addServiceRules();
            
            case 'PUT' : return $this->updateServiceRules();
                
            case 'GET' : return $this->getServicesRules();
        }
    }
    
    public function addServiceRules()
    {
        return [
          'serviceId' => 'required|array',
          'serviceId.*' => 'required|integer|min:1',
          'startDate' => 'required|array',
          'startDate.*' => 'required|date_format:Y-m-d',
          'endDate' => 'required|array',
          'endDate.*' => 'date_format:Y-m-d',
          'comments' => 'array',
          'comments.*' => '',
          'createdBy' => 'required'
        ];
    }
    
    public function updateServiceRules()
    {
        return [
          'startDate' => 'required|date_format:Y-m-d',
          'endDate' => 'date_format:Y-m-d',
          'comments' => '',
          'updatedBy' => 'required'
        ];
    }
    
    public function getServicesRules()
    {
        return [
          'startDate' => 'date_format:Y-m-d',
          'endDate' => 'date_format:Y-m-d',
          'comments' => ''
        ];
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
