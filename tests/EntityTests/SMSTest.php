<?php


namespace App\Tests\EntityTests;


use App\Entity\SMS;
use App\Exceptions\SIMSBadDefinitionsException;
use PHPUnit\Framework\TestCase;

class SMSTest extends TestCase
{
    public function testSMSDefinition__body1()
    {
        try {
            $sms = new SMS("SSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSS
        SSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSSS", "");
        } catch (SIMSBadDefinitionsException $exception) {
            $this->assertEquals($exception->getCode(), SMS::$bodyLengthExceptionCode);
        }
    }

    public function testSMSDefinition__phoneNumber1()
    {
        try {
            $sms = new SMS("", "");
        } catch (SIMSBadDefinitionsException $exception) {
            $this->assertEquals($exception->getCode(), SMS::$invalidNumberExceptionCode);
        }
    }

    public function testSMSDefinition__phoneNumber2()
    {
        $sms = new SMS("", "989024123432");
        $this->assertEquals($sms, $sms);
    }

    public function testSMSDefinition__phoneNumber3()
    {
        $sms = new SMS("", "98 90 2413 4329");
        $this->assertEquals($sms, $sms);
    }
}