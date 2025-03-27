<?php

namespace App\Repository;

use App\Entity\Portrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Portrait>
 */
class PortraitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Portrait::class);
    }

    public function findActivePortrait(): ?Portrait
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.isActive = :val')
            ->setParameter('val', true)
            ->leftJoin('p.article', 'a')
            ->andWhere('a IS NOT NULL')
            ->orderBy('p.semaineDu', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
