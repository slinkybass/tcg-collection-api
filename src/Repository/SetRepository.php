<?php

namespace App\Repository;

use App\Entity\Set;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Set>
 */
class SetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Set::class);
    }

    public function findIdAPI($idAPI): ?Set
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.idAPI = :idAPI')
            ->setParameter('idAPI', $idAPI)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
