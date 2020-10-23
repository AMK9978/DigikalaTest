<?php

namespace App\Controller;

use App\Entity\Message\SMSMessage;
use App\Entity\SMS;
use Symfony\Component\HttpClient\HttpClient;
use App\Entity\SMSLog;
use Carbon\Carbon;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;


class SMSController extends AbstractController
{

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
     * @throws Exception
     */
    public function sendSMS(Request $request)
    {
        $smsMessage = new SMSMessage($request->request->get('sms_id'));
        $number = random_int(1, 2);
        $smsMessage->setSmsHostApi($number);
        try {
            $this->sendAPI($number, $smsMessage);
        } catch (Exception $e) {
        }
        return new JsonResponse(["msg" => "your request's queued"], 200);
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


    public function sendAPI(int $api_number, SMSMessage $smsMessage)
    {
        $sms = $this->getDoctrine()->getManager()
            ->find(SMS::class, $smsMessage->getSmsId());
        $url = 'localhost:8' . $api_number . '/sms/send/?number=' .
            $sms->getNumber() . '&body=' . $sms->getBody();
        try {
            $client = HttpClient::create();
            $response = $client->request('GET', $url);
            $this->log($api_number, $sms->getId(), 1);
            return $response;
        } catch (Exception $e) {
            $this->log($api_number, $sms->getId(), 0);
            if ($smsMessage->getTtl() == 0) {
                $this->pushTaskQueue($sms);
                throw $e;
            } else {
                $smsMessage->setTtl($smsMessage->getTtl() - 1);
                return $this->sendAPI($api_number == 1 ? 2 : 1,
                    $smsMessage);
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
