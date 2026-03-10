<?php

namespace App\Service\Unit;

use App\Entity\UnitTemplate;
use App\Entity\Faction;

class UnitConfigProvider
{
    public function getConfig(UnitTemplate $unitTemplate): ?array
    {
        $name = $unitTemplate->getName();
        $divisionName = strtolower(trim($unitTemplate->getDivision()?->getName() ?? ''));
        if (!$this->isTemplateAllowedForFaction($unitTemplate, $unitTemplate->getDivision()?->getFaction())) {
            return null;
        }

        if ($divisionName === 'heavy platoon' || str_contains($divisionName, 'heavy')) {
            $lower = strtolower($name);
            if (str_contains($lower, 'commander')) {
                return $this->heavyPlatoonCommanderConfig($unitTemplate);
            }
            if (str_contains($lower, 'mortar')) {
                return $this->heavyMortarConfig($unitTemplate);
            }
            if (
                str_contains($lower, 'lkm')
                || str_contains($lower, 'lmg')
                || str_contains($lower, 'mmg')
                || str_contains($lower, 'hmg')
                || str_contains($lower, 'machine')
            ) {
                return $this->heavyLkmConfig($unitTemplate);
            }
        }

        // Armoured platoon: faction-specific vehicle variants
        $transportMap = [
            'universal carrier' => ['label' => 'Universal Carrier', 'capacity' => 8],
            'm3 half-track' => ['label' => 'M3 Half-track', 'capacity' => 8],
            'm5 half-track' => ['label' => 'M5 Half-track', 'capacity' => 8],
            'type 1 ho-ha' => ['label' => 'Type 1 Ho-Ha', 'capacity' => 8],
            'sdkfz 251/1 half-track' => ['label' => 'SdKfz 251/1 Half-track', 'capacity' => 10],
        ];

        $tankMap = [
            'stuart light tank' => ['label' => 'Stuart Light Tank', 'class' => 'Light'],
            'm3 stuart light tank' => ['label' => 'M3 Stuart Light Tank', 'class' => 'Light'],
            't-70 light tank' => ['label' => 'T-70 Light Tank', 'class' => 'Light'],
            'type 95 ha-go light tank' => ['label' => 'Type 95 Ha-Go Light Tank', 'class' => 'Light'],
            'panzer ii light tank' => ['label' => 'Panzer II Light Tank', 'class' => 'Light'],
            'cromwell medium tank' => ['label' => 'Cromwell Medium Tank', 'class' => 'Medium'],
            'm4 sherman medium tank' => ['label' => 'M4 Sherman Medium Tank', 'class' => 'Medium'],
            't-34/85 medium tank' => ['label' => 'T-34/85 Medium Tank', 'class' => 'Medium'],
            'type 97 chi-ha medium tank' => ['label' => 'Type 97 Chi-Ha Medium Tank', 'class' => 'Medium'],
            'panzer iv medium tank' => ['label' => 'Panzer IV Medium Tank', 'class' => 'Medium'],
            'churchill heavy tank' => ['label' => 'Churchill Heavy Tank', 'class' => 'Heavy'],
            'm26 pershing heavy tank' => ['label' => 'M26 Pershing Heavy Tank', 'class' => 'Heavy'],
            'is-2 heavy tank' => ['label' => 'IS-2 Heavy Tank', 'class' => 'Heavy'],
            'type 4 chi-to heavy tank' => ['label' => 'Type 4 Chi-To Heavy Tank', 'class' => 'Heavy'],
            'tiger i heavy tank' => ['label' => 'Tiger I Heavy Tank', 'class' => 'Heavy'],
        ];

        $nameKey = strtolower($name);
        if (isset($transportMap[$nameKey])) {
            return $this->armouredTransportConfig($unitTemplate, $transportMap[$nameKey]);
        }
        if (isset($tankMap[$nameKey])) {
            return $this->armouredTankConfig($unitTemplate, $tankMap[$nameKey]);
        }

        return match ($name) {
            'Platoon Commander' => $this->platoonCommanderConfig($unitTemplate),
            'Rifle Section' => $this->rifleSectionConfig($unitTemplate),
            'Field Medic' => $this->fieldMedicConfig($unitTemplate),
            'Commando Subsection' => $this->commandoConfig($unitTemplate),
            'Marines Squad' => $this->marinesConfig($unitTemplate),
            'Veteran Squad' => $this->sovietVeteranConfig($unitTemplate),
            'IJA Infantry Squad' => $this->ijaInfantryConfig($unitTemplate),
            'Panzergrenadier Squad' => $this->panzerGrenadierConfig($unitTemplate),
            default => null,
        };
    }

