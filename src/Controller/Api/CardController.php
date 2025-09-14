<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/api/{locale}/cards')]
class CardController extends AbstractController
{
    private HttpClientInterface $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    #[Route('', name: 'api_cards_list', methods: ['GET'])]
    public function list(string $locale): JsonResponse
    {
        $response = $this->client->request('GET', "{$_ENV['TCG_BASE_API_URL']}/{$locale}/cards");
        $data = $response->toArray();

        return $this->json($data);
    }

    #[Route('/{id}', name: 'api_cards_get', methods: ['GET'])]
    public function get(string $locale, string $id): JsonResponse
    {
        $response = $this->client->request('GET', "{$_ENV['TCG_BASE_API_URL']}/{$locale}/cards/{$id}");
        $data = $response->toArray();

        return $this->json($data);
    }
}
