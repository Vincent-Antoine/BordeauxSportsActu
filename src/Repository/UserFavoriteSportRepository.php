<?php

namespace App\Repository;

use App\Entity\UserFavoriteSport;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserFavoriteSport>
 *
 * @method UserFavoriteSport|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserFavoriteSport|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserFavoriteSport[]    findAll()
 * @method UserFavoriteSport[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserFavoriteSportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserFavoriteSport::class);
    }

    /**
     * Trouver les sports favoris d'un utilisateur
     */
    public function findFavoritesByUser(int $userId): array
    {
        return $this->createQueryBuilder('ufs')
            ->andWhere('ufs.user = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getResult();
    }
}