    public function isTemplateAllowedForFaction(UnitTemplate $unitTemplate, ?Faction $faction): bool
    {
        $name = strtolower($unitTemplate->getName());
        $factionName = strtolower($faction?->getName() ?? '');

        $map = [
            'commando subsection' => ['uk', 'brit', 'british'],
            'marines squad' => ['usa', 'us', 'united states'],
            'veteran squad' => ['zsr', 'zssr', 'ussr', 'soviet', 'red army', 'zrr'],
            'ija infantry squad' => ['japonia', 'japan', 'ija', 'imperial', 'jpn'],
            'panzergrenadier squad' => ['germ', 'deutsch', 'wehr', 'axis', 'germany'],
        ];

        if (!isset($map[$name])) {
            return true; // common unit
        }

        foreach ($map[$name] as $needle) {
            if ($needle !== '' && str_contains($factionName, $needle)) {
                return true;
            }
        }
        return false;
    }

    private function platoonCommanderConfig(UnitTemplate $unitTemplate): array
    {
        return [
            'composition' => '1 Officer',
            'base_size' => 0, // only officer, men added via extras
            'extra_allowance' => 5,
            'weapons' => ['Rifle'],
            'special_rules' => [],
            'experience_costs' => [
                ['key' => 'Inexperienced', 'label' => 'Inexperienced', 'cost' => 21],
                ['key' => 'Regular', 'label' => 'Regular', 'cost' => 30],
                ['key' => 'Veteran', 'label' => 'Veteran', 'cost' => 39],
            ],
            'options' => [
                [
                    'type' => 'number',
                    'label' => 'Add up to 5 men with rifles at +7pts each (Inexperienced)',
                    'name' => 'extra_inexperienced',
                    'max' => 5,
                    'cost_per' => 7,
                    'restricted_to' => 'Inexperienced',
                ],
                [
                    'type' => 'number',
                    'label' => 'Add up to 5 men with rifles at +10pts each (Regular)',
                    'name' => 'extra_regular',
                    'max' => 5,
                    'cost_per' => 10,
                    'restricted_to' => 'Regular',
                ],
                [
                    'type' => 'number',
                    'label' => 'Add up to 5 men with rifles at +13pts each (Veteran)',
                    'name' => 'extra_veteran',
                    'max' => 5,
                    'cost_per' => 13,
                    'restricted_to' => 'Veteran',
                ],
                [
                    'type' => 'checkbox',
                    'label' => 'Officer replaces rifle with SMG (+4pts)',
                    'name' => 'officer_smg',
                    'cost' => 4,
                ],
                [
                    'type' => 'number',
                    'label' => 'Men replace rifles with SMG (+4pts each)',
                    'name' => 'men_smg_count',
                    'max' => 10,
                    'cost_per' => 4,
                ],
                [
                    'type' => 'checkbox',
                    'label' => 'Officer may take pistol (-1pt)',
                    'name' => 'officer_pistol',
                    'cost' => -1,
                ],
                [
                    'type' => 'checkbox',
                    'label' => 'Intelligence training (+50pts, unlocks up to 5 additional men)',
                    'name' => 'intelligence',
                    'cost' => 50,
                ],
            ],
        ];
    }

