<?php

namespace App\Controller\Api;

use App\Entity\Card;
use App\Entity\Enum\CardCategory;
use App\Entity\CardSet;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CardSetOpenAction extends AbstractController
{
    private EntityManagerInterface $em;
    private HttpClientInterface $client;

    // Rarezas categorizadas.
    private $RARITIES = [
        'COMMON' => [
            "Common",
            "One Diamond",
        ],
        'UNCOMMON' => [
            "Uncommon",
            "Two Diamond",
            "Crown",
        ],
        'RARE' => [
            "Rare",
            "Three Diamond",
            "One Star",
            "Two Star",
            "Three Star",
            "Black White Rare",
            "Classic Collection",
        ],
        'HOLO' => [
            "Rare Holo",
            "Holo Rare",
            "Rare Holo LV.X",
            "Rare PRIME",
            "Amazing Rare",
            "Radiant Rare",
            "LEGEND",
        ],
        'ULTRA' => [
            "Holo Rare V",
            "Holo Rare VMAX",
            "Holo Rare VSTAR",
            "Full Art Trainer",
            "Double rare",
            "Ultra Rare",
            "Shiny rare",
            "Shiny rare V",
            "Shiny rare VMAX",
            "Illustration rare",
            "Special illustration rare",
        ],
        'SECRET' => [
            "Secret Rare",
            "Hyper rare",
            "ACE SPEC Rare",
            "Shiny Ultra Rare",
            "Four Diamond",
            "One Shiny",
            "Two Shiny",
        ],
    ];

    // Cantidad de cartas por slot en un sobre.
    private $SLOT_SIZES = [
        'COMMON' => 4,
        'UNCOMMON' => 3,
        'ENERGY' => 1,
        'SPECIAL' => 1,
        'BONUS' => 1,
    ];

    // Porcentaje de rareza para el slot SPECIAL.
    private $SPECIAL_SLOT_WEIGHTS = [
        'RARE' => 60,
        'HOLO' => 30,
        'ULTRA' => 9,
        'SECRET' => 1,
    ];

    // Porcentaje de rareza para el slot BONUS.
    private $BONUS_SLOT_WEIGHTS = [
        'NORMAL' => 80,
        'HOLO' => 15,
        'ULTRA' => 4,
        'SECRET' => 1,
    ];

    // Probabilidad de obtener un sobre GODPACK (1 de cada 4096).
    private $GODPACK_PROBABILITY = 4096;

    public function __construct(EntityManagerInterface $em, HttpClientInterface $client)
    {
        $this->em = $em;
        $this->client = $client;
    }

    public function __invoke(CardSet $cardSet): JsonResponse
    {
        $cards = [];
        $godpack = mt_rand(1, $this->GODPACK_PROBABILITY) === 1;

        if ($godpack) {
            $cards = $this->getRandomCardsGodpack($cardSet);
        } else {
            $cardsCommon = $this->getRandomCardsCommon($cardSet);
            $cards = array_merge($cards, $cardsCommon);

            $cardsUncommon = $this->getRandomCardsUncommon($cardSet);
            $cards = array_merge($cards, $cardsUncommon);

            $cardsEnergy = $this->getRandomCardsEnergy($cardSet);
            $cards = array_merge($cards, $cardsEnergy);

            $cardsSpecial = $this->getRandomCardsSpecial($cardSet);
            $cards = array_merge($cards, $cardsSpecial);

            $cardsBonus = $this->getRandomCardsBonus($cardSet);
            $cards = array_merge($cards, $cardsBonus);
        }

        return $this->json($cards, 200, [], ['groups' => 'card:read']);
    }

    // Obtiene aleatoriamente cartas del slot COMMON.
    // Si no encuentra la cantidad de cartas necesarias, se añaden las cartas restantes sin indicar rareza ni categoría.
    // Se obtiene la cantidad de cartas indicadas si se pasa el parámetro $size, sino se obtienen las indicadas en $SLOT_SIZES['COMMON'].
    private function getRandomCardsCommon(CardSet $cardSet, int|null $size = null) {
        $slot = 'COMMON';
        $size = $size ?? $this->SLOT_SIZES[$slot];
        $cards = $this->getRandomCards($cardSet, $size, $slot);
        $nCards = count($cards);
        if ($size === $nCards) {
            return $cards;
        } else {
            $cardsExtra = $this->getRandomCards($cardSet, $size-$nCards);
            return array_merge($cards, $cardsExtra);
        }
    }

    // Obtiene aleatoriamente cartas del slot UNCOMMON.
    // Si no encuentra la cantidad de cartas necesarias, se añaden las cartas restantes de getRandomCardsCommon().
    // Se obtiene la cantidad de cartas indicadas si se pasa el parámetro $size, sino se obtienen las indicadas en $SLOT_SIZES['UNCOMMON'].
    private function getRandomCardsUncommon(CardSet $cardSet, int|null $size = null) {
        $slot = 'UNCOMMON';
        $size = $size ?? $this->SLOT_SIZES[$slot];
        $cards = $this->getRandomCards($cardSet, $size, $slot);
        $nCards = count($cards);
        if ($size === $nCards) {
            return $cards;
        } else {
            $cardsExtra = $this->getRandomCardsCommon($cardSet, $size-$nCards);
            return array_merge($cards, $cardsExtra);
        }
    }

    // Obtiene aleatoriamente cartas de categoría Energy.
    // Si no encuentra la cantidad de cartas necesarias, se añaden las cartas restantes de getRandomCardsCommon().
    // Se obtiene la cantidad de cartas indicadas si se pasa el parámetro $size, sino se obtienen las indicadas en $SLOT_SIZES['ENERGY'].
    private function getRandomCardsEnergy(CardSet $cardSet, int|null $size = null) {
        $slot = 'ENERGY';
        $size = $size ?? $this->SLOT_SIZES[$slot];
        $cards = $this->getRandomCards($cardSet, $size, $slot);
        $nCards = count($cards);
        if ($size === $nCards) {
            return $cards;
        } else {
            $cardsExtra = $this->getRandomCardsCommon($cardSet, $size-$nCards);
            return array_merge($cards, $cardsExtra);
        }
    }

    // Obtiene aleatoriamente cartas de una rareza aleatoria según SPECIAL_SLOT_WEIGHTS.
    // Si no encuentra la cantidad de cartas necesarias, se añaden las cartas restantes del resto de rarezas SPECIAL y si no del slot NORMAL (COMMON y UNCOMMON).
    // Se obtiene la cantidad de cartas indicadas si se pasa el parámetro $size, sino se obtienen las indicadas en $SLOT_SIZES['SPECIAL'].
    private function getRandomCardsSpecial(CardSet $cardSet, int|null $size = null) {
        $slot = 'SPECIAL';
        $size = $size ?? $this->SLOT_SIZES[$slot];
        $rarity = $this->getRandomRarity($this->SPECIAL_SLOT_WEIGHTS);
        $cards = $this->getRandomCards($cardSet, $size, $rarity);
        $nCards = count($cards);
        if ($size === $nCards) {
            return $cards;
        } else {
            $keys = array_keys($this->SPECIAL_SLOT_WEIGHTS);
            $index = array_search($rarity, $keys) - 1;
            while ($index >= 0) {
                $rarityExtra = $keys[$index];
                $cardsExtra = $this->getRandomCards($cardSet, $size-$nCards, $rarityExtra);
                $cards = array_merge($cards, $cardsExtra);
                $nCards = count($cards);
                if ($size === $nCards) {
                    return $cards;
                }
                $index--;
            }
            $cardsExtra = $this->getRandomCards($cardSet, $size-$nCards, 'NORMAL');
            return array_merge($cards, $cardsExtra);
        }
    }

    // Obtiene aleatoriamente cartas de una rareza aleatoria según BONUS_SLOT_WEIGHTS.
    // Si no encuentra la cantidad de cartas necesarias, se añaden las cartas restantes del resto de rarezas BONUS y si no del slot NORMAL (COMMON y UNCOMMON).
    // Se obtiene la cantidad de cartas indicadas si se pasa el parámetro $size, sino se obtienen las indicadas en $SLOT_SIZES['BONUS'].
    private function getRandomCardsBonus(CardSet $cardSet, int|null $size = null) {
        $slot = 'BONUS';
        $size = $size ?? $this->SLOT_SIZES[$slot];
        $rarity = $this->getRandomRarity($this->BONUS_SLOT_WEIGHTS);
        $cards = $this->getRandomCards($cardSet, $size, $rarity);
        $nCards = count($cards);
        if ($size === $nCards) {
            return $cards;
        } else {
            $keys = array_keys($this->BONUS_SLOT_WEIGHTS);
            $index = array_search($rarity, $keys) - 1;
            while ($index >= 0) {
                $rarityExtra = $keys[$index];
                $cardsExtra = $this->getRandomCards($cardSet, $size-$nCards, $rarityExtra);
                $cards = array_merge($cards, $cardsExtra);
                $nCards = count($cards);
                if ($size === $nCards) {
                    return $cards;
                }
                $index--;
            }
            $cardsExtra = $this->getRandomCards($cardSet, $size-$nCards, 'NORMAL');
            return array_merge($cards, $cardsExtra);
        }
    }

    // Obtiene aleatoriamente cartas de las rarezas GODPACK (HOLO, ULTRA y SECRET).
    // Si no encuentra la cantidad de cartas necesarias, se añaden las cartas restantes del resto de rarezas GODPACK y si no del slot RARE o NORMAL (COMMON y UNCOMMON).
    // Se obtiene la cantidad de cartas indicadas si se pasa el parámetro $size, sino se obtienen las indicadas en el total de $SLOT_SIZES.
    private function getRandomCardsGodpack(CardSet $cardSet, int|null $size = null) {
        $size = $size ?? array_sum($this->SLOT_SIZES);
        $cards = $this->getRandomCards($cardSet, $size, 'GODPACK');
        $nCards = count($cards);
        if ($size === $nCards) {
            return $cards;
        } else {
            $cardsExtra = $this->getRandomCards($cardSet, $size-$nCards, 'RARE');
            $cards = array_merge($cards, $cardsExtra);
            $nCards = count($cards);
            if ($size === $nCards) {
                return $cards;
            } else {
                $cardsExtra = $this->getRandomCards($cardSet, $size-$nCards, 'NORMAL');
                return array_merge($cards, $cardsExtra);
            }
        }
    }

    // Obtiene aleatoriamente cartas de la rareza indicada.
    // Si la rareza es ENERGY, se obtienen todas las cartas con la categoría ENERGY.
    // Si la rareza es NORMAL, se obtienen todas las cartas de la rareza COMMON y UNCOMMON.
    // Si la rareza es GODPACK, se obtienen todas las cartas de la rareza HOLO, ULTRA y SECRET.
    // Si no se indica rareza, se obtienen todas las cartas sin filtro.
    private function getRandomCards(CardSet $cardSet, int $size, string|null $rarity = null) {
        $randomCards = [];

        $allCards = $this->em->getRepository(Card::class)->createQueryBuilder('c')
            ->where('c.cardSet = :cardSet')
            ->setParameter('cardSet', $cardSet);

        if ($rarity === 'ENERGY') {
            $allCards->andWhere('c.category = :category')->setParameter('category', CardCategory::ENERGY);
        } elseif ($rarity === 'NORMAL') {
            $allCards->andWhere('c.category != :category')->setParameter('category', CardCategory::ENERGY);
            $allCards->andWhere('c.rarity IN (:rarity)')->setParameter('rarity', array_merge($this->RARITIES['COMMON'], $this->RARITIES['UNCOMMON']));
        } elseif ($rarity === 'GODPACK') {
            $allCards->andWhere('c.category != :category')->setParameter('category', CardCategory::ENERGY);
            $allCards->andWhere('c.rarity IN (:rarity)')->setParameter('rarity', array_merge($this->RARITIES['HOLO'], $this->RARITIES['ULTRA'], $this->RARITIES['SECRET']));
        } elseif (is_string($rarity) && isset($this->RARITIES[$rarity])) {
            $allCards->andWhere('c.category != :category')->setParameter('category', CardCategory::ENERGY);
            $allCards->andWhere('c.rarity IN (:rarity)')->setParameter('rarity', $this->RARITIES[$rarity]);
        }

        $allCards = $allCards->getQuery()->getResult();

        if (!empty($allCards)) {
            shuffle($allCards);
            $randomCards = array_slice($allCards, 0, min($size, count($allCards)));
        }

        return $randomCards;
    }

    // Obtiene una rareza aleatoria según los pesos indicados.
    private function getRandomRarity(array $rarityWeights) {
        $pick = mt_rand(1, array_sum($rarityWeights));
        $n = 0;
        foreach ($rarityWeights as $rarity => $weight) {
            $n += $weight;
            if ($pick <= $n) {
                return $rarity;
            }
        }
    }
}
