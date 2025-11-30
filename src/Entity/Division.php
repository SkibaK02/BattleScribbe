<?php

namespace App\Entity;

use App\Repository\DivisionRepository;
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
        new Get(normalizationContext: ['groups' => ['division:read', 'division:read:full']]),
        new GetCollection(normalizationContext: ['groups' => ['division:read']]),
        new Post(denormalizationContext: ['groups' => ['division:write']]),
        new Put(denormalizationContext: ['groups' => ['division:write']]),
        new Delete()
    ],
    order: ['name' => 'ASC']
)]
#[ORM\Entity(repositoryClass: DivisionRepository::class)]
class Division
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['division:read', 'faction:read:full', 'roster:read', 'roster:read:full', 'unit_template:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 255)]
    #[Groups(['division:read', 'division:write', 'faction:read:full', 'roster:read', 'roster:read:full', 'unit_template:read'])]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['division:read', 'division:write', 'division:read:full'])]
    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'divisions')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    #[Groups(['division:read', 'division:write', 'division:read:full'])]
    private ?Faction $faction = null;

    #[ORM\OneToMany(mappedBy: 'division', targetEntity: UnitTemplate::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['division:read:full'])]
    private Collection $unitTemplates;

    #[ORM\Column]
    #[Groups(['division:read'])]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->unitTemplates = new ArrayCollection();
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

    public function getFaction(): ?Faction
    {
        return $this->faction;
    }

    public function setFaction(?Faction $faction): self
    {
        $this->faction = $faction;
        return $this;
    }

    public function getUnitTemplates(): Collection
    {
        return $this->unitTemplates;
    }

    public function addUnitTemplate(UnitTemplate $unitTemplate): static
    {
        if (!$this->unitTemplates->contains($unitTemplate)) {
            $this->unitTemplates->add($unitTemplate);
            $unitTemplate->setDivision($this);
        }

        return $this;
    }

    public function removeUnitTemplate(UnitTemplate $unitTemplate): static
    {
        if ($this->unitTemplates->removeElement($unitTemplate)) {
            if ($unitTemplate->getDivision() === $this) {
                $unitTemplate->setDivision(null);
            }
        }

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    #[Groups(['division:read', 'faction:read:full'])]
    public function getUnitTemplatesCount(): int
    {
        return $this->unitTemplates->count();
    }
}
