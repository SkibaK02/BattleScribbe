<?php

namespace App\Entity;

use App\Repository\RosterDivisionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RosterDivisionRepository::class)]
class RosterDivision
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'rosterDivisions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Division $division = null;

    #[ORM\ManyToOne(inversedBy: 'rosterDivisions')]
    private ?ArmyInstance $armyInstance = null;

    #[ORM\Column(length: 190)]
    private string $name;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\OneToMany(mappedBy: 'rosterDivision', targetEntity: UnitBuild::class)]
    private Collection $unitBuilds;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->unitBuilds = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(User $owner): self
    {
        $this->owner = $owner;
        return $this;
    }

    public function getDivision(): ?Division
    {
        return $this->division;
    }

    public function setDivision(Division $division): self
    {
        $this->division = $division;
        return $this;
    }

    public function getArmyInstance(): ?ArmyInstance
    {
        return $this->armyInstance;
    }

    public function setArmyInstance(?ArmyInstance $armyInstance): self
    {
        $this->armyInstance = $armyInstance;
        return $this;
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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return Collection<int, UnitBuild>
     */
    public function getUnitBuilds(): Collection
    {
        return $this->unitBuilds;
    }
}
