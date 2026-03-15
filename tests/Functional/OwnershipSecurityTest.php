<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\ArmyInstance;
use App\Entity\Division;
use App\Entity\Faction;
use App\Entity\RosterDivision;
use App\Entity\UnitBuild;
use App\Entity\UnitTemplate;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Assert;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class OwnershipSecurityTest extends WebTestCase
{
    public function testUserCannotDeleteAnotherUsersArmy(): void
    {
        [$client, $em, $army] = $this->createScenarioWithArmy();
        $userB = $this->createAndPersistUser($em, 'owner-b-'.uniqid().'@example.com');

        $client->loginUser($userB);
        $client->request('POST', '/app/armies/'.$army->getId().'/delete', ['_token' => 'dummy']);

        Assert::assertSame(403, $client->getResponse()->getStatusCode());
        $armyId = $army->getId();
        Assert::assertNotNull($armyId);
        $this->assertEntityExists($em, ArmyInstance::class, $armyId);
    }

    public function testUserCannotDeleteAnotherUsersPlatoon(): void
    {
        [$client, $em, $rosterDivision] = $this->createScenarioWithPlatoon();
        $userB = $this->createAndPersistUser($em, 'owner-b-'.uniqid().'@example.com');

        $client->loginUser($userB);
        $client->request('POST', '/app/roster-division/'.$rosterDivision->getId().'/delete', ['_token' => 'dummy']);

        Assert::assertSame(403, $client->getResponse()->getStatusCode());
        $rosterId = $rosterDivision->getId();
        Assert::assertNotNull($rosterId);
        $this->assertEntityExists($em, RosterDivision::class, $rosterId);
    }

    public function testUserCannotDeleteAnotherUsersUnit(): void
    {
        [$client, $em, $unitBuild] = $this->createScenarioWithUnit();
        $userB = $this->createAndPersistUser($em, 'owner-b-'.uniqid().'@example.com');

        $client->loginUser($userB);
        $client->request('POST', '/app/unit-build/'.$unitBuild->getId().'/delete', ['_token' => 'dummy']);

        Assert::assertSame(403, $client->getResponse()->getStatusCode());
        $unitId = $unitBuild->getId();
        Assert::assertNotNull($unitId);
        $this->assertEntityExists($em, UnitBuild::class, $unitId);
    }

    /**
     * @return array{KernelBrowser, EntityManagerInterface, ArmyInstance}
     */
    private function createScenarioWithArmy(): array
    {
        $client = static::createClient();
        $em = $this->entityManager($client);

        $userA = $this->createAndPersistUser($em, 'owner-a-'.uniqid().'@example.com');
        /** @var Faction $faction */
        $faction = $this->createAndPersist($em, (new Faction())->setName('Test Faction'));

        $army = new ArmyInstance();
        $army->setName('User A Army')->setOwner($userA)->setFaction($faction);
        $em->persist($army);
        $em->flush();

        return [$client, $em, $army];
    }

    /**
     * @return array{KernelBrowser, EntityManagerInterface, RosterDivision}
     */
    private function createScenarioWithPlatoon(): array
    {
        $client = static::createClient();
        $em = $this->entityManager($client);

        $userA = $this->createAndPersistUser($em, 'owner-a-'.uniqid().'@example.com');
        $faction = $this->createAndPersist($em, (new Faction())->setName('Test Faction'));
        $division = $this->createAndPersist($em, (new Division())->setName('Rifle Platoon')->setFaction($faction));

        $rosterDivision = new RosterDivision();
        $rosterDivision->setName('User A Platoon')->setOwner($userA)->setDivision($division);
        $em->persist($rosterDivision);
        $em->flush();

        return [$client, $em, $rosterDivision];
    }

    /**
     * @return array{KernelBrowser, EntityManagerInterface, UnitBuild}
     */
    private function createScenarioWithUnit(): array
    {
        $client = static::createClient();
        $em = $this->entityManager($client);

        $userA = $this->createAndPersistUser($em, 'owner-a-'.uniqid().'@example.com');
        /** @var Faction $faction */
        $faction = $this->createAndPersist($em, (new Faction())->setName('Test Faction'));
        /** @var Division $division */
        $division = $this->createAndPersist($em, (new Division())->setName('Rifle Platoon')->setFaction($faction));
        /** @var UnitTemplate $unitTemplate */
        $unitTemplate = $this->createAndPersist($em, (new UnitTemplate())
            ->setName('Platoon Commander')
            ->setType('Infantry')
            ->setBaseCost(50)
            ->setDivision($division));

        $rosterDivision = new RosterDivision();
        $rosterDivision->setName('User A Platoon')->setOwner($userA)->setDivision($division);
        $em->persist($rosterDivision);
        $em->flush();

        $unitBuild = new UnitBuild();
        $unitBuild->setOwner($userA)
            ->setUnitTemplate($unitTemplate)
            ->setRosterDivision($rosterDivision)
            ->setExperience('Regular')
            ->setTotalCost(50);
        $em->persist($unitBuild);
        $em->flush();

        return [$client, $em, $unitBuild];
    }

    private function entityManager(KernelBrowser $client): EntityManagerInterface
    {
        $em = $client->getContainer()->get(EntityManagerInterface::class);
        Assert::assertInstanceOf(EntityManagerInterface::class, $em);

        return $em;
    }

    private function createAndPersistUser(EntityManagerInterface $em, string $email): User
    {
        $user = (new User())->setEmail($email)->setPassword('hashed');
        $em->persist($user);
        $em->flush();
        return $user;
    }

    /**
     * @template T of object
     * @param T $entity
     * @return T
     */
    private function createAndPersist(EntityManagerInterface $em, object $entity): object
    {
        $em->persist($entity);
        $em->flush();
        return $entity;
    }

    /**
     * @param class-string $entityClass
     */
    private function assertEntityExists(EntityManagerInterface $em, string $entityClass, int $id): void
    {
        $em->clear();
        $entity = $em->find($entityClass, $id);
        Assert::assertNotNull($entity, sprintf('%s should still exist after forbidden delete attempt', $entityClass));
    }
}
