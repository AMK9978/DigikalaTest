<?php


namespace App\Tests;


use App\Controller\SMSController;
use App\Entity\SMS;
use PHPUnit\Framework\TestCase;

class SMSControllerTest extends TestCase
{
    public function testGetURL()
    {
        $smsController = new SMSController();
        $sms = new SMS();
        $sms->setPhoneNumber(-1);
        $sms->setBody("");
        $result = $smsController->getURL(-1, $sms);
        $this->assertNotEquals("localhost", $result);
    }
}