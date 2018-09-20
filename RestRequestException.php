<?php
/**
 * User: mzieba <admin@hadriel.net>
 * Date: 20.09.18
 * Time: 10:12
 */

namespace simialbi\yii2\rest;

use Throwable;

class RestRequestException extends \Exception
{
    private $url;
    private $statusCode;
    private $method;

    public function __construct($url, $method, $statusCode, $message = "", $code = 0, $previous = null)
    {
        $this->url = $url;
        $this->statusCode = $statusCode;
        $this->method = $method;

        parent::__construct($this->createMessage($message), $code, $previous);
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function getMethod()
    {
        return $this->method;
    }

    private function createMessage($message)
    {
        return $this->method.' '.$this->url.': '.$message;
    }
}
