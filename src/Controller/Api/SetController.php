<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/api/{locale}/sets')]
class SetController extends AbstractController
{
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

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    #[Route('', name: 'api_sets_list', methods: ['GET'])]
    public function list(string $locale): JsonResponse
    {
        $response = $this->client->request('GET', "{$_ENV['TCG_BASE_API_URL']}/{$locale}/sets");
        $data = $response->toArray();

        return $this->json($data);
    }

    #[Route('/{id}', name: 'api_sets_get', methods: ['GET'])]
    public function get(string $locale, string $id): JsonResponse
    {
        $response = $this->client->request('GET', "{$_ENV['TCG_BASE_API_URL']}/{$locale}/sets/{$id}");
        $data = $response->toArray();

        return $this->json($data);
    }

    #[Route('/{id}/open', name: 'api_sets_open', methods: ['GET'])]
    public function open(string $locale, string $id): JsonResponse
    {
        $cards = [];
        $godpack = mt_rand(1, $this->GODPACK_PROBABILITY) === 1;

        if ($godpack) {
            $cards = $this->getRandomCardsGodpack($id, $locale);
        } else {
            $cardsCommon = $this->getRandomCardsCommon($id, $locale);
            $cards = array_merge($cards, $cardsCommon);

            $cardsUncommon = $this->getRandomCardsUncommon($id, $locale);
            $cards = array_merge($cards, $cardsUncommon);

            $cardsEnergy = $this->getRandomCardsEnergy($id, $locale);
            $cards = array_merge($cards, $cardsEnergy);

            $cardsSpecial = $this->getRandomCardsSpecial($id, $locale);
            $cards = array_merge($cards, $cardsSpecial);

            $cardsBonus = $this->getRandomCardsBonus($id, $locale);
            $cards = array_merge($cards, $cardsBonus);
        }

        return $this->json($cards);
    }

    // Obtiene aleatoriamente cartas del slot COMMON.
    // Si no encuentra la cantidad de cartas necesarias, se añaden las cartas restantes sin indicar rareza ni categoría.
    // Se obtiene la cantidad de cartas indicadas si se pasa el parámetro $size, sino se obtienen las indicadas en $SLOT_SIZES['COMMON'].
    private function getRandomCardsCommon(string $set, string $locale, int|null $size = null) {
        $slot = 'COMMON';
        $size = $size ?? $this->SLOT_SIZES[$slot];
        $cards = $this->getRandomCards($set, $locale, $size, $slot);
        $nCards = count($cards);
        if ($size === $nCards) {
            return $cards;
        } else {
            $cardsExtra = $this->getRandomCards($set, $locale, $size-$nCards);
            return array_merge($cards, $cardsExtra);
        }
    }

    // Obtiene aleatoriamente cartas del slot UNCOMMON.
    // Si no encuentra la cantidad de cartas necesarias, se añaden las cartas restantes de getRandomCardsCommon().
    // Se obtiene la cantidad de cartas indicadas si se pasa el parámetro $size, sino se obtienen las indicadas en $SLOT_SIZES['UNCOMMON'].
    private function getRandomCardsUncommon(string $set, string $locale, int|null $size = null) {
        $slot = 'UNCOMMON';
        $size = $size ?? $this->SLOT_SIZES[$slot];
        $cards = $this->getRandomCards($set, $locale, $size, $slot);
        $nCards = count($cards);
        if ($size === $nCards) {
            return $cards;
        } else {
            $cardsExtra = $this->getRandomCardsCommon($set, $locale, $size-$nCards);
            return array_merge($cards, $cardsExtra);
        }
    }

    // Obtiene aleatoriamente cartas de categoría Energy.
    // Si no encuentra la cantidad de cartas necesarias, se añaden las cartas restantes de getRandomCardsCommon().
    // Se obtiene la cantidad de cartas indicadas si se pasa el parámetro $size, sino se obtienen las indicadas en $SLOT_SIZES['ENERGY'].
    private function getRandomCardsEnergy(string $set, string $locale, int|null $size = null) {
        $slot = 'ENERGY';
        $size = $size ?? $this->SLOT_SIZES[$slot];
        $cards = $this->getRandomCards($set, $locale, $size, $slot);
        $nCards = count($cards);
        if ($size === $nCards) {
            return $cards;
        } else {
            $cardsExtra = $this->getRandomCardsCommon($set, $locale, $size-$nCards);
            return array_merge($cards, $cardsExtra);
        }
    }

    // Obtiene aleatoriamente cartas de una rareza aleatoria según SPECIAL_SLOT_WEIGHTS.
    // Si no encuentra la cantidad de cartas necesarias, se añaden las cartas restantes del resto de rarezas SPECIAL y si no del slot NORMAL (COMMON y UNCOMMON).
    // Se obtiene la cantidad de cartas indicadas si se pasa el parámetro $size, sino se obtienen las indicadas en $SLOT_SIZES['SPECIAL'].
    private function getRandomCardsSpecial(string $set, string $locale, int|null $size = null) {
        $slot = 'SPECIAL';
        $size = $size ?? $this->SLOT_SIZES[$slot];
        $rarity = $this->getRandomRarity($this->SPECIAL_SLOT_WEIGHTS);
        $cards = $this->getRandomCards($set, $locale, $size, $rarity);
        $nCards = count($cards);
        if ($size === $nCards) {
            return $cards;
        } else {
            $keys = array_keys($this->SPECIAL_SLOT_WEIGHTS);
            $index = array_search($rarity, $keys) - 1;
            while ($index >= 0) {
                $rarityExtra = $keys[$index];
                $cardsExtra = $this->getRandomCards($set, $locale, $size-$nCards, $rarityExtra);
                $cards = array_merge($cards, $cardsExtra);
                $nCards = count($cards);
                if ($size === $nCards) {
                    return $cards;
                }
                $index--;
            }
            $cardsExtra = $this->getRandomCards($set, $locale, $size-$nCards, 'NORMAL');
            return array_merge($cards, $cardsExtra);
        }
    }

