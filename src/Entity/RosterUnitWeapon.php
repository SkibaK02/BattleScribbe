<?php

namespace App\Entity;

use App\Repository\RosterUnitWeaponRepository;
use Doctrine\ORM\Mapping as ORM;
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
        new Get(normalizationContext: ['groups' => ['roster_unit_weapon:read']]),
        new GetCollection(normalizationContext: ['groups' => ['roster_unit_weapon:read']]),
        new Post(denormalizationContext: ['groups' => ['roster_unit_weapon:write']]),
        new Put(denormalizationContext: ['groups' => ['roster_unit_weapon:write']]),
        new Delete()
    ]
)]
#[ORM\Entity(repositoryClass: RosterUnitWeaponRepository::class)]
class RosterUnitWeapon
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['roster_unit_weapon:read', 'roster_unit:read:full', 'roster:read:full'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'weapons')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    #[Groups(['roster_unit_weapon:read', 'roster_unit_weapon:write'])]
    private ?RosterUnit $rosterUnit = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    #[Groups(['roster_unit_weapon:read', 'roster_unit_weapon:write', 'roster_unit:read:full', 'roster:read:full'])]
    private Weapon $weapon;

    #[ORM\Column]
    #[Assert\Positive]
    #[Groups(['roster_unit_weapon:read', 'roster_unit_weapon:write', 'roster:read:full'])]
    private int $quantity = 1;

    #[ORM\Column]
    #[Groups(['roster_unit_weapon:read', 'roster:read:full'])]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRosterUnit(): ?RosterUnit
    {
        return $this->rosterUnit;
    }

    public function setRosterUnit(?RosterUnit $rosterUnit): self
    {
        $this->rosterUnit = $rosterUnit;
        return $this;
    }

    public function getWeapon(): Weapon
    {
        return $this->weapon;
    }

    public function setWeapon(Weapon $weapon): self
    {
        $this->weapon = $weapon;
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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    #[Groups(['roster_unit_weapon:read', 'roster:read:full'])]
    public function getTotalCost(): int
    {
        return ($this->weapon->getCost() ?? 0) * $this->quantity;
    }
}
