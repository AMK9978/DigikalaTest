<?php

namespace App\Controller;

use App\Entity\SMS;
use App\Entity\SMSLog;
use App\Repository\SMSLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ReportController extends AbstractController
{
    /**
     * @Route("/", name="report")
     * @param SMSLogRepository $smsLogRepository
     * @return Response
     */
    public function index(SMSLogRepository $smsLogRepository)
    {
        $all_sms = $smsLogRepository->getAllSMS();
        $sms_logs1 = $smsLogRepository->getAPIUsage(1);
        $sms_logs2 = $smsLogRepository->getAPIUsage(2);
        $api_faults1 = $smsLogRepository->getAPIFaultPercentage(1);
        $api_faults2 = $smsLogRepository->getAPIFaultPercentage(2);
        $top_10 = $smsLogRepository->getTop10();

        return $this->render('report/index.html.twig', [
            'controller_name' => 'ReportController',
            'all_sms' => $all_sms,
            'sms_logs1' => $sms_logs1,
            'sms_logs2' => $sms_logs2,
            'api_faults1' => $api_faults1,
            'api_faults2' => $api_faults2,
            'top_10' => $top_10,
        ]);
    }


    /**
     * @Route("/search", name="search")
     * @param SMSLogRepository $smsLogRepository
     * @param Request $request
     * @return Response
     */
    public function search(SMSLogRepository $smsLogRepository, Request $request)
    {
        $number = $request->request->get("number");
        $res = $smsLogRepository->searchLogs($number);
        return $this->render('report/search.html.twig', [
            'all_sms' => $res,
            'number' => $number
        ]);
    }
}
