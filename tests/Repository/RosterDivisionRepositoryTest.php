<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\Division;
use App\Entity\Faction;
use App\Entity\RosterDivision;
use App\Entity\User;
use App\Repository\RosterDivisionRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Assert;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class RosterDivisionRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private RosterDivisionRepository $repo;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);
        $this->em = $em;
        /** @var RosterDivisionRepository $repo */
        $repo = $container->get(RosterDivisionRepository::class);
        $this->repo = $repo;
        $this->em->getConnection()->beginTransaction();
    }

    protected function tearDown(): void
    {
        $conn = $this->em->getConnection();
        if ($conn->isTransactionActive()) {
            $conn->rollBack();
        }
        parent::tearDown();
    }

    public function testFindByOwnerAndDivisionReturnsOnlyMatching(): void
    {
        $user = $this->makeUser('rd@example.com');
        $faction = $this->makeFaction('UK');
        $division = $this->makeDivision('Rifle Platoon', $faction);
        $otherDivision = $this->makeDivision('Heavy Platoon', $faction);

        $match = $this->makeRosterDivision('Platoon A', $user, $division);
        $other = $this->makeRosterDivision('Platoon B', $user, $otherDivision);
        $this->em->persist($match);
        $this->em->persist($other);
        $this->em->flush();

        $divisionId = $division->getId();
        Assert::assertNotNull($divisionId, 'Division ID must be set after flush');
        $result = $this->repo->findByOwnerAndDivision($user, (int) $divisionId);

        Assert::assertCount(1, $result);
        Assert::assertSame('Platoon A', $result[0]->getName());
    }

    private function makeUser(string $email): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPassword('pw');
        $this->em->persist($user);
        return $user;
    }

    private function makeFaction(string $name): Faction
    {
        $faction = new Faction();
        $faction->setName($name);
        $this->em->persist($faction);
        return $faction;
    }

    private function makeDivision(string $name, Faction $faction): Division
    {
        $division = new Division();
        $division->setName($name)
            ->setFaction($faction);
        $this->em->persist($division);
        return $division;
    }

    private function makeRosterDivision(string $name, User $user, Division $division): RosterDivision
    {
        $rd = new RosterDivision();
        $rd->setName($name)
            ->setOwner($user)
            ->setDivision($division);
        return $rd;
    }

}
