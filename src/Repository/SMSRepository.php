<?php

namespace App\Repository;

use App\Entity\Message\SMSMessage;
use App\Entity\SMS;
use App\Entity\SMSLog;
use App\Exceptions\SMSNotFoundException;
use Carbon\Carbon;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @method SMS|null find($id, $lockMode = null, $lockVersion = null)
 * @method SMS|null findOneBy(array $criteria, array $orderBy = null)
 * @method SMS[]    findAll()
 * @method SMS[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SMSRepository extends ServiceEntityRepository
{
    private $logger;

    public function __construct(LoggerInterface $logger, ManagerRegistry $registry)
    {
        $this->logger = $logger;
        parent::__construct($registry, SMS::class);
    }


    /**
     * @param int $api_number
     * @param int $sms_id
     * @param bool $hasSent
     */
    public function log(int $api_number, int $sms_id, bool $hasSent)
    {
        $log = new SMSLog();
        $log->setSmsId($sms_id);
        $log->setHasSent($hasSent);
        $log->setUsedApi($api_number);
        $log->setDate(Carbon::now());
        try {
            $this->getEntityManager()->persist($log);
        } catch (ORMException $e) {
            $this->logger->error("Exception in log in SMSRepo:" . $e->getMessage());
        }
        try {
            $this->getEntityManager()->flush($log);
        } catch (OptimisticLockException | ORMException $e) {
            $this->logger->error("Exception in log in SMSRepo:" . $e->getMessage());
        }
    }


    /**
     * @param int $api_number
     * @param SMSMessage $smsMessage
     * @return JsonResponse|ResponseInterface
     * @throws SMSNotFoundException
     */
    public function sendAPI(int $api_number, SMSMessage $smsMessage)
    {
        $sms = $this->find($smsMessage->getSmsId());
        if ($sms == null) {
            throw new SMSNotFoundException("Related SMS not found", 201);
        }
        $url = $this->getURL($api_number, $sms);
        try {
            $client = HttpClient::create();
            $response = $client->request('GET', $url);
            $this->log($api_number, $sms->getId(), 1);
            return $response;
        } catch (TransportExceptionInterface | Exception $e) {
            $this->log($api_number, $sms->getId(), 0);
            if ($smsMessage->getTtl() == 0) {
                return new JsonResponse(['msg' => 'Task queued'], 503);
            } else {
                $smsMessage->setTtl($smsMessage->getTtl() - 1);
                return $this->sendAPI($api_number == 1 ? 2 : 1, $smsMessage);
            }
        }
    }

    public function getURL(int $api_number, SMS $sms): string
    {
        return 'localhost:8' . $api_number . '/sms/send/?number=' .
            $sms->getNumber() . '&body=' . $sms->getBody();
    }

    // /**
    //  * @return SMS[] Returns an array of SMS objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?SMS
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
