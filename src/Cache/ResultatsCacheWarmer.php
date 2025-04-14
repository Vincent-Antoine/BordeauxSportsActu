<?php
namespace App\Cache;

use App\Repository\TeamRepository;
use App\Service\ResultatsService;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

class ResultatsCacheWarmer implements CacheWarmerInterface
{
    private ResultatsService $resultatsService;
    private TeamRepository $teamRepository;

    public function __construct(ResultatsService $resultatsService, TeamRepository $teamRepository)
    {
        $this->resultatsService = $resultatsService;
        $this->teamRepository = $teamRepository;
    }

    public function isOptional(): bool
    {
        return false;
    }

    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        $teams = $this->teamRepository->findAllWithScorencoIds();

        $clubIds = [];
        $rankingIds = [];

        foreach ($teams as $team) {
            $clubIds[(int)$team->getScorencoMatchId()] = $team->getName();
            $rankingIds[$team->getName()] = (int)$team->getScorencoRankingId();
        }

        $this->resultatsService->getAllResults($clubIds);
        $this->resultatsService->getAllRankings(array_keys($clubIds));
        $this->resultatsService->getAllStandings($rankingIds);

        return [];
    }
}
