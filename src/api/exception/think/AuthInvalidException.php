<?php


namespace fulicommons\api\exception\think;


use fulicommons\api\exception\BaseException;

class AuthInvalidException extends BaseException
{
    public static $APPID_INVALID = 40001;
    public static $APPSECRET_INVALID = 40002;

    public function __construct($statusCode, $message = null, \Exception $previous = null, array $headers = [], $code = 0)
    {
        parent::__construct($statusCode, $message, $previous, $headers, $code);
    }

}
