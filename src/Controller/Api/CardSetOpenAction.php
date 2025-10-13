<?php

namespace App\Controller\Api;

use App\Entity\Card;
use App\Entity\Enum\CardCategory;
use App\Entity\CardSet;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class CardSetOpenAction extends AbstractController
{
    private EntityManagerInterface $em;

    // Rarezas categorizadas.
    private const RARITIES = [
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
    private const SLOT_SIZES = [
        'COMMON' => 4,
        'UNCOMMON' => 3,
        'ENERGY' => 1,
        'SPECIAL' => 1,
        'BONUS' => 1,
    ];

    // Porcentaje de rareza para el slot SPECIAL.
    private const SPECIAL_SLOT_WEIGHTS = [
        'RARE' => 60,
        'HOLO' => 30,
        'ULTRA' => 9,
        'SECRET' => 1,
    ];

    // Porcentaje de rareza para el slot BONUS.
    private const BONUS_SLOT_WEIGHTS = [
        'NORMAL' => 80,
        'HOLO' => 15,
        'ULTRA' => 4,
        'SECRET' => 1,
    ];

    // Probabilidad de obtener un sobre GODPACK (1 de cada 4096).
    private const GODPACK_PROBABILITY = 4096;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function __invoke(CardSet $cardSet): JsonResponse
    {
        $cards = [];
        $godpack = random_int(1, self::GODPACK_PROBABILITY) === 1;

        if ($godpack) {
            $cards = $this->getRandomCardsGodpack($cardSet);
        } else {
            $cardsCommon = $this->getRandomCardsCommon($cardSet);
            $cardsUncommon = $this->getRandomCardsUncommon($cardSet);
            $cardsEnergy = $this->getRandomCardsEnergy($cardSet);
            $cardsSpecial = $this->getRandomCardsSpecial($cardSet);
            $cardsBonus = $this->getRandomCardsBonus($cardSet);
            $cards = array_merge(...[$cardsCommon, $cardsUncommon, $cardsEnergy, $cardsSpecial, $cardsBonus]);
        }

        return $this->json($cards, 200, [], ['groups' => 'card:read']);
    }

    // Obtiene aleatoriamente cartas del slot COMMON.
    // Si no encuentra la cantidad de cartas necesarias, se añaden las cartas restantes sin indicar rareza ni categoría.
    // Se obtiene la cantidad de cartas indicadas si se pasa el parámetro $size, sino se obtienen las indicadas en $SLOT_SIZES['COMMON'].
    private function getRandomCardsCommon(CardSet $cardSet, ?int $size = null)
    {
        $slot = 'COMMON';
        $size = $size ?? self::SLOT_SIZES[$slot];
        $cards = $this->getRandomCards($cardSet, $size, $slot);
        return $this->fillMissingCards($cards, $cardSet, $size, fn ($missing) => $this->getRandomCards($cardSet, $missing));
    }

    // Obtiene aleatoriamente cartas del slot UNCOMMON.
    // Si no encuentra la cantidad de cartas necesarias, se añaden las cartas restantes de getRandomCardsCommon().
    // Se obtiene la cantidad de cartas indicadas si se pasa el parámetro $size, sino se obtienen las indicadas en $SLOT_SIZES['UNCOMMON'].
    private function getRandomCardsUncommon(CardSet $cardSet, ?int $size = null)
    {
        $slot = 'UNCOMMON';
        $size = $size ?? self::SLOT_SIZES[$slot];
        $cards = $this->getRandomCards($cardSet, $size, $slot);
        return $this->fillMissingCards($cards, $cardSet, $size, fn ($missing) => $this->getRandomCardsCommon($cardSet, $missing));
    }

    // Obtiene aleatoriamente cartas de categoría Energy.
    // Si no encuentra la cantidad de cartas necesarias, se añaden las cartas restantes de getRandomCardsCommon().
    // Se obtiene la cantidad de cartas indicadas si se pasa el parámetro $size, sino se obtienen las indicadas en $SLOT_SIZES['ENERGY'].
    private function getRandomCardsEnergy(CardSet $cardSet, ?int $size = null)
    {
        $slot = 'ENERGY';
        $size = $size ?? self::SLOT_SIZES[$slot];
        $cards = $this->getRandomCards($cardSet, $size, $slot);
        return $this->fillMissingCards($cards, $cardSet, $size, fn ($missing) => $this->getRandomCardsCommon($cardSet, $missing));
    }

    // Obtiene aleatoriamente cartas de una rareza aleatoria según SPECIAL_SLOT_WEIGHTS.
    // Si no encuentra la cantidad de cartas necesarias, se añaden las cartas restantes del resto de rarezas SPECIAL y si no del slot NORMAL (COMMON y UNCOMMON).
    // Se obtiene la cantidad de cartas indicadas si se pasa el parámetro $size, sino se obtienen las indicadas en $SLOT_SIZES['SPECIAL'].
    private function getRandomCardsSpecial(CardSet $cardSet, ?int $size = null)
    {
        $slot = 'SPECIAL';
        $size = $size ?? self::SLOT_SIZES[$slot];
        $rarity = $this->getRandomRarity(self::SPECIAL_SLOT_WEIGHTS);
        $cards = $this->getRandomCards($cardSet, $size, $rarity);

        $fallback = function ($missing) use ($cardSet, $rarity) {
            $keys = array_keys(self::SPECIAL_SLOT_WEIGHTS);
            $index = array_search($rarity, $keys) - 1;
            $cardsExtra = [];
            while ($index >= 0 && $missing > 0) {
                $cardsRarityExtra = $this->getRandomCards($cardSet, $missing, $keys[$index]);
                $cardsExtra = array_merge($cardsExtra, $cardsRarityExtra);
                $missing -= count($cardsRarityExtra);
                $index--;
            }
            if ($missing > 0) {
                $cardsExtra = array_merge($cardsExtra, $this->getRandomCards($cardSet, $missing, 'NORMAL'));
            }
            return $cardsExtra;
        };

        return $this->fillMissingCards($cards, $cardSet, $size, $fallback);
    }

    // Obtiene aleatoriamente cartas de una rareza aleatoria según BONUS_SLOT_WEIGHTS.
    // Si no encuentra la cantidad de cartas necesarias, se añaden las cartas restantes del resto de rarezas BONUS y si no del slot NORMAL (COMMON y UNCOMMON).
    // Se obtiene la cantidad de cartas indicadas si se pasa el parámetro $size, sino se obtienen las indicadas en $SLOT_SIZES['BONUS'].
    private function getRandomCardsBonus(CardSet $cardSet, ?int $size = null)
    {
        $slot = 'BONUS';
        $size = $size ?? self::SLOT_SIZES[$slot];
        $rarity = $this->getRandomRarity(self::BONUS_SLOT_WEIGHTS);
        $cards = $this->getRandomCards($cardSet, $size, $rarity);

        $fallback = function ($missing) use ($cardSet, $rarity) {
            $keys = array_keys(self::BONUS_SLOT_WEIGHTS);
            $index = array_search($rarity, $keys) - 1;
            $cardsExtra = [];
            while ($index >= 0 && $missing > 0) {
                $cardsRarityExtra = $this->getRandomCards($cardSet, $missing, $keys[$index]);
                $cardsExtra = array_merge($cardsExtra, $cardsRarityExtra);
                $missing -= count($cardsRarityExtra);
                $index--;
            }
            if ($missing > 0) {
                $cardsExtra = array_merge($cardsExtra, $this->getRandomCards($cardSet, $missing, 'NORMAL'));
            }
            return $cardsExtra;
        };

        return $this->fillMissingCards($cards, $cardSet, $size, $fallback);
    }

    // Obtiene aleatoriamente cartas de las rarezas GODPACK (HOLO, ULTRA y SECRET).
    // Si no encuentra la cantidad de cartas necesarias, se añaden las cartas restantes del resto de rarezas GODPACK y si no del slot RARE o NORMAL (COMMON y UNCOMMON).
    // Se obtiene la cantidad de cartas indicadas si se pasa el parámetro $size, sino se obtienen las indicadas en el total de $SLOT_SIZES.
    private function getRandomCardsGodpack(CardSet $cardSet, ?int $size = null)
    {
        $size = $size ?? array_sum(self::SLOT_SIZES);
        $cards = $this->getRandomCards($cardSet, $size, 'GODPACK');

        $fallback = function ($missing) use ($cardSet) {
            $cardsExtra = $this->getRandomCards($cardSet, $missing, 'RARE');
            $missing -= count($cardsExtra);
            if ($missing > 0) {
                $cardsExtra = array_merge($cardsExtra, $this->getRandomCards($cardSet, $missing, 'NORMAL'));
            }
            return $cardsExtra;
        };

        return $this->fillMissingCards($cards, $cardSet, $size, $fallback);
    }

    // Obtiene aleatoriamente cartas de la rareza indicada.
    // Si la rareza es ENERGY, se obtienen todas las cartas con la categoría ENERGY.
    // Si la rareza es NORMAL, se obtienen todas las cartas de la rareza COMMON y UNCOMMON.
    // Si la rareza es GODPACK, se obtienen todas las cartas de la rareza HOLO, ULTRA y SECRET.
    // Si no se indica rareza, se obtienen todas las cartas sin filtro.
    private function getRandomCards(CardSet $cardSet, int $size, ?string $rarity = null)
    {
        $cards = $this->em->getRepository(Card::class)->createQueryBuilder('c')
            ->where('c.cardSet = :cardSet')
            ->setParameter('cardSet', $cardSet);

        if ($rarity === 'ENERGY') {
            $cards->andWhere('c.category = :category')->setParameter('category', CardCategory::ENERGY);
        } elseif ($rarity === 'NORMAL') {
            $cards->andWhere('c.category != :category')->setParameter('category', CardCategory::ENERGY);
            $cards->andWhere('c.rarity IN (:rarity)')->setParameter('rarity', array_merge(self::RARITIES['COMMON'], self::RARITIES['UNCOMMON']));
        } elseif ($rarity === 'GODPACK') {
            $cards->andWhere('c.category != :category')->setParameter('category', CardCategory::ENERGY);
            $cards->andWhere('c.rarity IN (:rarity)')->setParameter('rarity', array_merge(self::RARITIES['HOLO'], self::RARITIES['ULTRA'], self::RARITIES['SECRET']));
        } elseif (isset(self::RARITIES[$rarity])) {
            $cards->andWhere('c.category != :category')->setParameter('category', CardCategory::ENERGY);
            $cards->andWhere('c.rarity IN (:rarity)')->setParameter('rarity', self::RARITIES[$rarity]);
        }

        $cards = $cards->getQuery()->getResult();

        if (!$cards) {
            return [];
        }

        shuffle($cards);
        return array_slice($cards, 0, $size);
    }

    // Si la cantidad de cartas obtenidas es menor que la indicada, se obtienen cartas aleatorias de la rareza indicada.
    private function fillMissingCards(array $cards, CardSet $cardSet, int $size, callable $fallback): array
    {
        $missing = $size - count($cards);
        if ($missing > 0) {
            $extra = $fallback($missing);
            $cards = array_merge($cards, $extra);
        }
        return $cards;
    }

    // Obtiene una rareza aleatoria según los pesos indicados.
    private function getRandomRarity(array $rarityWeights)
    {
        $pick = random_int(1, array_sum($rarityWeights));
        $n = 0;
        foreach ($rarityWeights as $rarity => $weight) {
            $n += $weight;
            if ($pick <= $n) {
                return $rarity;
            }
        }
    }
}
