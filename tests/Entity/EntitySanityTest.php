<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\ArmyInstance;
use App\Entity\Division;
use App\Entity\Faction;
use App\Entity\RosterDivision;
use App\Entity\UnitBuild;
use App\Entity\UnitTemplate;
use App\Entity\User;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

final class EntitySanityTest extends TestCase
{
    public function testUserEmailAndRoles(): void
    {
        $user = (new User())
            ->setEmail('john@example.com')
            ->setPassword('secret')
            ->setRoles(['ROLE_ADMIN']);

        Assert::assertSame('john@example.com', $user->getEmail());
        Assert::assertContains('ROLE_USER', $user->getRoles(), 'Default ROLE_USER should be present');
        Assert::assertContains('ROLE_ADMIN', $user->getRoles());
    }

    public function testFactionAndDivisionRelation(): void
    {
        $faction = (new Faction())->setName('UK');
        $division = (new Division())
            ->setName('Rifle Platoon')
            ->setFaction($faction);

        Assert::assertSame('Rifle Platoon', $division->getName());
        Assert::assertSame($faction, $division->getFaction());
    }

    public function testArmyInstanceHoldsOwnerAndFaction(): void
    {
        $user = (new User())->setEmail('a@example.com')->setPassword('pw');
        $faction = (new Faction())->setName('USA');

        $army = (new ArmyInstance())
            ->setName('Army #1')
            ->setOwner($user)
            ->setFaction($faction);

        Assert::assertSame('Army #1', $army->getName());
        Assert::assertSame($user, $army->getOwner());
        Assert::assertSame($faction, $army->getFaction());
    }

    public function testRosterDivisionLinksDivisionAndOwner(): void
    {
        $user = (new User())->setEmail('b@example.com')->setPassword('pw');
        $division = (new Division())->setName('Heavy Platoon')->setFaction((new Faction())->setName('USSR'));

        $rd = (new RosterDivision())
            ->setName('Platoon #1')
            ->setOwner($user)
            ->setDivision($division);

        Assert::assertSame('Platoon #1', $rd->getName());
        Assert::assertSame($user, $rd->getOwner());
        Assert::assertSame($division, $rd->getDivision());
    }

    public function testUnitTemplateAndUnitBuild(): void
    {
        $division = (new Division())->setName('Rifle Platoon')->setFaction((new Faction())->setName('UK'));
        $template = (new UnitTemplate())
            ->setName('Platoon Commander')
            ->setType('Infantry')
            ->setBaseCost(30)
            ->setDivision($division);

        $user = (new User())->setEmail('c@example.com')->setPassword('pw');
        $build = (new UnitBuild())
            ->setOwner($user)
            ->setUnitTemplate($template)
            ->setExperience('Regular')
            ->setConfiguration(['experience' => 'Regular', 'options' => []])
            ->setTotalCost(30);

        Assert::assertSame('Platoon Commander', $template->getName());
        Assert::assertSame('Regular', $build->getExperience());
        Assert::assertSame(30, $build->getTotalCost());
        Assert::assertSame($template, $build->getUnitTemplate());
        Assert::assertSame($user, $build->getOwner());
    }
}
