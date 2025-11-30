<?php

namespace App\Entity;

use App\Repository\FactionRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['faction:read', 'faction:read:full']]),
        new GetCollection(normalizationContext: ['groups' => ['faction:read']]),
        // Brak Post, Put, Delete - frakcje są tylko do odczytu!
    ],
    order: ['name' => 'ASC']
)]
#[ORM\Entity(repositoryClass: FactionRepository::class)]
class Faction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['faction:read', 'roster:read', 'roster:read:full', 'division:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['faction:read', 'roster:read', 'roster:read:full', 'division:read'])]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['faction:read', 'faction:read:full'])]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['faction:read', 'faction:read:full'])]
    private ?string $icon = null;

    #[ORM\OneToMany(mappedBy: 'faction', targetEntity: Division::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['faction:read:full'])]
    private Collection $divisions;

    #[ORM\Column]
    #[Groups(['faction:read'])]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->divisions = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): self
    {
        $this->icon = $icon;
        return $this;
    }

    public function getDivisions(): Collection
    {
        return $this->divisions;
    }

    public function addDivision(Division $division): static
    {
        if (!$this->divisions->contains($division)) {
            $this->divisions->add($division);
            $division->setFaction($this);
        }

        return $this;
    }

    public function removeDivision(Division $division): static
    {
        if ($this->divisions->removeElement($division)) {
            if ($division->getFaction() === $this) {
                $division->setFaction(null);
            }
        }

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    #[Groups(['faction:read'])]
    public function getDivisionsCount(): int
    {
        return $this->divisions->count();
    }
}
