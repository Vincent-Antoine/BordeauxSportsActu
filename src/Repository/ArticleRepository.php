<?php

namespace App\Repository;

use App\Entity\Article;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Article>
 */
class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    //    /**
    //     * @return Article[] Returns an array of Article objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Article
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
public function findLatestSummaries(): array
{
    return $this->createQueryBuilder('a')
        ->select('a.id, a.title, a.slug, a.category, a.updatedAt, a.imageName')
        ->orderBy('a.updatedAt', 'DESC')
        ->setMaxResults(5)
        ->getQuery()
        ->getArrayResult();
}

    public function findByTeam(\App\Entity\Team $team): array
{
    return $this->createQueryBuilder('a')
        ->innerJoin('a.teams', 't')
        ->where('t = :team')
        ->setParameter('team', $team)
        ->orderBy('a.updatedAt', 'DESC')
        ->getQuery()
        ->getResult();
}
}
