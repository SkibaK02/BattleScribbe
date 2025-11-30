<?php

namespace App\Entity;

use App\Repository\SpecialRuleRepository;
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
        new Get(normalizationContext: ['groups' => ['special_rule:read']]),
        new GetCollection(normalizationContext: ['groups' => ['special_rule:read']]),
        new Post(denormalizationContext: ['groups' => ['special_rule:write']]),
        new Put(denormalizationContext: ['groups' => ['special_rule:write']]),
        new Delete()
    ],
    order: ['name' => 'ASC']
)]
#[ORM\Entity(repositoryClass: SpecialRuleRepository::class)]
class SpecialRule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['special_rule:read', 'unit_template:read:full'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 255)]
    #[Groups(['special_rule:read', 'special_rule:write', 'unit_template:read:full'])]
    private string $name;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank]
    #[Groups(['special_rule:read', 'special_rule:write', 'unit_template:read:full'])]
    private string $description;

    #[ORM\Column(length: 50, nullable: true)]
    #[Assert\Choice(choices: ['Positive', 'Negative', 'Neutral'])]
    #[Groups(['special_rule:read', 'special_rule:write'])]
    private ?string $type = 'Neutral';

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero]
    #[Groups(['special_rule:read', 'special_rule:write'])]
    private ?int $pointModifier = null;

    #[ORM\Column]
    #[Groups(['special_rule:read'])]
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

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getPointModifier(): ?int
    {
        return $this->pointModifier;
    }

    public function setPointModifier(?int $pointModifier): self
    {
        $this->pointModifier = $pointModifier;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
