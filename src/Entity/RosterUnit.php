<?php

namespace App\Entity;

use App\Repository\RosterUnitRepository;
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
        new Get(normalizationContext: ['groups' => ['roster_unit:read', 'roster_unit:read:full']]),
        new GetCollection(normalizationContext: ['groups' => ['roster_unit:read']]),
        new Post(denormalizationContext: ['groups' => ['roster_unit:write']]),
        new Put(denormalizationContext: ['groups' => ['roster_unit:write']]),
        new Delete()
    ]
)]
#[ORM\Entity(repositoryClass: RosterUnitRepository::class)]
class RosterUnit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['roster_unit:read', 'roster:read:full'])]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    #[Groups(['roster_unit:read', 'roster_unit:write', 'roster:read:full'])]
    private UnitTemplate $unitTemplate;

    #[ORM\ManyToOne(inversedBy: 'units')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    #[Groups(['roster_unit:read', 'roster_unit:write'])]
    private ?Roster $roster = null;

    #[ORM\Column]
    #[Assert\PositiveOrZero]
    #[Groups(['roster_unit:read', 'roster_unit:write', 'roster:read:full'])]
    private int $customCost = 0;

    #[ORM\Column]
    #[Assert\Positive]
    #[Groups(['roster_unit:read', 'roster_unit:write', 'roster:read:full'])]
    private int $quantity = 1;

    #[ORM\OneToMany(mappedBy: 'rosterUnit', targetEntity: RosterUnitWeapon::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['roster_unit:read:full', 'roster:read:full'])]
    private Collection $weapons;

    #[ORM\Column]
    #[Groups(['roster_unit:read', 'roster:read:full'])]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->weapons = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUnitTemplate(): UnitTemplate
    {
        return $this->unitTemplate;
    }

    public function setUnitTemplate(UnitTemplate $unitTemplate): self
    {
        $this->unitTemplate = $unitTemplate;
        return $this;
    }

    public function getRoster(): ?Roster
    {
        return $this->roster;
    }

    public function setRoster(?Roster $roster): self
    {
        $this->roster = $roster;
        return $this;
    }

    public function getCustomCost(): int
    {
        return $this->customCost;
    }

    public function setCustomCost(int $customCost): self
    {
        $this->customCost = $customCost;
        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function getWeapons(): Collection
    {
        return $this->weapons;
    }

    public function addWeapon(RosterUnitWeapon $weapon): static
    {
        if (!$this->weapons->contains($weapon)) {
            $this->weapons->add($weapon);
            $weapon->setRosterUnit($this);
        }

        return $this;
    }

    public function removeWeapon(RosterUnitWeapon $weapon): static
    {
        if ($this->weapons->removeElement($weapon)) {
            if ($weapon->getRosterUnit() === $this) {
                $weapon->setRosterUnit(null);
            }
        }

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Oblicza całkowity koszt jednostki (bazowy + custom + bronie) * ilość
     */
    #[Groups(['roster_unit:read', 'roster:read:full'])]
    public function getTotalCost(): int
    {
        $weaponsCost = 0;
        foreach ($this->weapons as $rosterUnitWeapon) {
            $weaponsCost += $rosterUnitWeapon->getTotalCost();
        }

        $unitCost = $this->unitTemplate->getBaseCost() + $this->customCost + $weaponsCost;

        return $unitCost * $this->quantity;
    }

    #[Groups(['roster_unit:read'])]
    public function getWeaponsCount(): int
    {
        return $this->weapons->count();
    }
}