    private function marinesConfig(UnitTemplate $unitTemplate): array
    {
        return [
            'composition' => '5-10 Marines',
            'base_size' => 5,
            'extra_allowance' => 5,
            'weapons' => ['Rifle', 'SMG', 'LMG'],
            'special_rules' => [],
            'experience_costs' => [
                ['key' => 'Regular', 'label' => 'Regular', 'cost' => $unitTemplate->getBaseCost()],
                ['key' => 'Veteran', 'label' => 'Veteran', 'cost' => $unitTemplate->getBaseCost() + 10],
            ],
            'options' => [
                [
                    'type' => 'number',
                    'label' => 'Add up to 5 men (+10pts each, Regular)',
                    'name' => 'extra_regular',
                    'max' => 5,
                    'cost_per' => 10,
                    'restricted_to' => 'Regular',
                ],
                [
                    'type' => 'number',
                    'label' => 'Add up to 5 men (+13pts each, Veteran)',
                    'name' => 'extra_veteran',
                    'max' => 5,
                    'cost_per' => 13,
                    'restricted_to' => 'Veteran',
                ],
                [
                    'type' => 'checkbox',
                    'label' => 'NCO replaces rifle with SMG (+4pts)',
                    'name' => 'nco_smg',
                    'cost' => 4,
                ],
                [
                    'type' => 'number',
                    'label' => 'Men replace rifles with SMG (+4pts each, up to 9)',
                    'name' => 'men_smg_count',
                    'max' => 9,
                    'cost_per' => 4,
                ],
                [
                    'type' => 'number',
                    'label' => 'Add up to 2 LMG (15pts each, needs loader)',
                    'name' => 'lmg_count',
                    'max' => 2,
                    'cost_per' => 15,
                ],
            ],
        ];
    }

    private function sovietVeteranConfig(UnitTemplate $unitTemplate): array
    {
        return [
            'composition' => '5-10 veterans',
            'base_size' => 5,
            'extra_allowance' => 5,
            'weapons' => ['Rifle', 'SMG', 'LMG'],
            'special_rules' => ['Veteran assault element'],
            'experience_costs' => [
                ['key' => 'Veteran', 'label' => 'Veteran', 'cost' => $unitTemplate->getBaseCost()],
            ],
            'options' => [
                [
                    'type' => 'number',
                    'label' => 'Add up to 5 men (+13pts each, Veteran)',
                    'name' => 'extra_veteran',
                    'max' => 5,
                    'cost_per' => 13,
                    'restricted_to' => 'Veteran',
                ],
                [
                    'type' => 'checkbox',
                    'label' => 'NCO replaces rifle with SMG (+4pts)',
                    'name' => 'nco_smg',
                    'cost' => 4,
                ],
                [
                    'type' => 'number',
                    'label' => 'Men replace rifles with SMG (+4pts each, up to 9)',
                    'name' => 'men_smg_count',
                    'max' => 9,
                    'cost_per' => 4,
                ],
                [
                    'type' => 'number',
                    'label' => 'Add up to 2 LMG (15pts each, needs loader)',
                    'name' => 'lmg_count',
                    'max' => 2,
                    'cost_per' => 15,
                ],
            ],
        ];
    }

