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
            'timeout'  => 10,
            'verify'   => false,
        ]);
        $this->logger = $logger;
    }

    public function getMatchResults(string $apiUrl): array
    {
        try {
            // 1. Requête HTTP
            $response = $this->client->request('GET', $apiUrl);

            if ($response->getStatusCode() !== 200) {
                $this->logger->error('Erreur: code HTTP inattendu', [
                    'status_code' => $response->getStatusCode(),
                ]);
                return [];
            }

            // 2. Parser le JSON
            $data = json_decode($response->getBody()->getContents(), true);
            if (!$data) {
                $this->logger->error('Impossible de parser la réponse JSON');
                return [];
            }

            // 3. Extraire les matchs
            $matches = [];
            $phases = $data['data']['competition']['competitions_phases'] ?? [];

            foreach ($phases as $phase) {
                $journees = $phase['competitions_journees'] ?? [];
                foreach ($journees as $journee) {
                    $journeeNom = $journee['nom'] ?? 'Journée inconnue';

                    $rencontres = $journee['rencontres'] ?? [];
                    foreach ($rencontres as $rencontre) {
                        $dateMatch = $rencontre['date'] ?? 'Date inconnue';
                        $etat = $rencontre['Etat']['nom'] ?? 'État inconnu';

                        // --- Récupération des structures (local/visitor)
                        $localStructure   = $rencontre['local_structure']   ?? [];
                        $visitorStructure = $rencontre['visitor_structure'] ?? [];

                        // ==================================================
                        // Récupération du nom de l'équipe locale
                        // ==================================================
                        $localTeam = 'Équipe locale inconnue';
                        if (!empty($localStructure['Regroupement'])) {
                            $localTeam = $localStructure['Regroupement']['nom'] 
                                ?? 'Équipe locale inconnue';
                        } elseif (!empty($localStructure['Structure'])) {
                            $localTeam = $localStructure['Structure']['nom'] 
                                ?? 'Équipe locale inconnue';
                        }

                        // ==================================================
                        // Récupération du nom de l'équipe visiteuse
                        // ==================================================
                        $visitorTeam = 'Équipe visiteuse inconnue';
                        if (!empty($visitorStructure['Regroupement'])) {
                            $visitorTeam = $visitorStructure['Regroupement']['nom']
                                ?? 'Équipe visiteuse inconnue';
                        } elseif (!empty($visitorStructure['Structure'])) {
                            $visitorTeam = $visitorStructure['Structure']['nom']
                                ?? 'Équipe visiteuse inconnue';
                        }

                        // ==================================================
                        // Récupération du score
                        // ==================================================
                        $localScore   = $rencontre['RencontreResultatLocale']['pointsDeMarque']    ?? 0;
                        $visitorScore = $rencontre['RencontreResultatVisiteuse']['pointsDeMarque'] ?? 0;

                        // ==================================================
                        // Récupération du logo local
                        // ==================================================
                        $localLogo = null;
                        if (!empty($localStructure['Regroupement'])) {
                            $localLogo = $localStructure['Regroupement']['embleme'] ?? null;
                        } elseif (!empty($localStructure['StructuresItem'])) {
                            $localLogo = $localStructure['StructuresItem'][0]['embleme'] ?? null;
                        } elseif (!empty($localStructure['Structure'])) {
                            $localLogo = $localStructure['Structure']['embleme'] ?? null;
                        }

                        // ==================================================
                        // Récupération du logo visiteur
                        // ==================================================
                        $visitorLogo = null;
                        if (!empty($visitorStructure['Regroupement'])) {
                            $visitorLogo = $visitorStructure['Regroupement']['embleme'] ?? null;
                        } elseif (!empty($visitorStructure['StructuresItem'])) {
                            $visitorLogo = $visitorStructure['StructuresItem'][0]['embleme'] ?? null;
                        } elseif (!empty($visitorStructure['Structure'])) {
                            $visitorLogo = $visitorStructure['Structure']['embleme'] ?? null;
                        }

                        // ==================================================
                        // Final: on ajoute le match au tableau
                        // ==================================================
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

            return $matches;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération des résultats', [
                'exception' => $e->getMessage()
            ]);
            return [];
        }
    }
}
