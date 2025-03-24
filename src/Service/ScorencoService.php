<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class ScorencoService
{
    private const API_URL = 'https://graphql.scorenco.com/v1/graphql';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface $cache
    ) {}

    /**
     * Récupère les derniers matchs pour plusieurs clubs (avec cache de 5 min)
     */
    public function getMatchsForClubs(array $clubList, int $limit = 3): array
    {
        $results = [];

        foreach ($clubList as $teamId => $clubName) {
            $cacheKey = 'scorenco_matchs_' . $teamId;

            $matchs = $this->cache->get($cacheKey, function (ItemInterface $item) use ($teamId, $limit) {
                $item->expiresAfter(300); // 5 minutes

                $response = $this->httpClient->request('POST', self::API_URL, [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'x-hasura-role' => 'anonymous',
                        'x-hasura-locale' => 'fr-FR',
                    ],
                    'json' => [
                        'operationName' => 'GetMatchs',
                        'variables' => [
                            'teamId' => $teamId,
                            'dateFilter' => -1,
                            'limit' => $limit,
                        ],
                        'query' => <<<GQL
                            query GetMatchs(\$teamId: Int, \$dateFilter: Int, \$limit: Int) {
                                competitions_event_detail_by_team_id(
                                    args: {date_filter: \$dateFilter, id: \$teamId}
                                    limit: \$limit
                                ) {
                                    date
                                    teams
                                    place {
                                        name
                                    }
                                }
                            }
                        GQL
                    ]
                ]);

                $data = $response->toArray(false);

                return $data['data']['competitions_event_detail_by_team_id'] ?? [];
            });

            $results[$clubName] = $matchs;
        }

        return $results;
    }
}