    // Obtiene aleatoriamente cartas de una rareza aleatoria según BONUS_SLOT_WEIGHTS.
    // Si no encuentra la cantidad de cartas necesarias, se añaden las cartas restantes del resto de rarezas BONUS y si no del slot NORMAL (COMMON y UNCOMMON).
    // Se obtiene la cantidad de cartas indicadas si se pasa el parámetro $size, sino se obtienen las indicadas en $SLOT_SIZES['BONUS'].
    private function getRandomCardsBonus(string $set, string $locale, int|null $size = null) {
        $slot = 'BONUS';
        $size = $size ?? $this->SLOT_SIZES[$slot];
        $rarity = $this->getRandomRarity($this->BONUS_SLOT_WEIGHTS);
        $cards = $this->getRandomCards($set, $locale, $size, $rarity);
        $nCards = count($cards);
        if ($size === $nCards) {
            return $cards;
        } else {
            $keys = array_keys($this->BONUS_SLOT_WEIGHTS);
            $index = array_search($rarity, $keys) - 1;
            while ($index >= 0) {
                $rarityExtra = $keys[$index];
                $cardsExtra = $this->getRandomCards($set, $locale, $size-$nCards, $rarityExtra);
                $cards = array_merge($cards, $cardsExtra);
                $nCards = count($cards);
                if ($size === $nCards) {
                    return $cards;
                }
                $index--;
            }
            $cardsExtra = $this->getRandomCards($set, $locale, $size-$nCards, 'NORMAL');
            return array_merge($cards, $cardsExtra);
        }
    }

    // Obtiene aleatoriamente cartas de las rarezas GODPACK (HOLO, ULTRA y SECRET).
    // Si no encuentra la cantidad de cartas necesarias, se añaden las cartas restantes del resto de rarezas GODPACK y si no del slot RARE o NORMAL (COMMON y UNCOMMON).
    // Se obtiene la cantidad de cartas indicadas si se pasa el parámetro $size, sino se obtienen las indicadas en el total de $SLOT_SIZES.
    private function getRandomCardsGodpack(string $set, string $locale, int|null $size = null) {
        $size = $size ?? array_sum($this->SLOT_SIZES);
        $cards = $this->getRandomCards($set, $locale, $size, 'GODPACK');
        $nCards = count($cards);
        if ($size === $nCards) {
            return $cards;
        } else {
            $cardsExtra = $this->getRandomCards($set, $locale, $size-$nCards, 'RARE');
            $cards = array_merge($cards, $cardsExtra);
            $nCards = count($cards);
            if ($size === $nCards) {
                return $cards;
            } else {
                $cardsExtra = $this->getRandomCards($set, $locale, $size-$nCards, 'NORMAL');
                return array_merge($cards, $cardsExtra);
            }
        }
    }

    // Obtiene aleatoriamente cartas de la rareza indicada.
    // Si la rareza es ENERGY, se obtienen todas las cartas con la categoría ENERGY.
    // Si la rareza es NORMAL, se obtienen todas las cartas de la rareza COMMON y UNCOMMON.
    // Si la rareza es GODPACK, se obtienen todas las cartas de la rareza HOLO, ULTRA y SECRET.
    // Si no se indica rareza, se obtienen todas las cartas sin filtro.
    private function getRandomCards(string $set, string $locale, int $size, string|null $rarity = null) {
        $randomCards = [];

        $params['set'] = $set;
        if ($rarity === 'ENERGY') {
            $params['category'] = 'Energy';
        } elseif ($rarity === 'NORMAL') {
            $params['rarity'] = implode('|', array_merge($this->RARITIES['COMMON'], $this->RARITIES['UNCOMMON']));
            $params['category'] = 'not:Energy';
        } elseif ($rarity === 'GODPACK') {
            $params['rarity'] = implode('|', array_merge($this->RARITIES['HOLO'], $this->RARITIES['ULTRA'], $this->RARITIES['SECRET']));
            $params['category'] = 'not:Energy';
        } elseif (is_string($rarity) && isset($this->RARITIES[$rarity])) {
            $params['rarity'] = implode('|', $this->RARITIES[$rarity]);
            $params['category'] = 'not:Energy';
        }
        $allCards = $this->client->request('GET', "{$_ENV['TCG_BASE_API_URL']}/{$locale}/cards", [
            'query' => $params
        ])->toArray();

        if (!empty($allCards)) {
            shuffle($allCards);
            $randomCards = array_merge($randomCards, array_slice($allCards, 0, min($size, count($allCards))));
        }

        foreach ($randomCards as &$card) {
            $card['rarity'] = $rarity;
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
