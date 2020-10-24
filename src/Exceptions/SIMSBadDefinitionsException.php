<?php


namespace App\Exceptions;


class SIMSBadDefinitionsException extends \Exception
{
    protected $message = "You've defined body object wrong:";

    public function __construct($message = null, $code = 0)
    {
        if (!$message) {
            throw new $this('Unknown '. get_class($this));
        }
        parent::__construct($message, $code);
    }

}