<?php

namespace App\Controller\Api;

use App\Entity\Card;
use App\Entity\Enum\CardCategory;
use App\Entity\CardSet;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controlador que gestiona la apertura de sobres (CardSet) y devuelve las cartas generadas aleatoriamente.
 *
 * Genera la composición de cartas en función de las rarezas, categorías y pesos definidos.
 * Puede devolver un sobre normal o un sobre especial (GODPACK) con rarezas altas garantizadas.
 *
 * @package App\Controller\Api
 */
class CardSetOpenAction extends AbstractController
{
    private EntityManagerInterface $em;

    /**
     * Mapa de rarezas clasificadas en grupos lógicos.
     *
     * Cada clave representa una categoría interna de rareza, y su valor es un array con las
     * denominaciones exactas utilizadas en la base de datos.
     *
     * Estas agrupaciones permiten filtrar cartas por rareza durante la generación de sobres.
     *
     * @var array<string, string[]>
     */
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

    /**
     * Define la cantidad de cartas que contiene cada tipo de slot en un sobre.
     *
     * @var array<string, int>
     */
    private const SLOT_SIZES = [
        'COMMON' => 4,
        'UNCOMMON' => 3,
        'ENERGY' => 1,
        'SPECIAL' => 1,
        'BONUS' => 1,
    ];

    /**
     * Distribución porcentual de probabilidad de obtener una rareza específica
     * dentro del slot SPECIAL.
     *
     * @var array<string, int>
     */
    private const SPECIAL_SLOT_WEIGHTS = [
        'RARE' => 60,
        'HOLO' => 30,
        'ULTRA' => 9,
        'SECRET' => 1,
    ];

    /**
     * Distribución porcentual de probabilidad de obtener una rareza específica
     * dentro del slot BONUS.
     *
     * @var array<string, int>
     */
    private const BONUS_SLOT_WEIGHTS = [
        'NORMAL' => 80,
        'HOLO' => 15,
        'ULTRA' => 4,
        'SECRET' => 1,
    ];

    /**
     * Probabilidad de obtener un sobre especial conocido como "GODPACK".
     *
     * Un GODPACK contiene exclusivamente cartas de rarezas altas (HOLO, ULTRA y SECRET).
     * La probabilidad se expresa como "1 entre N", donde N es el valor de esta constante.
     *
     * @var int
     */
    private const GODPACK_PROBABILITY = 4096;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Acción principal que simula la apertura de un sobre.
     *
     * Si se genera un GODPACK (1 entre 4096), el sobre contendrá exclusivamente cartas de rarezas altas.
     * En caso contrario, se obtienen cartas de cada tipo de slot (COMMON, UNCOMMON, ENERGY, SPECIAL, BONUS).
     *
     * @param CardSet $cardSet  El conjunto de cartas del cual se generarán las cartas del sobre.
     * @return JsonResponse     Respuesta JSON con las cartas generadas.
     */
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

    /**
     * Obtiene aleatoriamente cartas del slot COMMON.
     *
     * Si no se encuentran suficientes cartas, se completan con cartas sin rareza ni categoría específica.
     *
     * @param CardSet $cardSet  Conjunto de cartas base.
     * @param int|null $size    Cantidad de cartas a obtener. Si no se indica, usa SLOT_SIZES['COMMON'].
     * @return Card[]           Array de cartas obtenidas.
     */
    private function getRandomCardsCommon(CardSet $cardSet, ?int $size = null)
    {
        $slot = 'COMMON';
        $size = $size ?? self::SLOT_SIZES[$slot];
        $cards = $this->getRandomCards($cardSet, $size, $slot);
        return $this->fillMissingCards($cards, $cardSet, $size, fn ($missing) => $this->getRandomCards($cardSet, $missing));
    }

    /**
     * Obtiene aleatoriamente cartas del slot UNCOMMON.
     *
     * Si faltan cartas, se completan con cartas del slot COMMON.
     *
     * @param CardSet $cardSet  Conjunto de cartas base.
     * @param int|null $size    Cantidad de cartas a obtener. Si no se indica, usa SLOT_SIZES['UNCOMMON'].
     * @return Card[]           Array de cartas obtenidas.
     */
    private function getRandomCardsUncommon(CardSet $cardSet, ?int $size = null)
    {
        $slot = 'UNCOMMON';
        $size = $size ?? self::SLOT_SIZES[$slot];
        $cards = $this->getRandomCards($cardSet, $size, $slot);
        return $this->fillMissingCards($cards, $cardSet, $size, fn ($missing) => $this->getRandomCardsCommon($cardSet, $missing));
    }

