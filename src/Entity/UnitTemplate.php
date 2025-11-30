<?php

namespace App\Entity;

use App\Repository\UnitTemplateRepository;
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
        new Get(normalizationContext: ['groups' => ['unit_template:read', 'unit_template:read:full']]),
        new GetCollection(normalizationContext: ['groups' => ['unit_template:read']]),
        new Post(denormalizationContext: ['groups' => ['unit_template:write']]),
        new Put(denormalizationContext: ['groups' => ['unit_template:write']]),
        new Delete()
    ],
    order: ['type' => 'ASC', 'name' => 'ASC']
)]
#[ORM\Entity(repositoryClass: UnitTemplateRepository::class)]
class UnitTemplate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['unit_template:read', 'division:read:full', 'roster_unit:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 255)]
    #[Groups(['unit_template:read', 'unit_template:write', 'division:read:full', 'roster_unit:read'])]
    private string $name;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Groups(['unit_template:read', 'unit_template:write', 'division:read:full', 'roster_unit:read'])]
    private string $type;

    #[ORM\Column]
    #[Assert\PositiveOrZero]
    #[Groups(['unit_template:read', 'unit_template:write', 'roster_unit:read'])]
    private int $baseCost;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['unit_template:read', 'unit_template:write', 'unit_template:read:full'])]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 1, max: 100)]
    #[Groups(['unit_template:read', 'unit_template:write'])]
    private ?int $minSize = 1;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 1, max: 100)]
    #[Groups(['unit_template:read', 'unit_template:write'])]
    private ?int $maxSize = 1;

    #[ORM\Column(length: 50, nullable: true)]
    #[Assert\Choice(choices: ['Rekrut', 'Standard', 'Veteran'])]
    #[Groups(['unit_template:read', 'unit_template:write', 'roster_unit:read'])]
    private ?string $experience = 'Standard';

    #[ORM\ManyToOne(inversedBy: 'unitTemplates')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    #[Groups(['unit_template:read', 'unit_template:write'])]
    private ?Division $division = null;

    #[ORM\ManyToMany(targetEntity: Weapon::class)]
    #[ORM\JoinTable(name: 'unit_template_weapon')]
    #[Groups(['unit_template:read:full', 'unit_template:write'])]
    private Collection $weapons;

    #[ORM\ManyToMany(targetEntity: SpecialRule::class)]
    #[ORM\JoinTable(name: 'unit_template_special_rule')]
    #[Groups(['unit_template:read:full', 'unit_template:write'])]
    private Collection $specialRules;

    #[ORM\Column]
    #[Groups(['unit_template:read'])]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->weapons = new ArrayCollection();
        $this->specialRules = new ArrayCollection();
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

    public function getBaseCost(): int
    {
        return $this->baseCost;
    }

    public function setBaseCost(int $baseCost): self
    {
        $this->baseCost = $baseCost;
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

    public function getMinSize(): ?int
    {
        return $this->minSize;
    }

    public function setMinSize(?int $minSize): self
    {
        $this->minSize = $minSize;
        return $this;
    }

    public function getMaxSize(): ?int
    {
        return $this->maxSize;
    }

    public function setMaxSize(?int $maxSize): self
    {
        $this->maxSize = $maxSize;
        return $this;
    }

    public function getExperience(): ?string
    {
        return $this->experience;
    }

    public function setExperience(?string $experience): self
    {
        $this->experience = $experience;
        return $this;
    }

    public function getDivision(): ?Division
    {
        return $this->division;
    }

    public function setDivision(?Division $division): self
    {
        $this->division = $division;
        return $this;
    }

    public function getWeapons(): Collection
    {
        return $this->weapons;
    }

    public function addWeapon(Weapon $weapon): self
    {
        if (!$this->weapons->contains($weapon)) {
            $this->weapons->add($weapon);
        }
        return $this;
    }

    public function removeWeapon(Weapon $weapon): static
    {
        $this->weapons->removeElement($weapon);
        return $this;
    }

    public function getSpecialRules(): Collection
    {
        return $this->specialRules;
    }

    public function addSpecialRule(SpecialRule $specialRule): self
    {
        if (!$this->specialRules->contains($specialRule)) {
            $this->specialRules->add($specialRule);
        }
        return $this;
    }

    public function removeSpecialRule(SpecialRule $specialRule): static
    {
        $this->specialRules->removeElement($specialRule);
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    #[Groups(['unit_template:read'])]
    public function getWeaponsCount(): int
    {
        return $this->weapons->count();
    }

    #[Groups(['unit_template:read'])]
    public function getSpecialRulesCount(): int
    {
        return $this->specialRules->count();
    }
}
