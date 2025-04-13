<?php

namespace App\EventListener;

use App\Entity\Team;
use App\Repository\TeamRepository;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Symfony\Component\String\Slugger\SluggerInterface;

class TeamSlugListener
{
    public function __construct(
        private SluggerInterface $slugger,
        private TeamRepository $teamRepository
    ) {}

    public function prePersist(Team $team, PrePersistEventArgs $event): void
    {
        $this->setSlug($team);
    }

    public function preUpdate(Team $team, PreUpdateEventArgs $event): void
    {
        $uow = $event->getObjectManager()->getUnitOfWork();
        $changeSet = $uow->getEntityChangeSet($team);

        // Si le nom a changé OU le slug est vide
        if (isset($changeSet['name']) || !$team->getSlug()) {
            $this->setSlug($team);
        }
    }

    private function setSlug(Team $team): void
    {
        // On ne regénère que si aucun slug n’a été défini manuellement
        if ($team->getName()) {
            $baseSlug = $this->slugger->slug($team->getName())->lower();
            $slug = $this->generateUniqueSlug($baseSlug, $team->getId());
            $team->setSlug($slug);
        }
    }

    private function generateUniqueSlug(string $baseSlug, ?int $teamId = null): string
    {
        $slug = $baseSlug;
        $i = 2;

        while (
            $existing = $this->teamRepository->findOneBy(['slug' => $slug])
        ) {
            if ($teamId !== null && $existing->getId() === $teamId) {
                break; // C’est la même entité, on ne considère pas comme un doublon
            }
            $slug = $baseSlug . '-' . $i;
            $i++;
        }

        return $slug;
    }
}
