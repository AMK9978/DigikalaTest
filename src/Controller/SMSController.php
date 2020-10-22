<?php

namespace App\Controller;

use App\Entity\SMS;
use App\Entity\SMSLog;
use Carbon\Carbon;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use GuzzleHttp\Client;


class SMSController extends AbstractController
{

    /**
     * @Route("/create_sms", name="createSMS")
     * @param string $number
     * @param string $body
     * @return SMS
     */
    public function createSMS(string $number, string $body): SMS
    {
        $sms = new SMS();
        $sms->setBody($body);
        $sms->setPhoneNumber($number);
        $this->getDoctrine()->getManager()->persist($sms);
        $this->getDoctrine()->getManager()->flush();
        return $sms;
    }


    /**
     * @Route("/sendSMS", name="sendSMS")
     * @param SMS $sms
     */
    public function sendSMS(SMS $sms)
    {
        try {
            $number = random_int(1, 2);
            if ($number == 1) {
                $this->sendAPI1($sms);
            } else {
                $this->sendAPI2($sms);
            }
        } catch (\Exception $e) {
        }

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
        $this->sendSMS($sms);
    }


}
