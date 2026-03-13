<?php

declare(strict_types=1);

namespace App\Tests\Service\Unit;

use App\Entity\UnitTemplate;
use App\Service\Unit\UnitCostCalculator;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

final class UnitCostCalculatorTest extends TestCase
{
    private UnitCostCalculator $calculator;
    private UnitTemplate $template;

    protected function setUp(): void
    {
        $this->calculator = new UnitCostCalculator();
        $this->template = (new UnitTemplate())
            ->setName('Test Squad')
            ->setType('Infantry')
            ->setBaseCost(0);
    }

    public function testValidRegularSquadCostsAreCalculated(): void
    {
        $config = $this->makeConfig();
        $data = [
            'experience' => 'Regular',
            'extra_regular' => 2,
            'officer_smg' => 'on',
        ];

        $result = $this->calculator->calculate($this->template, $config, $data);

        Assert::assertSame([], $result['errors']);
        Assert::assertSame(54, $result['total'], 'Base 30 + 2*10 extras + 4 SMG = 54');
        Assert::assertArrayHasKey('officer_smg', $result['payload']);
    }

    public function testInvalidExperienceProducesError(): void
    {
        $config = $this->makeConfig();
        $data = ['experience' => 'Unknown'];

        $result = $this->calculator->calculate($this->template, $config, $data);

        Assert::assertNotEmpty($result['errors']);
    }

    /**
     * @return array<string, mixed>
     */
    private function makeConfig(): array
    {
        return [
            'experience_costs' => [
                ['key' => 'Regular', 'cost' => 30],
            ],
            'base_size' => 5,
            'extra_allowance' => 5,
            'options' => [
                [
                    'type' => 'number',
                    'label' => 'Add men',
                    'name' => 'extra_regular',
                    'max' => 5,
                    'cost_per' => 10,
                    'restricted_to' => 'Regular',
                ],
                [
                    'type' => 'checkbox',
                    'label' => 'Officer SMG',
                    'name' => 'officer_smg',
                    'cost' => 4,
                ],
            ],
        ];
    }
}
