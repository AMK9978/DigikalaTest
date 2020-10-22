<?php


namespace App\Controller;

use Predis\Autoloader;
use Predis\Client;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class RedisController extends AbstractController
{
    /** @var  Client */
    private static $redisClient;

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    private function __wakeup()
    {
    }


    public static function getInstance(): Client
    {
        if (RedisController::$redisClient == null) {
            Autoloader::register();
            RedisController::$redisClient = new Client([
                "password" => "DUMMY_PASS"
            ]);
        }
        return RedisController::$redisClient;
    }


    public function createKeyAction($key, $value)
    {
        RedisController::$redisClient->set($key, $value);
        return $this->json([
            "status" => Response::HTTP_OK
        ]);
    }

    public function deleteKeyAction($key)
    {
        RedisController::$redisClient->del($key);
        return $this->json([
            "status" => Response::HTTP_OK
        ]);
    }
}