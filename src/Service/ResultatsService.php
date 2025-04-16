<?php

namespace App\Service;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Psr\Log\LoggerInterface;

class ResultatsService
{
    private $client;
    private $cache;
    private string $dataPath;
    private LoggerInterface $logger;

    public function __construct(
        CacheInterface $cache,
        ParameterBagInterface $params,
        LoggerInterface $logger,
        private readonly ScorencoService $scorencoService
    ) {
        $this->client = HttpClient::create();
        $this->cache = $cache;
        $this->logger = $logger;

        $projectDir = $params->get('kernel.project_dir');
        $this->dataPath = $projectDir . '/scripts/public/data/resultats.json';
    }

    public function getResults(int $clubId): array
{
    $cacheKey = 'scorenco_' . $clubId;

    return $this->cache->get($cacheKey, function (ItemInterface $item) use ($clubId) {
        $item->expiresAfter(300); // ⏱️ 5 minutes

        try {
            $results = $this->fetchResultsFromApi($clubId);

            if (empty($results)) {
                $this->logger->warning("[Scorenco] Données vides pour clubId={$clubId}, cache non enregistré.");
                // On empêche le cache d'enregistrer une valeur vide
                throw new \RuntimeException("Résultat vide pour club {$clubId}, on ne le met pas en cache.");
            }

            $this->logger->info("[Scorenco] Résultats récupérés avec succès pour clubId={$clubId}");
            return $results;

        } catch (\Throwable $e) {
            $this->logger->error("[Scorenco] Erreur lors de la récupération des résultats pour clubId={$clubId}", [
                'error' => $e->getMessage()
            ]);
            throw $e; // Important : ne pas cacher l'erreur, ça évite de stocker un résultat vide
        }
    });
}


    private function fetchResultsFromApi(int $clubId): array
{
    $this->logger->info("🟢 [fetchResultsFromApi] Démarrage pour club ID: {$clubId}");

    $query = <<<'GRAPHQL'
        query GetMatchs($teamId: Int, $dateFilter: Int, $limit: Int) {
            competitions_event_detail_by_team_id(
                args: {date_filter: $dateFilter, id: $teamId}
                limit: $limit
            ) {
                id
                status
                date
                time
                url
                teams
                level_name
                place {
                    id
                    name
                }
            }
        }
    GRAPHQL;

    $variables = [
        'teamId' => $clubId,
        'dateFilter' => -1,
        'limit' => 10,
    ];

    try {
        $this->logger->info("[fetchResultsFromApi] Envoi requête GraphQL à Scorenco", [
            'variables' => $variables,
        ]);

        $response = $this->client->request('POST', 'https://graphql.scorenco.com/v1/graphql', [
            'headers' => [
                'Content-Type' => 'application/json',
                'x-hasura-role' => 'anonymous',
                'x-hasura-locale' => 'fr-FR',
            ],
            'json' => [
                'query' => $query,
                'variables' => $variables,
                'operationName' => 'GetMatchs',
            ],
        ]);

        $data = $response->toArray(false);

        if (!isset($data['data']['competitions_event_detail_by_team_id'])) {
            $this->logger->warning("[fetchResultsFromApi] Pas de données 'competitions_event_detail_by_team_id' pour club ID: {$clubId}", [
                'response' => $data,
            ]);
            return [];
        }

        $matches = $data['data']['competitions_event_detail_by_team_id'];
        $this->logger->info("[fetchResultsFromApi] {$clubId} => {$matches ? count($matches) : 0} match(s) récupéré(s)");

        $formatted = [];
        foreach ($matches as $match) {
            $teams = $match['teams'] ?? [];

            $formatted[] = [
                'date' => $match['date'] ?? null,
                'time' => $match['time'] ?? null,
                'home_team' => $teams[0]['name_in_competition'] ?? null,
                'home_score' => $teams[0]['score'] ?? null,
                'home_logo' => $teams[0]['logo'] ?? null,
                'away_team' => $teams[1]['name_in_competition'] ?? null,
                'away_score' => $teams[1]['score'] ?? null,
                'away_logo' => $teams[1]['logo'] ?? null,
            ];
        }

        return $formatted;
    } catch (\Throwable $e) {
        $this->logger->error("[fetchResultsFromApi] Erreur pour club ID {$clubId}: " . $e->getMessage(), [
            'exception' => $e,
        ]);
        return [];
    }
}