    private function ijaInfantryConfig(UnitTemplate $unitTemplate): array
    {
        return [
            'composition' => '5-10 infantry',
            'base_size' => 5,
            'extra_allowance' => 5,
            'weapons' => ['Rifle', 'SMG', 'LMG'],
            'special_rules' => ['IJA infantry squad'],
            'experience_costs' => [
                ['key' => 'Regular', 'label' => 'Regular', 'cost' => $unitTemplate->getBaseCost()],
                ['key' => 'Veteran', 'label' => 'Veteran', 'cost' => $unitTemplate->getBaseCost() + 10],
            ],
            'options' => [
                [
                    'type' => 'number',
                    'label' => 'Add up to 5 men (+10pts each, Regular)',
                    'name' => 'extra_regular',
                    'max' => 5,
                    'cost_per' => 10,
                    'restricted_to' => 'Regular',
                ],
                [
                    'type' => 'number',
                    'label' => 'Add up to 5 men (+13pts each, Veteran)',
                    'name' => 'extra_veteran',
                    'max' => 5,
                    'cost_per' => 13,
                    'restricted_to' => 'Veteran',
                ],
                [
                    'type' => 'checkbox',
                    'label' => 'NCO replaces rifle with SMG (+4pts)',
                    'name' => 'nco_smg',
                    'cost' => 4,
                ],
                [
                    'type' => 'number',
                    'label' => 'Men replace rifles with SMG (+4pts each, up to 9)',
                    'name' => 'men_smg_count',
                    'max' => 9,
                    'cost_per' => 4,
                ],
                [
                    'type' => 'number',
                    'label' => 'Add up to 2 LMG (15pts each, needs loader)',
                    'name' => 'lmg_count',
                    'max' => 2,
                    'cost_per' => 15,
                ],
            ],
        ];
    }
    private function rifleSectionConfig(UnitTemplate $unitTemplate): array
    {
        return [
            'composition' => '5-10 soldiers with rifles/LMG',
            'base_size' => 5,
            'extra_allowance' => 5,
            'weapons' => ['Rifle', 'LMG'],
            'special_rules' => [],
            'experience_costs' => [
                ['key' => 'Inexperienced', 'label' => 'Inexperienced', 'cost' => max(0, $unitTemplate->getBaseCost() - 9)],
                ['key' => 'Regular', 'label' => 'Regular', 'cost' => $unitTemplate->getBaseCost()],
                ['key' => 'Veteran', 'label' => 'Veteran', 'cost' => $unitTemplate->getBaseCost() + 9],
            ],
            'options' => [
                [
                    'type' => 'number',
                    'label' => 'Add up to 5 men (+7pts each, Inexperienced)',
                    'name' => 'extra_inexperienced',
                    'max' => 5,
                    'cost_per' => 7,
                    'restricted_to' => 'Inexperienced',
                ],
                [
                    'type' => 'number',
                    'label' => 'Add up to 5 men (+10pts each, Regular)',
                    'name' => 'extra_regular',
                    'max' => 5,
                    'cost_per' => 10,
                    'restricted_to' => 'Regular',
                ],
                [
                    'type' => 'number',
                    'label' => 'Add up to 5 men (+13pts each, Veteran)',
                    'name' => 'extra_veteran',
                    'max' => 5,
                    'cost_per' => 13,
                    'restricted_to' => 'Veteran',
                ],
                [
                    'type' => 'checkbox',
                    'label' => 'NCO replaces rifle with SMG (+4pts)',
                    'name' => 'nco_smg',
                    'cost' => 4,
                ],
                [
                    'type' => 'number',
                    'label' => 'Men replace rifles with SMG (+4pts each, up to 5)',
                    'name' => 'men_smg_count',
                    'max' => 5,
                    'cost_per' => 4,
                ],
                [
                    'type' => 'number',
                    'label' => 'Add up to 2 LMG (15pts each, requires loader)',
                    'name' => 'lmg_count',
                    'max' => 2,
                    'cost_per' => 15,
                ],
            ],
        ];
    }

    private function fieldMedicConfig(UnitTemplate $unitTemplate): array
    {
        return [
            'composition' => '1 medic',
            'base_size' => 1,
            'extra_allowance' => 0,
            'weapons' => ['Pistol'],
            'special_rules' => [],
            'experience_costs' => [
                ['key' => 'Inexperienced', 'label' => 'Inexperienced', 'cost' => max(0, $unitTemplate->getBaseCost() - 6)],
                ['key' => 'Regular', 'label' => 'Regular', 'cost' => $unitTemplate->getBaseCost()],
                ['key' => 'Veteran', 'label' => 'Veteran', 'cost' => $unitTemplate->getBaseCost() + 6],
            ],
            'options' => [],
        ];
    }

    private function commandoConfig(UnitTemplate $unitTemplate): array
    {
        return [
            'composition' => '5-10 commandos',
            'base_size' => 5,
            'extra_allowance' => 5,
            'weapons' => ['Rifle', 'SMG'],
            'special_rules' => [],
            'experience_costs' => [
                ['key' => 'Veteran', 'label' => 'Veteran', 'cost' => $unitTemplate->getBaseCost()],
            ],
            'options' => [
                [
                    'type' => 'number',
                    'label' => 'Add up to 5 men (+13pts each, Veteran)',
                    'name' => 'extra_veteran',
                    'max' => 5,
                    'cost_per' => 13,
                    'restricted_to' => 'Veteran',
                ],
                [
                    'type' => 'checkbox',
                    'label' => 'NCO replaces rifle with SMG (+4pts)',
                    'name' => 'nco_smg',
                    'cost' => 4,
                ],
                [
                    'type' => 'number',
                    'label' => 'Men replace rifles with SMG (+4pts each, up to 9)',
                    'name' => 'men_smg_count',
                    'max' => 9,
                    'cost_per' => 4,
                ],
                [
                    'type' => 'number',
                    'label' => 'Add up to 2 LMG (15pts each, needs loader)',
                    'name' => 'lmg_count',
                    'max' => 2,
                    'cost_per' => 15,
                ],
                [
                    'type' => 'checkbox',
                    'label' => 'SAS training (+2pts per model)',
                    'name' => 'sas_training',
                    'cost' => 2,
                    'per_model' => true,
                ],
            ],
        ];
    }

