<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\ArmyInstance;
use App\Entity\Faction;
use App\Entity\User;
use App\Repository\ArmyInstanceRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Assert;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ArmyInstanceRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private ArmyInstanceRepository $repo;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);
        $this->em = $em;
        /** @var ArmyInstanceRepository $repo */
        $repo = $container->get(ArmyInstanceRepository::class);
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

    public function testFindByOwnerAndFactionReturnsOnlyMatching(): void
    {
        $user = $this->makeUser('repo1@example.com');
        $factionUk = $this->makeFaction('UK');
        $factionUsa = $this->makeFaction('USA');

        $armyMatch = $this->makeArmy('Match', $user, $factionUk);
        $armyOtherFaction = $this->makeArmy('OtherFaction', $user, $factionUsa);
        $this->em->persist($armyMatch);
        $this->em->persist($armyOtherFaction);
        $this->em->flush();

        $factionId = $factionUk->getId();
        Assert::assertNotNull($factionId, 'Faction ID must be set after flush');
        $result = $this->repo->findByOwnerAndFaction($user, (int) $factionId);

        Assert::assertCount(1, $result);
        Assert::assertSame('Match', $result[0]->getName());
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

    private function makeArmy(string $name, User $user, Faction $faction): ArmyInstance
    {
        $army = new ArmyInstance();
        $army->setName($name)
            ->setOwner($user)
            ->setFaction($faction);
        return $army;
    }

}
