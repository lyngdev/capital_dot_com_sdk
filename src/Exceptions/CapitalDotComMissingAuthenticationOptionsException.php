<?php

namespace lyngdev\CapitalDotComSDK\Exceptions;

use Exception;
use Throwable;

class CapitalDotComMissingAuthenticationOptionsException extends Exception
{
    public function __construct($message = "Missing required authentication options. Add them by calling \$client->setAuth([...options here...])", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}