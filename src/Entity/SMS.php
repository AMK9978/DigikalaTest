<?php

namespace App\Entity;

use App\Exceptions\SIMSBadDefinitionsException;
use App\Repository\SMSRepository;
use Doctrine\ORM\Mapping as ORM;
use phpDocumentor\Reflection\Types\Boolean;

/**
 * @ORM\Entity(repositoryClass=SMSRepository::class)
 */
class SMS implements \JsonSerializable
{
    public static $bodyLength = 60;
    public static $bodyLengthExceptionCode = 100;
    public static $invalidNumberExceptionCode = 101;
    private static $validNumberPatten = "/^(98)?[1-9]{1}[0-9]{1}[0-9]{4}[0-9]{4}$/";

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $number;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $body;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    /**
     * @param string $number
     * @return $this
     * @throws SIMSBadDefinitionsException
     */
    public function setPhoneNumber(string $number): self
    {
        $number = trim($number);
        $number = str_replace(' ', '', $number);
        $this->validatePhoneNumber($number);
        $this->number = $number;
        return $this;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    /**
     * @param string $body
     * @return $this
     * @throws SIMSBadDefinitionsException
     */
    public function setBody(string $body): self
    {
        $this->validateBody($body);
        $this->body = $body;
        return $this;
    }

    /**
     * @param string $body
     * @throws SIMSBadDefinitionsException
     */
    public function validateBody(string $body)
    {
        if (strlen($body) > self::$bodyLength){
            throw new SIMSBadDefinitionsException("Body of SMS cannot be more > " . self::$bodyLength,
                self::$bodyLengthExceptionCode);
        }
    }

    /**
     * @param string $number
     * @throws SIMSBadDefinitionsException
     */
    public function validatePhoneNumber(string $number)
    {
        if (!preg_match(self::$validNumberPatten, $number)) {
            throw new SIMSBadDefinitionsException("Phone number is not valid", self::$invalidNumberExceptionCode);
        }
    }

    /**
     * SMS constructor.
     * @param string $body
     * @param string $phoneNumber
     * @throws SIMSBadDefinitionsException
     */
    public function __construct(string $body, string $phoneNumber)
    {
        $this->setBody($body);
        $this->setPhoneNumber($phoneNumber);
    }

    public function __toString()
    {
        return "id:" . $this->getId() . ", number:" .
            $this->getNumber() . ", body:" .
            $this->getBody();
    }

    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return array(
            'id' => $this->getId(),
            'number' => $this->getNumber(),
            'body' => $this->getBody()
        );
    }
}
