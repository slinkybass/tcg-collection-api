<?php

namespace App\Repository;

use App\Entity\Card;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Card>
 */
class CardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Card::class);
    }

    public function findIdAPI($idAPI): ?Card
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.idAPI = :idAPI')
            ->setParameter('idAPI', $idAPI)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
