<?php


namespace App\Exceptions;


class SMSNotFoundException extends \Exception
{
    protected $message = "This SMS not found";

    public function __construct($message = null, $code = 200)
    {
        if (!$message) {
            throw new $this('Unknown '. get_class($this));
        }
        parent::__construct($message, $code);
    }

}