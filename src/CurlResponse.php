<?php

namespace lyngdev\CapitalDotComSDK;

class CurlResponse
{
    private int $http_status_code = -1;
    private string $body = '';
    private string $headers = '';
    private $error = null;

    public function __construct(int $http_status_code = -1, string $body = '', string $headers = '', $error = null)
    {
        $this->http_status_code = $http_status_code;
        $this->body = $body;
        $this->headers = $headers;
        $this->error = $error;
    }

    public function getJsonDecodedBody(bool $associative = true):mixed{
        if($decoded = json_decode($this->body, $associative)){
            return $decoded;
        }
        if($associative){
            return [];
        }
        return null;
    }

    public function getRawBody():string{
        return $this->body;
    }

    public function getHeaderArray():array{
        return $this->parseHeaderString($this->getRawHeaders());
    }

    private function parseHeaderString(string $headerString):array{
        $return = [];
        foreach(explode("\n", $headerString) as $row_no => $row){
            $rowParts = explode(':', $row);
            if(count($rowParts) > 1 && strlen($rowParts[0]) > 1){
                $key = $rowParts[0];
                $value = substr($row,strlen($key)+1);
                $return[$key] = trim($value);
            }
        }
        return $return;
    }

    public function getRawHeaders():string{
        return $this->headers;
    }
}