    private function armouredTransportConfig(UnitTemplate $unitTemplate, array $variant = ['label' => 'Transport', 'capacity' => 8]): array
    {
        $label = $variant['label'] ?? 'Transport';
        $capacity = $variant['capacity'] ?? 8;
        return [
            'composition' => sprintf('%s (capacity %d)', $label, $capacity),
            'base_size' => 0,
            'extra_allowance' => 0,
            'weapons' => ['Transport'],
            'special_rules' => [sprintf('Carries up to %d models', $capacity)],
            'experience_costs' => [
                ['key' => 'Regular', 'label' => 'Regular', 'cost' => max(0, $unitTemplate->getBaseCost())],
                ['key' => 'Veteran', 'label' => 'Veteran', 'cost' => max(0, $unitTemplate->getBaseCost() + 10)],
            ],
            'options' => [
                [
                    'type' => 'checkbox',
                    'label' => 'Add LKM (+15pts)',
                    'name' => 'add_lkm',
                    'cost' => 15,
                ],
            ],
        ];
    }

    private function armouredTankConfig(UnitTemplate $unitTemplate, array $variant): array
    {
        $class = $variant['class'] ?? 'Tank';
        $label = $variant['label'] ?? sprintf('%s tank', $class);
        return [
            'composition' => $label,
            'base_size' => 0,
            'extra_allowance' => 0,
            'weapons' => ['Tank armament'],
            'special_rules' => [],
            'experience_costs' => [
                ['key' => 'Regular', 'label' => 'Regular', 'cost' => max(0, $unitTemplate->getBaseCost())],
                ['key' => 'Veteran', 'label' => 'Veteran', 'cost' => max(0, $unitTemplate->getBaseCost() + 10)],
            ],
            'options' => [
                [
                    'type' => 'checkbox',
                    'label' => 'Add LKM (+15pts)',
                    'name' => 'add_lkm',
                    'cost' => 15,
                ],
                [
                    'type' => 'checkbox',
                    'label' => 'Add pintle HMG (+20pts)',
                    'name' => 'pintle_hmg',
                    'cost' => 20,
                ],
                [
                    'type' => 'checkbox',
                    'label' => 'Smoke dischargers (+5pts)',
                    'name' => 'smoke',
                    'cost' => 5,
                ],
                ...($class === 'Heavy' ? [[
                    'type' => 'checkbox',
                    'label' => 'Improved armour (+25pts)',
                    'name' => 'improved_armour',
                    'cost' => 25,
                ]] : []),
            ],
        ];
    }

    private function panzerGrenadierConfig(UnitTemplate $unitTemplate): array
    {
        return [
            'composition' => '5-10 panzergrenadiers',
            'base_size' => 5,
            'extra_allowance' => 5,
            'weapons' => ['Rifle', 'SMG', 'LMG'],
            'special_rules' => [],
            'experience_costs' => [
                ['key' => 'Regular', 'label' => 'Regular', 'cost' => $unitTemplate->getBaseCost()],
                ['key' => 'Veteran', 'label' => 'Veteran', 'cost' => $unitTemplate->getBaseCost() + 10],
            ],
            'options' => [
                [
                    'type' => 'number',
                    'label' => 'Add up to 5 men (+10pts each, Regular)',
                    'name' => 'extra_regular',
                    'max' => 5,
                    'cost_per' => 10,
                    'restricted_to' => 'Regular',
                ],
                [
                    'type' => 'number',
                    'label' => 'Add up to 5 men (+13pts each, Veteran)',
                    'name' => 'extra_veteran',
                    'max' => 5,
                    'cost_per' => 13,
                    'restricted_to' => 'Veteran',
                ],
                [
                    'type' => 'checkbox',
                    'label' => 'NCO replaces rifle with SMG (+4pts)',
                    'name' => 'nco_smg',
                    'cost' => 4,
                ],
                [
                    'type' => 'number',
                    'label' => 'Men replace rifles with SMG (+4pts each, up to 9)',
                    'name' => 'men_smg_count',
                    'max' => 9,
                    'cost_per' => 4,
                ],
                [
                    'type' => 'number',
                    'label' => 'Add up to 2 LMG (15pts each, needs loader)',
                    'name' => 'lmg_count',
                    'max' => 2,
                    'cost_per' => 15,
                ],
            ],
        ];
    }

