<?php

namespace App\Repository;

use App\Entity\SMSLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SMSLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method SMSLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method SMSLog[]    findAll()
 * @method SMSLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SMSLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SMSLog::class);
    }


    public function getAPIUsage(int $api_number)
    {
        $entityManager = $this->getEntityManager();
        $api_usages = $entityManager->createQueryBuilder()
            ->select('count(p)')
            ->from('App\Entity\SMSLog', 'p')
            ->where('p.used_api = :api')
            ->setParameter('api', $api_number)
            ->getQuery()->getSingleScalarResult();
        $all_usages = $entityManager->createQueryBuilder()
            ->select('count(p)')
            ->from('App\Entity\SMSLog', 'p')
            ->getQuery()->getSingleScalarResult();
        if ($all_usages == 0) {
            return 0;
        }
        return $api_usages / $all_usages;
    }

    public function getAPIFaultPercentage(int $api_number)
    {
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
        if ($api_all == 0) {
            return 0;
        }
        return $api_faults / $api_all;
    }

    public function getTop10()
    {
        $entityManager = $this->getEntityManager();
        return $entityManager->createQuery('
                SELECT p.number
                FROM App\Entity\SMS p
                GROUP BY p.number
                ORDER BY COUNT(p.id) desc')
            ->setMaxResults(10)->getResult();
    }

    public function searchLogs(string $number)
    {
        $entityManager = $this->getEntityManager();
        return $entityManager->createQuery('
                SELECT *
                FROM App\Entity\SMSLog p
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
