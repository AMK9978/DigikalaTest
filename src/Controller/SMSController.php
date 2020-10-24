<?php

namespace App\Controller;

use App\Entity\Message\SMSMessage;
use App\Entity\SMS;
use App\Exceptions\SIMSBadDefinitionsException;
use App\Exceptions\SMSNotFoundException;
use App\Repository\SMSRepository;
use Symfony\Component\HttpClient\HttpClient;
use App\Entity\SMSLog;
use Carbon\Carbon;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;


class SMSController extends AbstractController
{

    /**
     * @Route("/create_sms", name="createSMS")
     * @param SMSRepository $repository
     * @param Request $request
     * @return JsonResponse
     */
    public function createSMS(SMSRepository $repository, Request $request): JsonResponse
    {
        $number = $request->request->get("number");
        $body = $request->request->get("body");
        try {
            $sms = new SMS($body, $number);
        } catch (SIMSBadDefinitionsException $exception) {
            return new JsonResponse(["msg" => "Your request is invalid"], 400);
        }
        $this->getDoctrine()->getManager()->persist($sms);
        $this->getDoctrine()->getManager()->flush();
        $request->request->set("sms_id", $sms->getId());
        $response = $this->sendSMS($repository, $request);
        $response->setData(['sms' => $sms]);
        return $response;
    }


    /**
     * @Route("/sendSMS", name="sendSMS")
     * @param SMSRepository $repository
     * @param Request $request
     * @return JsonResponse
     */
    public function sendSMS(SMSRepository $repository, Request $request)
    {
        $smsMessage = new SMSMessage($request->request->get('sms_id'));
        try {
            $number = random_int(1, 2);
        } catch (Exception $e) {
            return new JsonResponse(['msg' =>
                'Random systems are not available'], 503);
        }
        $smsMessage->setSmsHostApi($number);
        try {
            $repository->sendAPI($number, $smsMessage);
        } catch (SMSNotFoundException $e) {
            return new JsonResponse(["msg" => $e->getMessage()], 404);
        }
        return new JsonResponse(["msg" => "your request's queued"], 200);
    }

}
