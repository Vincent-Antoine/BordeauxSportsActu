<?php

namespace App\Entity;

use App\Repository\EvenementRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EvenementRepository::class)]
class Evenement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $equipeDomicile;

    #[ORM\Column(type: 'string', length: 255)]
    private string $equipeExterieur;

    #[ORM\Column(type: 'string', length: 255)]
    private string $lieu;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $date;

    #[ORM\Column(type: 'string', length: 255)]
    private string $competition;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEquipeDomicile(): string
    {
        return $this->equipeDomicile;
    }

    public function setEquipeDomicile(string $equipeDomicile): self
    {
        $this->equipeDomicile = $equipeDomicile;
        return $this;
    }

    public function getEquipeExterieur(): string
    {
        return $this->equipeExterieur;
    }

    public function setEquipeExterieur(string $equipeExterieur): self
    {
        $this->equipeExterieur = $equipeExterieur;
        return $this;
    }

    public function getLieu(): string
    {
        return $this->lieu;
    }

    public function setLieu(string $lieu): self
    {
        $this->lieu = $lieu;
        return $this;
    }

    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;
        return $this;
    }

    public function getCompetition(): string
    {
        return $this->competition;
    }

    public function setCompetition(string $competition): self
    {
        $this->competition = $competition;
        return $this;
    }
}
