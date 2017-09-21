<?php

return [
    'baseUri' => 'https://hw-be.airtimelink.com:8057/provisioning/',
    'requestDefaultOptions' => [
        'verify' => false,
        'headers' => [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ],
        'auth' => [ 
                    's1_user', 
                    's1_user#'
                ],
     // 'proxy' => '127.0.0.1:8888',  
    ]  
]; 
