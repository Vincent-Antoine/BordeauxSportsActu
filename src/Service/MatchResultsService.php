<?php

namespace App\Service;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

class MatchResultsService
{
    private Client $client;
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->client = new Client([
            'timeout' => 10,
            'verify' => false,
            'headers' => [
                'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:125.0) Gecko/20100101 Firefox/125.0',
                'Accept'          => 'application/json, text/plain, */*',
                'Accept-Language' => 'fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7',
                'Referer'         => 'https://www.ffr.fr/',
                'Origin'          => 'https://www.ffr.fr',
                'Connection'      => 'keep-alive',
            ]
        ]);
        $this->logger = $logger;
    }

    public function getMatchResults(string $apiUrl): array
    {
        try {
            $this->logger->info("📡 Appel à l'API pour le club : " . $apiUrl);

            $response = $this->client->request('GET', $apiUrl);

            if ($response->getStatusCode() !== 200) {
                $this->logger->error('Erreur: code HTTP inattendu', [
                    'status_code' => $response->getStatusCode(),
                ]);
                return [];
            }

            $data = json_decode($response->getBody()->getContents(), true);
            if (!$data) {
                $this->logger->error('Impossible de parser la réponse JSON');
                return [];
            }

            $matches = [];
            $phases = $data['data']['competition']['competitions_phases'] ?? [];

            foreach ($phases as $phase) {
                foreach ($phase['competitions_journees'] ?? [] as $journee) {
                    $journeeNom = $journee['nom'] ?? 'Journée inconnue';

                    foreach ($journee['rencontres'] ?? [] as $rencontre) {
                        $dateMatch = $rencontre['date'] ?? 'Date inconnue';
                        $etat = $rencontre['Etat']['nom'] ?? 'État inconnu';

                        $localStructure = $rencontre['local_structure'] ?? [];
                        $visitorStructure = $rencontre['visitor_structure'] ?? [];

                        $localTeam = $localStructure['Regroupement']['nom']
                            ?? $localStructure['Structure']['nom']
                            ?? 'Équipe locale inconnue';

                        $visitorTeam = $visitorStructure['Regroupement']['nom']
                            ?? $visitorStructure['Structure']['nom']
                            ?? 'Équipe visiteuse inconnue';

                        $localScore = $rencontre['RencontreResultatLocale']['pointsDeMarque'] ?? 0;
                        $visitorScore = $rencontre['RencontreResultatVisiteuse']['pointsDeMarque'] ?? 0;

                        $localLogo = $localStructure['Regroupement']['embleme']
                            ?? $localStructure['StructuresItem'][0]['embleme']
                            ?? $localStructure['Structure']['embleme']
                            ?? null;

                        $visitorLogo = $visitorStructure['Regroupement']['embleme']
                            ?? $visitorStructure['StructuresItem'][0]['embleme']
                            ?? $visitorStructure['Structure']['embleme']
                            ?? null;

                        $matches[] = [
                            'journee'       => $journeeNom,
                            'date'          => $dateMatch,
                            'etat'          => $etat,
                            'local_team'    => $localTeam,
                            'local_score'   => $localScore,
                            'local_logo'    => $localLogo,
                            'visitor_team'  => $visitorTeam,
                            'visitor_score' => $visitorScore,
                            'visitor_logo'  => $visitorLogo,
                        ];
                    }
                }
            }

            $this->logger->info('✅ Résultats récupérés : ' . count($matches));
            return $matches;
        } catch (\Exception $e) {
            $this->logger->error('❌ Exception attrapée lors de la récupération des résultats', [
                'exception' => $e->getMessage()
            ]);
            return [];
        }
    }
}
