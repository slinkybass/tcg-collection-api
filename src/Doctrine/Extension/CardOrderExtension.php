<?php

namespace App\Doctrine\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use App\Entity\Card;

final class CardOrderExtension implements QueryCollectionExtensionInterface
{
    public function applyToCollection(QueryBuilder $qb, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if ($resourceClass !== Card::class) {
            return;
        }

        $alias = $qb->getRootAliases()[0];

        $qb->leftJoin("$alias.cardSet", 's');
		$qb->addSelect("LENGTH($alias.setPos) AS HIDDEN pos_length");
		$qb->addSelect("LENGTH($alias.id) AS HIDDEN id_length");
        $qb->addOrderBy('s.releaseDate', 'ASC');
        $qb->addOrderBy('pos_length', 'ASC');
        $qb->addOrderBy("$alias.setPos", 'ASC');
        $qb->addOrderBy('id_length', 'ASC');
        $qb->addOrderBy("$alias.id", 'ASC');
    }
}
