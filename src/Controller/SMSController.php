<?php

namespace App\Controller;

use App\Entity\SMS;
use App\Entity\SMSLog;
use Carbon\Carbon;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use GuzzleHttp\Client;


class SMSController extends AbstractController
{

    private $pubSubClient = null;
    /**
     * @Route("/create_sms", name="createSMS")
     * @param Request $request
     * @return JsonResponse
     */
    public function createSMS(Request $request): JsonResponse
    {
        $number = $request->request->get("number");
        $body = $request->request->get("body");
        $sms = new SMS();
        $sms->setBody($body);
        $sms->setPhoneNumber($number);
        $this->getDoctrine()->getManager()->persist($sms);
        $this->getDoctrine()->getManager()->flush();

        return new JsonResponse(["sms" => json_encode($sms)], 200);
    }


    /**
     * @Route("/sendSMS", name="sendSMS")
     * @param Request $request
     * @return JsonResponse
     */
    public function sendSMS(Request $request)
    {
        $sms = $this->getDoctrine()->getManager()
            ->find(SMS::class, $request->get('sms_id'));
        try {
            return $this->messageBroker($sms);
        } catch (\Exception $e) {
            return new JsonResponse(["msg" => "your request's queued"], 503);
        }
    }

    public function messageBroker(SMS $sms)
    {
        RedisController::getInstance()
            ->rpush("", (array)$sms);
        $pubSubClient = RedisController::getInstance()
            ->pubSubLoop()->getClient();
        $pubSubClient->rpush("", (array)$sms);
        $pubSubClient->publish("ch1", $sms);
        $number = random_int(1, 2);
        if ($number == 1) {

            try {
                $this->sendAPI1($sms);
                return new JsonResponse(["msg" => "your request's sent to api1"], 200);
            } catch (\Exception $exception) {

            }
        } else {
            $this->sendAPI2($sms);
            return new JsonResponse(["msg" => "your request's sent to api2"], 200);
        }
    }

    public function smsPop()
    {
        $client = RedisController::getInstance()->pubSubLoop()
            ->getClient();
        $client->subscribe("ch1");

        var_dump(RedisController::getInstance()
            ->rpop(""));
    }

    public function sendAPI1(SMS $sms)
    {
        $this->sendAPI(1, $sms);
    }

    public function sendAPI2(SMS $sms)
    {
        $this->sendAPI(2, $sms);
    }

    public function log(int $api_number, int $sms_id, bool $hasSent)
    {
        $log = new SMSLog();
        $log->setSmsId($sms_id);
        $log->setHasSent($hasSent);
        $log->setUsedApi($api_number);
        $log->setDate(Carbon::now());
        $this->getDoctrine()->getManager()->persist($log);
        $this->getDoctrine()->getManager()->flush();
    }


    public function sendAPI(int $api_number, SMS $sms)
    {
        $client = new Client([
            // Base URI is used with relative requests
            'base_uri' => 'http://localhost',
            'timeout' => 2.0,
        ]);
        if ($api_number == 1) {
            $url = 'localhost:81/sms/send/?number=' .
                $sms->getNumber() . '&body=' . $sms->getBody();
            try {
                $response = $client->get($url);
                echo $response->getStatusCode();
                $this->log($api_number, $sms->getId(), 1);
                return $response;
            } catch (GuzzleException $e) {
                $this->log($api_number, $sms->getId(), 0);
                $this->pushTaskQueue($sms);
                throw $e;
            }
        } else {
            $url = 'localhost:82/sms/send/?number=' .
                $sms->getNumber() . '&body=' . $sms->getBody();
            try {
                $response = $client->get($url);
                echo $response->getStatusCode();
                $this->log($api_number, $sms->getId(), 1);
                return $response;
            } catch (GuzzleException $e) {
                $this->log($api_number, $sms->getId(), 0);
                $this->pushTaskQueue($sms);
                throw $e;
            }
        }
    }

    public function pushTaskQueue(SMS $sms)
    {
        $request = new Request(['sms_id' => $sms->getId()]);
        return;
//        $this->sendSMS($request);
    }


}
