<?php

namespace Services;

/**
* Library to provide an interface sms notification
*/
class SmsNotification
{
    const API_URL 			    = '';
    const API_USERNAME 			= '';
    const API_ID 			    = '';
    const API_TXTID 			= '';
    const API_PASSWORD 	        = '';

    private $api_status 		= false;
    private $api_call_status 	= array();
    private $token 				= '';

    private $successful_response_codes 	= array(200);

    public function __construct()
    {

    }

    /**
    * Login and get token to SMS API
    * @return array
    */
    public function login()
    {
        $parameters = array(
            'username' => self::API_USERNAME,
            'password' => self::API_PASSWORD,
            'app_id' => self::API_ID
        );

        $data = $this->apiCall('login', 'POST', $parameters);

        $data = json_decode($data, true);

        if(!$data && !isset($data['response']) && !isset($data['response']['token']) && !$data['response']['token']){
            return false;
        }

        $this->token = $data['response']['token'];

        return true;
    }

    /**
    * Send SMS
    * @param  string $number
    * @param  string $message
    * @return array
    */
    public function sendMessage($number, $message)
    {
        if (!$number) {
            return false;
        }

        if (!$message) {
            return false;
        }

        if (is_array($number)) {
            $number = implode(',', $number);
        }

        //login if token not available
        if(!$this->token){
            $this->login();
        }

        $parameters = array(
            'token' => $this->token,
            'recipients' => $number,
            'message' => $message,
            'txtid' => self::API_TXTID
        );

        $data = $this->apiCall('sms', 'POST', $parameters);

        $data = json_decode($data, true);

        if(!$data && !isset($data['response']) && !isset($data['response']['code']) && !$data['response']['code']==200){
            return false;
        }

        return $data;
    }

    /**
    * Retrieve Outbox
    * @return array
    */
    public function getOutbox()
    {

        //login if token not available
        if(!$this->token){
            $this->login();
        }

        $parameters = array(
            'token' => $this->token,
            'txtid' => self::API_TXTID
        );

        $data = $this->apiCall('outbox', 'GET', $parameters);

        $data = json_decode($data, true);

        if(!$data && !isset($data['response']) && !isset($data['response']['code']) && !$data['response']['code']==200){
            return false;
        }

        return $data;
    }

    /**
    * Retrieve Inbox
    * @return array
    */
    public function getInbox()
    {

        //login if token not available
        if(!$this->token){
            $this->login();
        }

        $parameters = array(
            'token' => $this->token,
            'txtid' => self::API_TXTID
        );

        $data = $this->apiCall('inbox', 'GET', $parameters);

        $data = json_decode($data, true);

        if(!$data && !isset($data['response']) && !isset($data['response']['code']) && !$data['response']['code']==200){
            return false;
        }

        return $data;
    }


    /**
    * getter method for api_status
    */
    public function getApiStatus()
    {
        return $this->api_status;
    }


    // *************************** Private Functions ***************************
    // *************************************************************************

    /**
    * Controls the flow of calling various api interface
    */
    private function apiCall($method = 'login', $request = 'GET', $data = array())
    {
        $curl = '';

        // construct api url
        $api_url = $this->constructUrl($method, $data);

        $curl = curl_init($api_url);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        // set the following if GET
        if ($request=='GET') {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
        }

        // set the following if POST
        if ($request=='POST') {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        }

        $response 				= curl_exec($curl);
        $this->api_call_status 	= curl_getinfo($curl);

        curl_close($curl);


        if ($this->callFailed($this->api_call_status['http_code'])===true) {
            return array('call_error'=>$this->getResponseCodeDescription($this->api_call_status['http_code']));
        }

        return $response;
    }



    /**
    * Construct URL base on requested $method to proper api method
    */
    private function constructUrl($method = 'login', $data = array())
    {
        $final_url = '';

        switch ($method) {
            case 'login':
                $final_url = self::API_URL . '/login';
                break;

            case 'sms':
                $final_url = self::API_URL . '/sms';
                break;

            case 'inbox':
                $final_url = self::API_URL . '/inbox?token='.$data['token'].'&txtid='.$data['txtid'];
                break;

            case 'outbox':
                $final_url = self::API_URL . '/outbox?token='.$data['token'].'&txtid='.$data['txtid'];
                break;

            case 'settings':
                $final_url = self::API_URL . '/settings';
                break;

            case 'summary':
                $final_url = self::API_URL . '/summary';
                break;

            default:
                $final_url = self::API_URL . '/login';
        }

        return $final_url;
    }



    /**
    * If API returned response_code not defined in successful_response_codes,
    * return response code interpretation
    */
    private function callFailed($response_code = '')
    {
        if (! in_array($response_code, $this->successful_response_codes)) {
            return true;
        }
    }




    // **************************** Helper Functions ****************************
    // **************************************************************************

    /**
    * Header code reference
    * @param  [type] $response_code [description]
    */
    private function getResponseCodeDescription($response_code)
    {
        $codes = array(
            100 => 'Continue',
            101 => 'Switching Protocols',
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            306 => '(Unused)',
            307 => 'Temporary Redirect',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested Range Not Satisfiable',
            417 => 'Expectation Failed',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported'
            );

        return (isset($codes[$response_code])) ? $codes[$response_code] : '';
    }
}
