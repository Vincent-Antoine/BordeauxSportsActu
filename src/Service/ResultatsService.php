<?php

namespace App\Service;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\HttpClient\HttpClient;

class ResultatsService
{
    private $client;
    private $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->client = HttpClient::create();
        $this->cache = $cache;
    }

    public function getResults(int $clubId): array
    {
        $query = <<<'GRAPHQL'
        query GetMatchs($teamId: Int, $dateFilter: Int, $limit: Int, $cacheTTL: Int = 30) {
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
            $item->expiresAfter(300); // Cache 5 min

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

            return $data['data']['competitions_event_detail_by_team_id'] ?? [];
        });
    }

    // 🔧 NOUVELLE MÉTHODE
    public function getAllResults(array $clubList): array
    {
        $results = [];
        foreach ($clubList as $id => $name) {
            $results[$name] = $this->getResults($id);
        }
        return $results;
    }
}
