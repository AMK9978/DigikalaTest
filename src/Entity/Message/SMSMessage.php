<?php


namespace App\Entity\Message;


class SMSMessage
{
    private $sms_id;
    private $ttl = 2;
    private $sms_host_api = -1;

    /**
     * @return mixed
     */
    public function getSmsHostApi()
    {
        return $this->sms_host_api;
    }

    /**
     * @param mixed $sms_host_api
     */
    public function setSmsHostApi($sms_host_api): void
    {
        $this->sms_host_api = $sms_host_api;
    }



    /**
     * @return int
     */
    public function getSmsId(): int
    {
        return $this->sms_id;
    }

    /**
     * @param int $sms_id
     */
    public function setSmsId(int $sms_id): void
    {
        $this->sms_id = $sms_id;
    }


    /**
     * @return int
     */
    public function getTtl(): int
    {
        return $this->ttl;
    }

    /**
     * @param int $ttl
     */
    public function setTtl(int $ttl): void
    {
        $this->ttl = $ttl;
    }


    public function __construct(int  $sms_id)
    {
        $this->sms_id = $sms_id;
    }
}