    /**
     * Obtiene aleatoriamente cartas de la categoría ENERGY.
     *
     * Si faltan cartas, se completan con cartas del slot COMMON.
     *
     * @param CardSet $cardSet  Conjunto de cartas base.
     * @param int|null $size    Cantidad de cartas a obtener. Si no se indica, usa SLOT_SIZES['ENERGY'].
     * @return Card[]           Array de cartas obtenidas.
     */
    private function getRandomCardsEnergy(CardSet $cardSet, ?int $size = null)
    {
        $slot = 'ENERGY';
        $size = $size ?? self::SLOT_SIZES[$slot];
        $cards = $this->getRandomCards($cardSet, $size, $slot);
        return $this->fillMissingCards($cards, $cardSet, $size, fn ($missing) => $this->getRandomCardsCommon($cardSet, $missing));
    }

    /**
     * Obtiene aleatoriamente cartas de una rareza aleatoria según SPECIAL_SLOT_WEIGHTS.
     *
     * Si no se encuentran suficientes cartas, se añaden del resto de rarezas SPECIAL y, si aún faltan, del slot NORMAL (COMMON y UNCOMMON).
     *
     * @param CardSet $cardSet  Conjunto de cartas base.
     * @param int|null $size    Cantidad de cartas a obtener. Si no se indica, usa SLOT_SIZES['SPECIAL'].
     * @return Card[]           Array de cartas obtenidas.
     */
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

    /**
     * Obtiene aleatoriamente cartas de una rareza aleatoria según BONUS_SLOT_WEIGHTS.
     *
     * Si no se encuentran suficientes cartas, se añaden del resto de rarezas BONUS y, si aún faltan, del slot NORMAL (COMMON y UNCOMMON).
     *
     * @param CardSet $cardSet  Conjunto de cartas base.
     * @param int|null $size    Cantidad de cartas a obtener. Si no se indica, usa SLOT_SIZES['BONUS'].
     * @return Card[]           Array de cartas obtenidas.
     */
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

    /**
     * Obtiene aleatoriamente cartas de las rarezas altas (HOLO, ULTRA y SECRET), propias de un GODPACK.
     *
     * Si no se encuentran suficientes cartas, se añaden cartas de rarezas RARE o, si aún faltan, del slot NORMAL.
     *
     * @param CardSet $cardSet  Conjunto de cartas base.
     * @param int|null $size    Cantidad de cartas a obtener. Si no se indica, usa el total de SLOT_SIZES.
     * @return Card[]           Array de cartas obtenidas.
     */
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

    /**
     * Obtiene aleatoriamente cartas según la rareza indicada.
     *
     * @param CardSet $cardSet  Conjunto de cartas base.
     * @param int $size         Cantidad de cartas a obtener.
     * @param string|null $rarity Rareza objetivo (opcional).
     * @return Card[]           Array de cartas obtenidas.
     */
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

    /**
     * Completa un conjunto de cartas si la cantidad obtenida es menor que la esperada.
     *
     * Usa un callback ($fallback) para recuperar las cartas faltantes.
     *
     * @param Card[] $cards     Cartas inicialmente obtenidas.
     * @param CardSet $cardSet  Conjunto de cartas base.
     * @param int $size         Cantidad esperada de cartas.
     * @param callable $fallback Función que obtiene las cartas faltantes.
     * @return Card[]           Array final de cartas completas.
     */
    private function fillMissingCards(array $cards, CardSet $cardSet, int $size, callable $fallback): array
    {
        $missing = $size - count($cards);
        if ($missing > 0) {
            $extra = $fallback($missing);
            $cards = array_merge($cards, $extra);
        }
        return $cards;
    }

    /**
     * Obtiene una rareza aleatoria según los pesos definidos en el array recibido.
     *
     * @param array $rarityWeights Array asociativo de rarezas => peso.
     * @return string              Rareza seleccionada aleatoriamente.
     */
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
