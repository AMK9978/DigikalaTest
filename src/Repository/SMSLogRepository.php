<?php

namespace App\Repository;

use App\Controller\RedisController;
use App\Entity\SMSLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;
use MessagePack\Packer;
use Psr\Log\LoggerInterface;

/**
 * @method SMSLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method SMSLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method SMSLog[]    findAll()
 * @method SMSLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SMSLogRepository extends ServiceEntityRepository
{
    private $logger;

    public function __construct(LoggerInterface $logger, ManagerRegistry $registry)
    {
        $this->logger = $logger;
        parent::__construct($registry, SMSLog::class);
    }

    public function getAllSMS()
    {
        if (RedisController::getInstance()->exists("all_sms")) {
            return unserialize(RedisController::getInstance()->get("all_sms"));
        } else {
            $entityManager = $this->getEntityManager();
            try {
                $all_sms = $entityManager->createQueryBuilder()
                    ->select('count(p)')
                    ->from('App\Entity\SMSLog', 'p')
                    ->where('p.hasSent = 1')
                    ->getQuery()->getSingleScalarResult();
            } catch (NoResultException | NonUniqueResultException $e) {
                $this->logger->error("Exception at line 43 getAllSMS raised:" . $e->getMessage());
                $all_sms = 0;
            }
            RedisController::getInstance()->set("all_sms", serialize($all_sms));
            $this->expireKey("all_sms", 5 * 60);
            return $all_sms;
        }
    }

    public function getAPIUsage(int $api_number)
    {
        if (RedisController::getInstance()
            ->exists("all_usages")) {
            $all_usages = RedisController::getInstance()->get("all_usages");
        }
        if (((RedisController::getInstance()
                    ->exists("api1_usage") && $api_number == 1)
            || (RedisController::getInstance()
                    ->exists("api2_usage") && $api_number == 2))) {
            if ($api_number == 1) {
                $api_usage = RedisController::getInstance()->get("api1_usage");
            } else {
                $api_usage = RedisController::getInstance()->get("api2_usage");
            }
        } else {
            $entityManager = $this->getEntityManager();
            try {
                $api_usage = $entityManager->createQueryBuilder()
                    ->select('count(p)')
                    ->from('App\Entity\SMSLog', 'p')
                    ->where('p.used_api = :api')
                    ->setParameter('api', $api_number)
                    ->getQuery()->getSingleScalarResult();
            } catch (NoResultException | NonUniqueResultException $e) {
                $this->logger->error("Exception at line 77 getAPIUsage raised: " . $e->getMessage());
                $api_usage = 0;
            }
            try {
                $all_usages = $entityManager->createQueryBuilder()
                    ->select('count(p)')
                    ->from('App\Entity\SMSLog', 'p')
                    ->getQuery()->getSingleScalarResult();
            } catch (NoResultException | NonUniqueResultException $e) {
                $this->logger->error("Exception at line 86 getAPIUsage raised: " . $e->getMessage());
                $all_usages = 1;
            }

            RedisController::getInstance()
                ->set("all_usages", $all_usages);
            RedisController::getInstance()->expire("all_usages", 5 * 60);
            RedisController::getInstance()->persist("all_usages");
            if ($api_number == 1) {
                RedisController::getInstance()
                    ->set("api1_usage", $api_usage);
                $this->expireKey("api1_usage", 5 * 60);
            } else {
                RedisController::getInstance()
                    ->set("api2_usage", $api_usage);
                $this->expireKey("api2_usage", 5 * 60);
            }
        }
        if ($all_usages == 0) {
            return 0;
        }
        return $api_usage / $all_usages;
    }

    public function getAPIFaultPercentage(int $api_number)
    {
        if ((RedisController::getInstance()
                    ->exists("api1_all") && (RedisController::getInstance()
                        ->exists("api1_faults") && $api_number == 1))
            || (RedisController::getInstance()
                    ->exists("api2_all") && (RedisController::getInstance()
                        ->exists("api2_faults") && $api_number == 2))) {
            $api_all = $api_number == 1 ?
                RedisController::getInstance()->get("api1_all") :
                RedisController::getInstance()->get("api2_all");
            $api_faults = $api_number == 1 ?
                RedisController::getInstance()->get("api1_faults") :
                RedisController::getInstance()->get("api2_faults");

        } else {
            $entityManager = $this->getEntityManager();
            try {
                $api_faults = $entityManager->createQueryBuilder()
                    ->select('count(p)')
                    ->from('App\Entity\SMSLog', 'p')
                    ->where('p.used_api = :api and p.hasSent = 0')
                    ->setParameter('api', $api_number)
                    ->getQuery()->getSingleScalarResult();
            } catch (NonUniqueResultException | NoResultException $e) {
                $this->logger->error("Exception at line 135 getAPIFaultPercentage raised: " . $e->getMessage());
                $api_faults = 0;
            }

            try {
                $api_all = $entityManager->createQueryBuilder()
                    ->select('count(p)')
                    ->from('App\Entity\SMSLog', 'p')
                    ->where('p.used_api = :api')
                    ->setParameter('api', $api_number)
                    ->getQuery()->getSingleScalarResult();
            } catch (NoResultException | NonUniqueResultException $e) {
                $this->logger->error("Exception at line 147 getAPIUsage raised: " . $e->getMessage());
                $api_all = 1;
            }
            if ($api_number == 1) {
                RedisController::getInstance()->set("api1_all", $api_all);
                RedisController::getInstance()->set("api1_faults", $api_faults);
                $this->expireKey("api1_all", 5 * 60);
                $this->expireKey("api1_faults", 5 * 60);

            } else {
                RedisController::getInstance()->set("api2_all", $api_all);
                RedisController::getInstance()->set("api2_faults", $api_faults);
                $this->expireKey("api2_all", 5 * 60);
                $this->expireKey("api2_faults", 5 * 60);
            }
        }
        if ($api_all == 0) {
            return 0;
        }
        return $api_faults / $api_all;
    }

    public function expireKey(string $key, int $amount)
    {
        RedisController::getInstance()->expire($key, $amount);
    }

    public function getTop10()
    {
        if (RedisController::getInstance()->exists("top10")) {
            return unserialize(RedisController::getInstance()->get("top10"));
        }
        $entityManager = $this->getEntityManager();
        $top10 = $entityManager->createQueryBuilder()
            ->select('p.number')
            ->from('App\Entity\SMS', 'p')
            ->groupBy('p.number')
            ->orderBy('count(p.id)')
            ->setMaxResults(10)->getQuery()->getScalarResult();
        $new_m = array();
        foreach ($top10 as $item) {
            foreach ($item as $key => $value) {
                $new_m[] = $value;
            }
        }
        RedisController::getInstance()->set("top10", serialize($new_m));
        $this->expireKey("top10", 5 * 60);
        return $new_m;
    }

    public function searchLogs(string $number)
    {
        $entityManager = $this->getEntityManager();
        return $entityManager->createQuery('
                SELECT p
                FROM App\Entity\SMS p
                WHERE p.number = :number
                ')->setParameter('number', $number)->getResult();
    }

    public function getNotSentSMS()
    {
        $entityManager = $this->getEntityManager();
        $not_sent = $entityManager->createQueryBuilder()
            ->select('DISTINCT p.sms_id')
            ->from('App\Entity\SMSLog', 'p')
            ->where('p.hasSent = 0')->getQuery()->getResult();
        $sent = $entityManager->createQueryBuilder()
            ->select('DISTINCT p.sms_id')
            ->from('App\Entity\SMSLog', 'p')
            ->where('p.hasSent = 1')->getQuery()->getResult();
        $new_m = array();
        foreach ($not_sent as $item) {
            foreach ($item as $key => $value) {
                $new_m[] = $value;
            }
        }
        $new_n = array();
        foreach ($sent as $item) {
            foreach ($item as $key => $value) {
                $new_n[] = $value;
            }
        }
        return array_diff($new_m, $new_n);
    }

}
