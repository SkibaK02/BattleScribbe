<?php

namespace App\Entity;

use App\Repository\RosterRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['roster:read', 'roster:read:full']]),
        new GetCollection(normalizationContext: ['groups' => ['roster:read']]),
        new Post(denormalizationContext: ['groups' => ['roster:write']]),
        new Put(denormalizationContext: ['groups' => ['roster:write']]),
        new Delete()
    ],
    order: ['id' => 'DESC'],
    paginationEnabled: true
)]
#[ORM\Entity(repositoryClass: RosterRepository::class)]
class Roster
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['roster:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 255)]
    #[Groups(['roster:read', 'roster:write'])]
    private string $name;

    #[ORM\Column]
    #[Assert\Positive]
    #[Groups(['roster:read', 'roster:write'])]
    private int $pointsLimit;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    #[Groups(['roster:read', 'roster:write', 'roster:read:full'])]
    private Faction $faction;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    #[Groups(['roster:read', 'roster:write', 'roster:read:full'])]
    private Division $division;

    #[ORM\OneToMany(mappedBy: 'roster', targetEntity: RosterUnit::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['roster:read:full'])]
    private Collection $units;

    #[ORM\Column]
    #[Groups(['roster:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    #[Groups(['roster:read'])]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->units = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
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

    public function getPointsLimit(): int
    {
        return $this->pointsLimit;
    }

    public function setPointsLimit(int $pointsLimit): self
    {
        $this->pointsLimit = $pointsLimit;
        return $this;
    }

    public function getFaction(): Faction
    {
        return $this->faction;
    }

    public function setFaction(Faction $faction): self
    {
        $this->faction = $faction;
        return $this;
    }

    public function getDivision(): Division
    {
        return $this->division;
    }

    public function setDivision(Division $division): self
    {
        $this->division = $division;
        return $this;
    }

    public function getUnits(): Collection
    {
        return $this->units;
    }

    public function addUnit(RosterUnit $unit): static
    {
        if (!$this->units->contains($unit)) {
            $this->units->add($unit);
            $unit->setRoster($this);
        }

        return $this;
    }

    public function removeUnit(RosterUnit $unit): static
    {
        if ($this->units->removeElement($unit)) {
            if ($unit->getRoster() === $this) {
                $unit->setRoster(null);
            }
        }

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Oblicza całkowity koszt punktowy rostera
     */
    #[Groups(['roster:read', 'roster:read:full'])]
    public function getTotalCost(): int
    {
        $total = 0;
        foreach ($this->units as $unit) {
            $total += $unit->getTotalCost();
        }
        return $total;
    }

    /**
     * Oblicza pozostałe punkty do wykorzystania
     */
    #[Groups(['roster:read', 'roster:read:full'])]
    public function getRemainingPoints(): int
    {
        return $this->pointsLimit - $this->getTotalCost();
    }

    /**
     * Sprawdza czy roster jest w limicie punktów
     */
    #[Groups(['roster:read', 'roster:read:full'])]
    public function isWithinPointsLimit(): bool
    {
        return $this->getTotalCost() <= $this->pointsLimit;
    }

    /**
     * Zwraca liczbę jednostek w rosterze
     */
    #[Groups(['roster:read'])]
    public function getUnitsCount(): int
    {
        return $this->units->count();
    }
}
