<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\UserFavoriteSportRepository;

#[ORM\Entity(repositoryClass: UserFavoriteSportRepository::class)]
#[ORM\Table(name: 'user_favorite_sport')]
class UserFavoriteSport
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'favoriteSports')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Team::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Team $team;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $addedAt;

    public function __construct(User $user, Team $team)
    {
        $this->user = $user;
        $this->team = $team;
        $this->addedAt = new \DateTime();
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getTeam(): Team
    {
        return $this->team;
    }

    public function getAddedAt(): \DateTimeInterface
    {
        return $this->addedAt;
    }

    public function setAddedAt(\DateTimeInterface $addedAt): self
    {
        $this->addedAt = $addedAt;
        return $this;
    }
}
