<?php

namespace App\Entity;

use App\Repository\PortraitRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PortraitRepository::class)]
class Portrait
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Article::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Article $article = null;

    #[ORM\Column(type: 'text')]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $nom = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $prenom = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = false;

    #[ORM\Column(type: 'date')]
    private ?\DateTimeInterface $semaineDu = null;

    // Getters & setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getArticle(): ?Article
    {
        return $this->article;
    }

    public function setArticle(?Article $article): self
    {
        $this->article = $article;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): self
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getSemaineDu(): ?\DateTimeInterface
    {
        return $this->semaineDu;
    }

    public function setSemaineDu(\DateTimeInterface $semaineDu): self
    {
        $this->semaineDu = $semaineDu;
        return $this;
    }
}
