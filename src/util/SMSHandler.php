<?php


namespace App\util;


use App\Controller\RedisController;
use App\Entity\Message\SMSMessage;
use App\Entity\SMS;
use App\Repository\SMSRepository;

class SMSHandler
{
    private $smsRepo;

    public function __construct(SMSRepository $smsRepository)
    {
        $this->smsRepo = $smsRepository;
    }

    public static function dispatch(SMSMessage $smsMessage)
    {
        $number = random_int(1, 2);
        if ($smsMessage->getSmsHostApi() == -1) {
            $smsMessage->setSmsHostApi($number);
            RedisController::getInstance()->pubSubLoop()
                ->getClient()->publish("ch" . $number, $smsMessage);
        } else if ($smsMessage->getSmsHostApi() == 1) {
            RedisController::getInstance()->pubSubLoop()
                ->getClient()->publish("ch2", $smsMessage);
        } else if ($smsMessage->getSmsHostApi() == 2) {
            RedisController::getInstance()->pubSubLoop()
                ->getClient()->publish("ch1", $smsMessage);
        }
    }


}