<?php


namespace App\Tests;


use App\Controller\SMSController;
use App\Entity\SMS;
use App\Exceptions\SMSNotFoundException;
use App\Repository\SMSRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class SMSControllerTest extends TestCase
{
    public function testSendSMS()
    {
        $smsController = new SMSController();

        $smsRepo = $this->getMockBuilder(SMSRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $smsRepo->expects($this->once())
            ->method('sendAPI')
            ->will($this->throwException(new SMSNotFoundException("Not found")));

        $request = new Request();
        $request->request->set("sms_id", -1);
        $result = $smsController->sendSMS($smsRepo, $request);
        $this->assertEquals(404, $result->getStatusCode());
    }
}