    private function heavyPlatoonCommanderConfig(UnitTemplate $unitTemplate): array
    {
        return [
            'composition' => '1 Officer + up to 2 assistants',
            'base_size' => 1,
            'extra_allowance' => 2,
            'weapons' => ['Rifle'],
            'special_rules' => [],
            'experience_costs' => [
                ['key' => 'Regular', 'label' => 'Regular', 'cost' => max(0, $unitTemplate->getBaseCost())],
                ['key' => 'Veteran', 'label' => 'Veteran', 'cost' => max(0, $unitTemplate->getBaseCost() + 10)],
            ],
            'options' => [
                [
                    'type' => 'number',
                    'label' => 'Add up to 2 men with rifles (+10pts each, Regular)',
                    'name' => 'extra_regular',
                    'max' => 2,
                    'cost_per' => 10,
                    'restricted_to' => 'Regular',
                ],
                [
                    'type' => 'number',
                    'label' => 'Add up to 2 men with rifles (+13pts each, Veteran)',
                    'name' => 'extra_veteran',
                    'max' => 2,
                    'cost_per' => 13,
                    'restricted_to' => 'Veteran',
                ],
                [
                    'type' => 'checkbox',
                    'label' => 'Officer replaces rifle with SMG (+4pts)',
                    'name' => 'officer_smg',
                    'cost' => 4,
                ],
                [
                    'type' => 'number',
                    'label' => 'Men replace rifles with SMG (+4pts each, up to 2)',
                    'name' => 'men_smg_count',
                    'max' => 2,
                    'cost_per' => 4,
                ],
                [
                    'type' => 'checkbox',
                    'label' => 'Officer may take pistol (-1pt)',
                    'name' => 'officer_pistol',
                    'cost' => -1,
                ],
            ],
        ];
    }

    private function heavyMortarConfig(UnitTemplate $unitTemplate): array
    {
        return [
            'composition' => '3 crew and mortar',
            'base_size' => max(3, $unitTemplate->getMinSize() ?? 3),
            'extra_allowance' => 0,
            'weapons' => ['Mortar'],
            'special_rules' => ['Smoke available (obscures targets)'],
            'experience_costs' => [
                ['key' => 'Regular', 'label' => 'Regular', 'cost' => max(0, $unitTemplate->getBaseCost())],
                ['key' => 'Veteran', 'label' => 'Veteran', 'cost' => max(0, $unitTemplate->getBaseCost() + 10)],
            ],
            'options' => [
                [
                    'type' => 'select',
                    'label' => 'Mortar type',
                    'name' => 'mortar_type',
                    'options' => [
                        ['label' => 'Light (default)', 'value' => 'light', 'cost' => 0],
                        ['label' => 'Medium (+20pts)', 'value' => 'medium', 'cost' => 20],
                        ['label' => 'Heavy (+40pts)', 'value' => 'heavy', 'cost' => 40],
                    ],
                ],
            ],
        ];
    }

    private function heavyLkmConfig(UnitTemplate $unitTemplate): array
    {
        return [
            'composition' => '3 crew and MMG',
            'base_size' => max(3, $unitTemplate->getMinSize() ?? 3),
            'extra_allowance' => 0,
            'weapons' => ['LMG/MMG'],
            'special_rules' => ['FIXED position'],
            'experience_costs' => [
                ['key' => 'Regular', 'label' => 'Regular', 'cost' => max(0, $unitTemplate->getBaseCost())],
                ['key' => 'Veteran', 'label' => 'Veteran', 'cost' => max(0, $unitTemplate->getBaseCost() + 10)],
            ],
            'options' => [
                [
                    'type' => 'select',
                    'label' => 'Weapon choice',
                    'name' => 'mg_variant',
                    'options' => [
                        ['label' => 'LKM/MMG (default)', 'value' => 'mmg', 'cost' => 0],
                        ['label' => 'CKM/HMG (+15pts)', 'value' => 'hmg', 'cost' => 15],
                    ],
                ],
            ],
        ];
    }

}
