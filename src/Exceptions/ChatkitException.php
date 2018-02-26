<?php

namespace Chatkit\Exceptions;

use Exception;

class ChatkitException extends Exception
{
    /**
     * The complete error response from Chatkit
     * Contains error, error_description and error_uri
     *
     * @var array
     */
    protected $body;

    public function __construct($message = "", $code = 0, $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return array
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param array $body
     * @return $this
     */
    public function setBody($body)
    {
        $this->body = $body;
        return $this;
    }
}
