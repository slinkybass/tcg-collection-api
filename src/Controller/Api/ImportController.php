<?php

namespace App\Controller\Api;

use App\Entity\Card;
use App\Entity\Enum\CardCategory;
use App\Entity\Serie;
use App\Entity\Set;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/api/{locale}/import')]
class ImportController extends AbstractController
{
    private EntityManagerInterface $em;
    private HttpClientInterface $client;

    public function __construct(EntityManagerInterface $em, HttpClientInterface $client)
    {
        $this->em = $em;
        $this->client = $client;
    }

    #[Route('/series', name: 'api_import_series', methods: ['GET'])]
    public function series(string $locale): JsonResponse
    {
        $response = $this->client->request('GET', "{$_ENV['TCG_BASE_API_URL']}/{$locale}/series");
        if ($response->getStatusCode() !== 200) {
            return $this->json(array(), 404);
        }
        $seriesData = $response->toArray();

        $data = array();
        foreach ($seriesData as $serieData) {
            $response = $this->client->request('GET', "{$_ENV['TCG_BASE_API_URL']}/{$locale}/series/{$serieData['id']}");
            if ($response->getStatusCode() !== 200) {
                continue;
            }
            $serieData = $response->toArray();

            $id = str_replace('.', '_', $serieData['id']);
            $name = array_key_exists('name', $serieData) ? $serieData['name'] : null;
            $logo = array_key_exists('logo', $serieData) ? $serieData['logo'] . '.png' : null;
            $releaseDate = array_key_exists('releaseDate', $serieData) ? new \DateTime($serieData['releaseDate']) : null;

            $serie = $this->em->getRepository(Serie::class)->find($id);
            if (!$serie) {
                $serie = new Serie();
                $serie->setId($id);
            }
            $serie->setName($name);
            $serie->setLogo($logo);
            $serie->setReleaseDate($releaseDate);
            $this->em->persist($serie);

            $data[] = $serie;
        }

        $this->em->flush();

        return $this->json($data);
    }

    #[Route('/sets', name: 'api_import_sets', methods: ['GET'])]
    public function sets(string $locale): JsonResponse
    {
        $response = $this->client->request('GET', "{$_ENV['TCG_BASE_API_URL']}/{$locale}/sets");
        if ($response->getStatusCode() !== 200) {
            return $this->json(array(), 404);
        }
        $setsData = $response->toArray();

        $data = array();
        foreach ($setsData as $setData) {
            $response = $this->client->request('GET', "{$_ENV['TCG_BASE_API_URL']}/{$locale}/sets/{$setData['id']}");
            if ($response->getStatusCode() !== 200) {
                continue;
            }
            $setData = $response->toArray();

            $id = str_replace('.', '_', $setData['id']);
            $serieId = array_key_exists('serie', $setData) ? (array_key_exists('id', $setData['serie']) ? str_replace('.', '_', $setData['serie']['id']) : null) : null;
            $serie = $serieId ? $this->em->getRepository(Serie::class)->find($serieId) : null;
            $name = array_key_exists('name', $setData) ? $setData['name'] : null;
            $logo = array_key_exists('logo', $setData) ? $setData['logo'] . '.png' : null;
            $releaseDate = array_key_exists('releaseDate', $setData) ? new \DateTime($setData['releaseDate']) : null;

            $set = $this->em->getRepository(Set::class)->find($id);
            if (!$set) {
                $set = new Set();
                $set->setId($id);
            }
            $set->setSerie($serie);
            $set->setName($name);
            $set->setLogo($logo);
            $set->setReleaseDate($releaseDate);
            $this->em->persist($set);

            $data[] = $set;
        }

        $this->em->flush();

        return $this->json($data);
    }

    #[Route('/cards', name: 'api_import_cards', methods: ['GET'])]
    public function cards(Request $request, string $locale): JsonResponse
    {
        $setId = $request->query->get('set');
        $sets = $setId ? [ $this->em->getRepository(Set::class)->find($setId) ] : $this->em->getRepository(Set::class)->findAll();

        $data = array();
        foreach ($sets as $set) {
            $setId = str_replace('_', '.', $set->getId());
            $response = $this->client->request('GET', "{$_ENV['TCG_BASE_API_URL']}/{$locale}/sets/{$setId}");
            if ($response->getStatusCode() !== 200) {
                continue;
            }
            $setData = $response->toArray();
            $cardsData = array_key_exists('cards', $setData) ? $setData['cards'] : array();

            foreach ($cardsData as $cardData) {
                $response = $this->client->request('GET', "{$_ENV['TCG_BASE_API_URL']}/{$locale}/cards/{$cardData['id']}");
                if ($response->getStatusCode() !== 200) {
                    continue;
                }
                $cardData = $response->toArray();

                $id = str_replace('.', '_', $cardData['id']);
                $name = array_key_exists('name', $cardData) ? $cardData['name'] : null;
                $localId = array_key_exists('localId', $cardData) ? $cardData['localId'] : null;
                $imageLow = array_key_exists('image', $cardData) ? $cardData['image'] . '/low.png' : null;
                $imageHigh = array_key_exists('image', $cardData) ? $cardData['image'] . '/high.png' : null;
                $category = array_key_exists('category', $cardData) ? CardCategory::from($cardData['category']) : null;
                $illustrator = array_key_exists('illustrator', $cardData) ? $cardData['illustrator'] : null;
                $rarity = array_key_exists('rarity', $cardData) ? $cardData['rarity'] : null;
                $variants = array_key_exists('variants', $cardData) ? $cardData['variants'] : array();
                $variantNormal = array_key_exists('normal', $variants) ? $variants['normal'] : false;
                $variantReverse = array_key_exists('reverse', $variants) ? $variants['reverse'] : false;
                $variantHolo = array_key_exists('holo', $variants) ? $variants['holo'] : false;
                $variantFirstEdition = array_key_exists('firstEdition', $variants) ? $variants['firstEdition'] : false;
                $variantPromo = array_key_exists('wPromo', $variants) ? $variants['wPromo'] : false;
                $updated = array_key_exists('updated', $cardData) ? new \DateTime($cardData['updated']) : null;

                $card = $this->em->getRepository(Card::class)->find($id);
                if (!$card) {
                    $card = new Card();
                    $card->setId($id);
                }
                $card->setCardSet($set);
                $card->setName($name);
                $card->setSetPos($localId);
                $card->setImageLow($imageLow);
                $card->setImageHigh($imageHigh);
                $card->setCategory($category);
                $card->setIllustrator($illustrator);
                $card->setRarity($rarity);
                $card->setUpdated($updated);
                $card->setVariantNormal($variantNormal);
                $card->setVariantReverse($variantReverse);
                $card->setVariantHolo($variantHolo);
                $card->setVariantFirstEdition($variantFirstEdition);
                $card->setVariantPromo($variantPromo);
                $this->em->persist($card);

                $data[] = $card;
            }
        }

        $this->em->flush();

        return $this->json($data);
    }
}
