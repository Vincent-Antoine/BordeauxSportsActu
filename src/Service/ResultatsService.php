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

    /**
     * Requête API GraphQL pour un club (via son ID Scorenco)
     */
    public function getResults(int $clubId): array
    {
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

        $cacheKey = 'scorenco_' . $clubId;

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($query, $variables) {
            $item->expiresAfter(300); // 5 minutes

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

            $data = $response->toArray();

            $matches = $data['data']['competitions_event_detail_by_team_id'] ?? [];

            // 🧪 DEBUG : Affiche toutes les équipes pour extraire team_in_season_id
            foreach ($matches as $match) {
                $teams = $match['teams'] ?? [];

                foreach ($teams as $team) {
                    // var_dump("🧪 Équipe détectée (team_id = " . ($team['team_id'] ?? 'N/A') . ")");
                    // var_dump($team);
                }
            }

            // ➜ On convertit au bon format pour le template
            $formatted = [];
            foreach ($matches as $match) {
                $teams = $match['teams'] ?? [];

                $homeTeam = $teams[0]['name_in_competition'] ?? null;
                $awayTeam = $teams[1]['name_in_competition'] ?? null;
                $homeScore = $teams[0]['score'] ?? null;
                $awayScore = $teams[1]['score'] ?? null;
                $homeLogo = $teams[0]['logo'] ?? null;
                $awayLogo = $teams[1]['logo'] ?? null;

                $formatted[] = [
                    'date' => $match['date'] ?? null,
                    'time' => $match['time'] ?? null,
                    'home_team' => $homeTeam,
                    'home_score' => $homeScore,
                    'home_logo' => $homeLogo,
                    'away_team' => $awayTeam,
                    'away_score' => $awayScore,
                    'away_logo' => $awayLogo,
                ];
            }

            return $formatted;
        });
    }

    /**
     * Requête API GraphQL pour récupérer le classement d'une équipe via son ID Scorenco
     */
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
            $item->expiresAfter(300); // 5 minutes

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


    /**
     * Pour obtenir tous les résultats via GraphQL (plusieurs IDs)
     */
    public function getAllResults(array $clubList): array
    {
        $results = [];
        foreach ($clubList as $id => $name) {
            $results[$name] = $this->getResults($id);
        }
        return $results;
    }

    /**
     * Ancienne méthode : récupère les résultats depuis le fichier JSON local
     */
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

            $variables = [
                'teamInSeasonId' => $teamInSeasonId
            ];

            $cacheKey = 'scorenco_standings_' . $teamInSeasonId;

            $result = $this->cache->get($cacheKey, function (ItemInterface $item) use ($query, $variables) {
                $item->expiresAfter(300); // 5 minutes

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

            // On prend la première entrée du classement (celle de l'équipe concernée)
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

public function getResultsForClub(int $teamId, int $limit = 3): array
{
    return $this->getResults($teamId); // format déjà formaté avec home_team, away_team, etc.
}





    /**
     * Ancienne méthode : résultats pour un sport spécifique (depuis le JSON)
     */
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
