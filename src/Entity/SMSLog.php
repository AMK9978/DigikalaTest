<?php

namespace App\Entity;

use App\Repository\SMSLogRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=SMSLogRepository::class)
 */
class SMSLog
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $date;

    /**
     * @ORM\Column(type="integer")
     */
    private $sms_id;

    /**
     * @ORM\Column(type="integer")
     */
    private $used_api;

    /**
     * @ORM\Column(type="boolean")
     */
    private $hasSent;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getSmsId(): ?int
    {
        return $this->sms_id;
    }

    public function setSmsId(int $sms_id): self
    {
        $this->sms_id = $sms_id;

        return $this;
    }

    public function getUsedApi(): ?int
    {
        return $this->used_api;
    }

    public function setUsedApi(int $used_api): self
    {
        $this->used_api = $used_api;

        return $this;
    }

    public function getHasSent(): ?bool
    {
        return $this->hasSent;
    }

    public function setHasSent(bool $hasSent): self
    {
        $this->hasSent = $hasSent;

        return $this;
    }
}
