<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\TeamRepository;

#[ORM\Entity(repositoryClass: TeamRepository::class)]
class Team
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $sport = null; // Lien vers le sport du fichier JSON

    #[ORM\Column(length: 255, unique: true, nullable: true)]
    private ?string $slug = null;

    #[ORM\Column(nullable: true)]
    private ?int $scorencoMatchId = null;

    #[ORM\Column(nullable: true)]
    private ?int $scorencoRankingId = null;

    public function getScorencoMatchId(): ?int
    {
        return $this->scorencoMatchId;
    }

    public function setScorencoMatchId(?int $scorencoMatchId): self
    {
        $this->scorencoMatchId = $scorencoMatchId;

        return $this;
    }

    public function getScorencoRankingId(): ?int
    {
        return $this->scorencoRankingId;
    }

    public function setScorencoRankingId(?int $scorencoRankingId): self
    {
        $this->scorencoRankingId = $scorencoRankingId;

        return $this;
    }


    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): static
    {
        $this->slug = $slug;
        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;
        return $this;
    }

    public function getSport(): ?string
    {
        return $this->sport;
    }

    public function setSport(?string $sport): static
    {
        $this->sport = $sport;
        return $this;
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }
}
