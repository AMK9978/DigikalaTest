<?php


namespace App\Repository;


use App\Controller\RedisController;
use App\Entity\Message\SMSMessage;
use Predis\Client;
use Superbalist\PubSub\Redis\RedisPubSubAdapter;

class PubSubAdapter
{
    private static $client;
    private static $adapter;
    private static $pubSubAdapter;

    private function __construct()
    {
    }

    public static function getInstance(): PubSubAdapter
    {
        PubSubAdapter::$client = RedisController::getInstance();
        PubSubAdapter::$adapter = new RedisPubSubAdapter(PubSubAdapter::$client);
        return new PubSubAdapter();
    }

    public function subChannel(string $channel)
    {
        PubSubAdapter::$adapter->subscribe($channel, function (SMSMessage $message) {
            var_dump($message);
        });
    }

    public function pubMessage(string $channel, SMSMessage $smsMessage)
    {
        PubSubAdapter::$adapter->publish($channel, $smsMessage);
    }
}