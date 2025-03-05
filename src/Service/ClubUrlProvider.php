<?php

namespace App\Service;

class ClubUrlProvider
{
    private array $clubUrls;

    public function __construct(array $clubUrls)
    {
        $this->clubUrls = $clubUrls;
    }

    public function getUrlForClub(string $clubName): ?string
    {
        return $this->clubUrls[$clubName] ?? null;
    }

    public function getAllClubs(): array
    {
        // Retourne la liste de tous les clubs si nécessaire
        return array_keys($this->clubUrls);
    }
}
