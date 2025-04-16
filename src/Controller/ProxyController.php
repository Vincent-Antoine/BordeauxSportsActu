<?php

namespace App\Controller;

use GuzzleHttp\Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ProxyController extends AbstractController
{
    #[Route('/proxy', name: 'api_proxy', methods: ['GET'])]
    public function proxy(Request $request): JsonResponse
    {
        $url = $request->query->get('url');
        if (!$url) {
            return $this->json(['error' => 'Paramètre URL manquant'], 400);
        }

        try {
            $client = new Client([
                'timeout' => 10,
                'verify' => false,
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:125.0) Gecko/20100101 Firefox/125.0',
                    'Accept' => 'application/json, text/plain, */*',
                    'Accept-Language' => 'fr-FR,fr;q=0.9',
                    'Referer' => 'https://www.ffr.fr/',
                    'Origin' => 'https://www.ffr.fr',
                ]
            ]);

            $response = $client->request('GET', $url);
            $data = json_decode($response->getBody()->getContents(), true);

            return $this->json($data);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Échec du proxy', 'details' => $e->getMessage()], 500);
        }
    }
}
