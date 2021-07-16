<?php


namespace App\Repository;


use App\Entity\Activity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

class ActivityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Activity::class);
    }

    /**
     * @param $value
     * @return Activity|null Returns on Activity object
     * @throws NonUniqueResultException
     */
    public function findByBlockee($value): ?Activity
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.blockee = :val')
            ->setParameter('val', $value)
            ->setMaxResults(10)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param $value
     * @return Activity|null Returns on Activity object
     * @throws NonUniqueResultException
     */
    public function findByBlocker($value): ?Activity
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.blocker = :val')
            ->setParameter('val', $value)
            ->setMaxResults(10)
            ->getQuery()
            ->getOneOrNullResult();
    }


}