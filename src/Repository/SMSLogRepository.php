<?php

namespace App\Repository;

use App\Controller\RedisController;
use App\Entity\SMSLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use MessagePack\Packer;

/**
 * @method SMSLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method SMSLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method SMSLog[]    findAll()
 * @method SMSLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SMSLogRepository extends ServiceEntityRepository
{
    private $packer;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SMSLog::class);
        $this->packer = new Packer();
    }

    public function getAllSMS()
    {
        if (RedisController::getInstance()->exists("all_sms")) {
            return unserialize(RedisController::getInstance()->get("all_sms"));
        } else {
            $entityManager = $this->getEntityManager();
            $all_sms = $entityManager->createQueryBuilder()
                ->select("p")
                ->from('App\Entity\SMS', 'p')->getQuery()->getResult();
            RedisController::getInstance()->set("all_sms", serialize($all_sms));
            $this->expireKey("all_sms", 5 * 60);
            return $all_sms;
        }
    }

    public function getAPIUsage(int $api_number)
    {
        if (RedisController::getInstance()
                ->exists("all_usages") && ((RedisController::getInstance()
                        ->exists("api1_usage") && $api_number == 1)
                || (RedisController::getInstance()
                        ->exists("api2_usage") && $api_number == 2))) {
            $all_usages = RedisController::getInstance()->get("all_usages");
            if ($api_number == 1) {
                $api_usage = RedisController::getInstance()->get("api1_usage");
            } else {
                $api_usage = RedisController::getInstance()->get("api2_usage");
            }
        } else {
            $entityManager = $this->getEntityManager();
            $api_usage = $entityManager->createQueryBuilder()
                ->select('count(p)')
                ->from('App\Entity\SMSLog', 'p')
                ->where('p.used_api = :api')
                ->setParameter('api', $api_number)
                ->getQuery()->getSingleScalarResult();
            $all_usages = $entityManager->createQueryBuilder()
                ->select('count(p)')
                ->from('App\Entity\SMSLog', 'p')
                ->getQuery()->getSingleScalarResult();

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
            RedisController::getInstance()->set("redis", "asshole");
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
            $api_faults = $entityManager->createQueryBuilder()
                ->select('count(p)')
                ->from('App\Entity\SMSLog', 'p')
                ->where('p.used_api = :api and p.hasSent = 0')
                ->setParameter('api', $api_number)
                ->getQuery()->getSingleScalarResult();

            $api_all = $entityManager->createQueryBuilder()
                ->select('count(p)')
                ->from('App\Entity\SMSLog', 'p')
                ->where('p.used_api = :api')
                ->setParameter('api', $api_number)
                ->getQuery()->getSingleScalarResult();
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
        $top10 = array_values($entityManager->createQuery('
                SELECT p.number
                FROM App\Entity\SMS p
                GROUP BY p.number
                ORDER BY COUNT(p.id) desc')
            ->setMaxResults(10)->getResult()[0]);
        RedisController::getInstance()->set("top10", serialize($top10));
        $this->expireKey("top10", 5 * 60);
        return $top10;
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


    // /**
    //  * @return SMSLog[] Returns an array of SMSLog objects
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
    public function findOneBySomeField($value): ?SMSLog
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
