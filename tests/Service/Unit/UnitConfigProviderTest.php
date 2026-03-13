<?php

declare(strict_types=1);

namespace App\Tests\Service\Unit;

use App\Entity\Division;
use App\Entity\Faction;
use App\Entity\UnitTemplate;
use App\Service\Unit\UnitConfigProvider;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

final class UnitConfigProviderTest extends TestCase
{
    private UnitConfigProvider $provider;

    protected function setUp(): void
    {
        $this->provider = new UnitConfigProvider();
    }

    public function testVeteranSquadAllowedOnlyInRiflePlatoon(): void
    {
        $rifleTemplate = $this->makeTemplate('Veteran Squad', 'Rifle Platoon', 'Soviet Union');
        Assert::assertTrue($this->provider->isTemplateAllowedForDivision($rifleTemplate));

        $heavyTemplate = $this->makeTemplate('Veteran Squad', 'Heavy Platoon', 'Soviet Union');
        Assert::assertFalse($this->provider->isTemplateAllowedForDivision($heavyTemplate));
    }

    public function testGetConfigReturnsDefinitionForPlatoonCommander(): void
    {
        $template = $this->makeTemplate('Platoon Commander', 'Rifle Platoon', 'United Kingdom');
        $config = $this->provider->getConfig($template);

        Assert::assertNotNull($config, 'Config should be returned for allowed unit.');
        Assert::assertArrayHasKey('experience_costs', $config);
        Assert::assertArrayHasKey('options', $config);
        Assert::assertNotEmpty($config['experience_costs']);
    }

    private function makeTemplate(string $name, string $divisionName, string $factionName): UnitTemplate
    {
        $faction = (new Faction())->setName($factionName);
        $division = (new Division())
            ->setName($divisionName)
            ->setFaction($faction);

        $template = new UnitTemplate();
        $template->setName($name);
        $template->setType('Infantry');
        $template->setBaseCost(10);
        $template->setDivision($division);

        return $template;
    }
}
