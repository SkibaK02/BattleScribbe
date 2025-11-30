<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use App\Repository\WeaponRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['weapon:read']]),
        new GetCollection(normalizationContext: ['groups' => ['weapon:read']]),
        new Post(denormalizationContext: ['groups' => ['weapon:write']]),
        new Put(denormalizationContext: ['groups' => ['weapon:write']]),
        new Delete()
    ],
    order: ['type' => 'ASC', 'name' => 'ASC']
)]
#[ORM\Entity(repositoryClass: WeaponRepository::class)]
class Weapon
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['weapon:read', 'unit_template:read:full', 'roster_unit_weapon:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 255)]
    #[Groups(['weapon:read', 'weapon:write', 'unit_template:read:full', 'roster_unit_weapon:read'])]
    private string $name;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['Rifle', 'SMG', 'LMG', 'HMG', 'Pistol', 'Sniper', 'AT', 'Mortar', 'Grenade', 'Melee'])]
    #[Groups(['weapon:read', 'weapon:write', 'unit_template:read:full', 'roster_unit_weapon:read'])]
    private string $type;

    #[ORM\Column]
    #[Assert\Range(min: 1, max: 20)]
    #[Groups(['weapon:read', 'weapon:write', 'unit_template:read:full'])]
    private int $strength;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: -5, max: 10)]
    #[Groups(['weapon:read', 'weapon:write', 'unit_template:read:full'])]
    private ?int $armorPenetration = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 0, max: 200)]
    #[Groups(['weapon:read', 'weapon:write', 'unit_template:read:full'])]
    private ?int $range = null;

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero]
    #[Groups(['weapon:read', 'weapon:write', 'roster_unit_weapon:read'])]
    private ?int $cost = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['weapon:read', 'weapon:write'])]
    private ?string $description = null;

    #[ORM\Column]
    #[Groups(['weapon:read'])]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
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

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getStrength(): int
    {
        return $this->strength;
    }

    public function setStrength(int $strength): self
    {
        $this->strength = $strength;
        return $this;
    }

    public function getArmorPenetration(): ?int
    {
        return $this->armorPenetration;
    }

    public function setArmorPenetration(?int $armorPenetration): self
    {
        $this->armorPenetration = $armorPenetration;
        return $this;
    }

    public function getRange(): ?int
    {
        return $this->range;
    }

    public function setRange(?int $range): self
    {
        $this->range = $range;
        return $this;
    }

    public function getCost(): ?int
    {
        return $this->cost;
    }

    public function setCost(?int $cost): self
    {
        $this->cost = $cost;
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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
