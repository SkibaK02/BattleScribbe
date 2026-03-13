<?php

namespace App\Entity;

use App\Repository\UnitBuildRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UnitBuildRepository::class)]
class UnitBuild
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private User $owner;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private UnitTemplate $unitTemplate;

    #[ORM\ManyToOne(inversedBy: 'unitBuilds')]
    private ?RosterDivision $rosterDivision = null;

    #[ORM\Column(length: 50)]
    private string $experience;

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: 'json')]
    private array $configuration = [];

    #[ORM\Column]
    private int $totalCost = 0;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOwner(): User
    {
        return $this->owner;
    }

    public function setOwner(User $owner): self
    {
        $this->owner = $owner;
        return $this;
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

    public function getRosterDivision(): ?RosterDivision
    {
        return $this->rosterDivision;
    }

    public function setRosterDivision(?RosterDivision $rosterDivision): self
    {
        $this->rosterDivision = $rosterDivision;
        return $this;
    }

    public function getExperience(): string
    {
        return $this->experience;
    }

    public function setExperience(string $experience): self
    {
        $this->experience = $experience;
        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    /**
     * @param array<string, mixed> $configuration
     */
    public function setConfiguration(array $configuration): self
    {
        $this->configuration = $configuration;
        return $this;
    }

    public function getTotalCost(): int
    {
        return $this->totalCost;
    }

    public function setTotalCost(int $totalCost): self
    {
        $this->totalCost = $totalCost;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}

