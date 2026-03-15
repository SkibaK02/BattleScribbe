<?php

namespace App\Service\Unit;

use App\Entity\UnitTemplate;

class UnitCostCalculator
{
    /**
     * @return array{errors: array<int, string>, total: int, payload: array<string, mixed>}
     */
    public function calculate(UnitTemplate $unitTemplate, ?array $config, array $data): array
    {
        if (!$config) {
            return ['errors' => ['This unit template cannot be configured yet.'], 'total' => 0, 'payload' => []];
        }

        $experience = $data['experience'] ?? null;
        $experienceDefinitions = $config['experience_costs'] ?? [];
        $allowedExperiences = [];
        $experienceCosts = [];
        foreach ($experienceDefinitions as $definition) {
            $allowedExperiences[] = $definition['key'];
            $experienceCosts[$definition['key']] = $definition['cost'];
        }

        if (!$experience || !in_array($experience, $allowedExperiences, true)) {
            return ['errors' => ['Please choose a valid experience level.'], 'total' => 0, 'payload' => []];
        }

        $errors = [];
        $payloadOptions = [];

        $intelligenceSelected = !empty($data['intelligence']);
        $total = $experienceCosts[$experience];
        $baseSize = $config['base_size'] ?? 0;
        $extraAllowance = $config['extra_allowance'] ?? 0;
        $maxAdditional = $extraAllowance + ($intelligenceSelected ? 5 : 0);
        $menCap = $baseSize + $maxAdditional;
        $menCount = $baseSize;

        $experienceExtras = [
            'Inexperienced' => $data['extra_inexperienced'] ?? 0,
            'Regular' => $data['extra_regular'] ?? 0,
            'Veteran' => $data['extra_veteran'] ?? 0,
        ];
        if (isset($experienceExtras[$experience])) {
            $extraRaw = max(0, (int) $experienceExtras[$experience]);
            $menCount += min($extraRaw, $maxAdditional);
        }

        $soldierCount = max(0, $menCount - 1); // NCO separate

        $officerSmgSelected = false;
        $officerPistolSelected = false;
        $smgCount = 0;

        foreach ($config['options'] as $option) {
            $name = $option['name'];

            if ($option['type'] === 'number') {
                $value = (int)($data[$name] ?? 0);
                $optionMax = $option['max'];
                if ($intelligenceSelected && str_starts_with($name, 'extra_')) {
                    $optionMax += 5;
                }
                if ($name === 'men_smg_count') {
                    $optionMax = max(0, $menCap - 1); // exclude NCO
                }
                $value = max(0, min($optionMax, $value));
                if ($name === 'men_smg_count') {
                    $value = min($value, $soldierCount + max(0, $optionMax - $soldierCount)); // clamp again
                    $smgCount = $value;
                }

                if (!empty($option['restricted_to']) && $option['restricted_to'] !== $experience && $value > 0) {
                    $errors[] = sprintf('Only %s platoons can add %s.', strtolower($option['restricted_to']), strtolower($option['label']));
                }

                if ($name === 'men_smg_count' && $value > $menCap) {
                    $errors[] = sprintf('SMG replacements cannot exceed allowed squad cap (%d).', $menCap);
                }

                $total += $value * (int)$option['cost_per'];
                $payloadOptions[$name] = $value;
                continue;
            }

            if ($option['type'] === 'checkbox') {
                $checked = isset($data[$name]);
                if ($checked) {
                    if (!empty($option['per_model'])) {
                        $total += (int)$option['cost'] * $menCount;
                    } else {
                        $total += (int)$option['cost'];
                    }
                }
                if ($name === 'officer_smg') {
                    $officerSmgSelected = $checked;
                }
                if ($name === 'officer_pistol') {
                    $officerPistolSelected = $checked;
                }
                $payloadOptions[$name] = $checked;
                continue;
            }

            if ($option['type'] === 'select') {
                $selected = $data[$name] ?? 'none';
                $payloadOptions[$name] = $selected;

                if ($selected === 'none' || $selected === '0') {
                    continue;
                }

                $choice = null;
                foreach ($option['options'] as $choiceOption) {
                    if (($choiceOption['value'] ?? '') === $selected) {
                        $choice = $choiceOption;
                        break;
                    }
                }

                if (!$choice) {
                    $errors[] = sprintf('Invalid value provided for %s.', strtolower($option['label']));
                    continue;
                }

                if (isset($choice['cost'])) {
                    $total += (int)$choice['cost'];
                }

                if (isset($choice['cost_per_model'])) {
                    $total += (int)$choice['cost_per_model'] * $menCount;
                }
            }
        }

        if ($officerSmgSelected && $officerPistolSelected) {
            $errors[] = 'Officer may take either SMG or pistol, not both.';
        }

        return [
            'errors' => $errors,
            'total' => $total,
            'payload' => $payloadOptions,
        ];
    }
}