    public function getAllResults(array $clubList): array
    {
        $cacheKey = 'scorenco_all_results_' . md5(json_encode($clubList));

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($clubList) {
            $item->expiresAfter(300);

            $results = [];
            foreach ($clubList as $id => $name) {
                $results[$name] = $this->fetchResultsFromApi($id);
            }

            return $results;
        });
    }

    public function getAllRankings(array $teamIds): array
    {
        $cacheKey = 'scorenco_all_rankings';

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($teamIds) {
            $item->expiresAfter(300);

            $rankings = [];
            foreach ($teamIds as $teamId) {
                $rankings[$teamId] = $this->getRanking($teamId);
            }

            return $rankings;
        });
    }

    public function getAllStandings(array $teamInSeasonIds): array
    {
        $cacheKey = 'scorenco_all_standings';

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($teamInSeasonIds) {
            $item->expiresAfter(300);

            $standings = [];
            foreach ($teamInSeasonIds as $teamName => $teamInSeasonId) {
                $partial = $this->getStandings([$teamName => $teamInSeasonId]);
                if (!empty($partial[$teamName])) {
                    $standings[$teamName] = $partial[$teamName];
                }
            }

            return $standings;
        });
    }

    public function getResultsForClub(int $teamId, int $limit = 3): array
    {
        return $this->getResults($teamId);
    }

    public function getRanking(int $teamId): array
    {
        $query = <<<'GRAPHQL'
            query TeamRankingQuery($teamId: Int, $cacheTTL: Int = 120) @cached(ttl: $cacheTTL) {
                teams_team_detail_by_pk(args: {id: $teamId}) {
                    id
                    team_id
                    name_in_club
                    team_in_season {
                        last_competition_with_ranking {
                            id
                            name
                            rankings {
                                id
                                name_in_competition
                                name_in_club
                                pts
                                rank
                                played
                                win
                                lost
                                logo
                                url
                                serie
                            }
                        }
                    }
                }
            }
        GRAPHQL;

        $variables = [
            'teamId' => $teamId,
            'cacheTTL' => 120,
        ];

        $cacheKey = 'scorenco_ranking_' . $teamId;

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($query, $variables) {
            $item->expiresAfter(300);

            $response = $this->client->request('POST', 'https://graphql.scorenco.com/v1/graphql', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'x-hasura-role' => 'anonymous',
                    'x-hasura-locale' => 'fr-FR',
                ],
                'json' => [
                    'query' => $query,
                    'variables' => $variables,
                    'operationName' => 'TeamRankingQuery',
                ],
            ]);

            $data = $response->toArray(false);
            $teamDetail = $data['data']['teams_team_detail_by_pk'][0] ?? null;

            if (!$teamDetail || !isset($teamDetail['team_in_season']['last_competition_with_ranking'][0]['rankings'])) {
                return [];
            }

            $rankings = $teamDetail['team_in_season']['last_competition_with_ranking'][0]['rankings'];

            $formatted = [];
            foreach ($rankings as $entry) {
                $formatted[] = [
                    'rank' => $entry['rank'],
                    'name' => $entry['name_in_competition'] ?? $entry['name_in_club'],
                    'pts' => $entry['pts'],
                    'played' => $entry['played'],
                    'win' => $entry['win'],
                    'lost' => $entry['lost'],
                    'logo' => $entry['logo'],
                    'url' => $entry['url'],
                    'serie' => $entry['serie'] ?? [],
                ];
            }

            return $formatted;
        });
    }

    public function getStandings(array $teamInSeasonIds): array
    {
        $standings = [];

        foreach ($teamInSeasonIds as $teamName => $teamInSeasonId) {
            $query = <<<'GRAPHQL'
                query GetStandings($teamInSeasonId: Int) {
                    competitions_competition_ranking_by_team_in_season_id(args: {team_in_season_id: $teamInSeasonId}) {
                        id
                        name
                        rankings {
                            id
                            name_in_competition
                            name_in_club
                            pts
                            rank
                            played
                            win
                            lost
                            logo
                            url
                            serie
                        }
                    }
                }
            GRAPHQL;

            $variables = ['teamInSeasonId' => $teamInSeasonId];
            $cacheKey = 'scorenco_standings_' . $teamInSeasonId;

            $result = $this->cache->get($cacheKey, function (ItemInterface $item) use ($query, $variables) {
                $item->expiresAfter(300);

                $response = $this->client->request('POST', 'https://graphql.scorenco.com/v1/graphql', [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'x-hasura-role' => 'anonymous',
                        'x-hasura-locale' => 'fr-FR',
                    ],
                    'json' => [
                        'query' => $query,
                        'variables' => $variables,
                        'operationName' => 'GetStandings',
                    ],
                ]);

                $data = $response->toArray(false);
                return $data['data']['competitions_competition_ranking_by_team_in_season_id'][0]['rankings'] ?? [];
            });

            $main = array_filter($result, fn($entry) => isset($entry['rank']));

            if (!empty($main)) {
                $entry = array_values($main)[0];
                $standings[$teamName] = [
                    'position' => $entry['rank'],
                    'points' => $entry['pts'],
                    'played' => $entry['played'],
                    'win' => $entry['win'],
                    'lost' => $entry['lost'],
                    'name' => $entry['name_in_competition'] ?? $entry['name_in_club'],
                    'logo' => $entry['logo'] ?? null,
                ];
            }
        }

        return $standings;
    }

    public function getLegacyResults(): array
    {
        if (!file_exists($this->dataPath)) {
            $this->logger->error('Fichier de résultats non trouvé.', ['path' => $this->dataPath]);
            throw new FileNotFoundException(sprintf('Fichier non trouvé : %s', $this->dataPath));
        }

        $jsonContent = file_get_contents($this->dataPath);
        $data = json_decode($jsonContent, true);

        return [
            'football' => $data['football'] ?? [],
            'rugby' => $data['rugby'] ?? [],
            'rugby_f' => $data['rugby_f'] ?? [],
            'hockey' => $data['hockey'] ?? [],
            'basket' => $data['basket'] ?? [],
            'volley' => $data['volley'] ?? [],
        ];
    }

    public function getResultsForSport(string $sport): array
    {
        if (!file_exists($this->dataPath)) {
            $this->logger->error('Fichier de résultats non trouvé.', ['path' => $this->dataPath]);
            throw new FileNotFoundException(sprintf('Fichier non trouvé : %s', $this->dataPath));
        }

        $jsonContent = file_get_contents($this->dataPath);
        $data = json_decode($jsonContent, true);

        return $data[$sport] ?? [];
    }
}

?>