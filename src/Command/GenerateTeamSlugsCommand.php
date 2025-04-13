<?php

namespace App\Command;

use App\Repository\TeamRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

#[AsCommand(
    name: 'app:generate-team-slugs',
    description: 'Génère des slugs pour toutes les équipes sans slug.',
)]
class GenerateTeamSlugsCommand extends Command
{
    public function __construct(
        private TeamRepository $teamRepository,
        private EntityManagerInterface $em,
        private SluggerInterface $slugger
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $teams = $this->teamRepository->findAll();

        foreach ($teams as $team) {
            if (!$team->getSlug()) {
                $baseSlug = $this->slugger->slug($team->getName())->lower();
                $slug = $this->generateUniqueSlug($baseSlug);
                $team->setSlug($slug);
                $output->writeln("✅ Slug généré pour « {$team->getName()} » : <info>$slug</info>");
            }
        }

        $this->em->flush();

        $output->writeln('<comment>✅ Tous les slugs ont été générés avec succès.</comment>');
        return Command::SUCCESS;
    }

    private function generateUniqueSlug(string $baseSlug): string
    {
        $slug = $baseSlug;
        $i = 2;

        while ($this->teamRepository->findOneBy(['slug' => $slug])) {
            $slug = $baseSlug . '-' . $i;
            $i++;
        }

        return $slug;
    }
}
