<?php

namespace lyngdev\CapitalDotComSDK;

use lyngdev\CapitalDotComSDK\Exceptions\CapitalDotComMissingAuthenticationOptionsException;

class CapitalClient
{
    private const API_BASE_URL = 'https://api-capital.backend-capital.com/api/v1/';
    private array $auth_options = [
        'api_key' => '',
        'clear_password' => '',
        'identifier' => '',
    ];
    private array $session_data;

    public function __construct(string|array $identifierOrOptions = '', string $password = '', string $api_key = '')
    {
        if($identifierOrOptions && is_array($identifierOrOptions)){
            $this->setAuthOptions($identifierOrOptions);
        }
        $this->invalidateCurrentSession();
    }

    /**
     * @param array $options
     */
    public function setAuthOptions(array $options){
        foreach(array_keys($this->auth_options) as $k){
            $this->auth_options[$k] = $options[$k];
        }
    }

    /**
     * @return bool
     */
    public function hasRequiredAuthOptions():bool{
        foreach($this->auth_options as $k => $v){
            if($v === '' || $v === null){
                return false;
            }
        }
        return true;
    }

    /**
     * @throws CapitalDotComMissingAuthenticationOptionsException
     */
    public function auth(){
        $this->fetchSession();
    }

    public function getPositions(): object|bool|array|null
    {
        return $this->curlGet(self::API_BASE_URL . 'positions',$this->getStandardSessionHeaders())->getJsonDecodedBody();
    }

    public function getOrders(){
        return $this->curlGet(self::API_BASE_URL . 'workingorders',$this->getStandardSessionHeaders())->getJsonDecodedBody();
    }

    public function getTopLevelMarketCategories(){
        return $this->curlGet(self::API_BASE_URL . 'marketnavigation',$this->getStandardSessionHeaders())->getJsonDecodedBody();
    }

    /**
     * Ping the service to keep a trading session alive
     *
     * @return mixed
     */
    public function pingSession(){
        return $this->curlGet(self::API_BASE_URL . 'ping',$this->getStandardSessionHeaders())->getJsonDecodedBody();
    }

    /**
     * Test connectivity to the API and get the current server time
     * Note: No auth required for this endpoint
     */
    public function getServerTime(){
        return $this->curlGet(self::API_BASE_URL . 'time')->getJsonDecodedBody();
    }

    private function getStandardSessionHeaders():array{
        return [
            'X-SECURITY-TOKEN: ' . $this->getSessionSecurityToken(),
            'CST: ' . $this->getSessionCSTToken()
        ];
    }

    /**
     * @return string
     */
    private function getSessionSecurityToken():string{
        return $this->session_data['headers']['x-security-token'] ?? '';
    }

    /**
     * @return string
     */
    private function getSessionCSTToken():string{
        return $this->session_data['headers']['cst'] ?? '';
    }

    private function fetchSession(){
        if(!$this->hasRequiredAuthOptions()){
            throw new CapitalDotComMissingAuthenticationOptionsException();
        }

        $parameters = [
            'identifier' => $this->auth_options['identifier'],
            'password' => $this->auth_options['clear_password'],
            ''
        ];
        $headers = [
            'X-CAP-API-KEY: ' . $this->auth_options['api_key'],
            'Content-Type: application/json'
        ];

        $sessionResponse = $this->curlPost(self::API_BASE_URL . 'session', $parameters, $headers);

        $decoded = $sessionResponse->getJsonDecodedBody();

        if($decoded && ($decoded['clientId'] ?? false)){
            $this->session_data['body'] = $decoded;
            $this->session_data['headers'] = $sessionResponse->getHeaderArray();
            if($this->getSessionCSTToken() && $this->getSessionSecurityToken()){
                $this->authenticated = true;
            }
        }else{
            $this->authenticated = false;
        }
    }

    /**
     * Invalidates the current CST and Security Token
     */
    private function invalidateCurrentSession(){
        $this->session_data = [
            'CST' => '',
            'security_token' => '',
        ];
    }

    private function curlPost(string $url, array $parameters = [], array $headers = []){
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($parameters));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, true);
        //curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);

        $return = [
            'body' => '',
            'error' => null,
            'http_status' => -1,
            'header' => ''
        ];

        // error
        if (!$response) {
            $return['error'] = curl_error($ch);
        } else {
            $return['http_status'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $return['header'] = substr($response, 0, $header_size);
            $return['body'] = substr($response, $header_size);;
        }

        curl_close($ch);

        return new CurlResponse($return['http_status'], $return['body'], $return['header'], $return['error']);
    }

    private function curlGet(string $url, array $headers = []):CurlResponse{
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, true);
        //curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);

        $return = [
            'body' => null,
            'error' => null,
            'http_status' => -1,
            'header' => []
        ];

        // error
        if (!$response) {
            $return['error'] = curl_error($ch);
        } else {
            $return['http_status'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $return['header'] = substr($response, 0, $header_size);
            $return['body'] = substr($response, $header_size);;
        }

        curl_close($ch);

        return new CurlResponse($return['http_status'], $return['body'], $return['header'], $return['error']);
    }
}