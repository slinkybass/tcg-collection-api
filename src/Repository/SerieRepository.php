<?php

namespace App\Repository;

use App\Entity\Serie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Serie>
 */
class SerieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Serie::class);
    }

    public function findIdAPI($idAPI): ?Serie
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.idAPI = :idAPI')
            ->setParameter('idAPI', $idAPI)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
