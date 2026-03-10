<?php

namespace App\Service\Unit;

use App\Entity\Division;
use App\Entity\UnitBuild;
use App\Service\Unit\UnitConfigProvider;
use App\Entity\Faction;
use Dompdf\Dompdf;
use Dompdf\Options;

class UnitPdfExporter
{
    public function __construct(
        private readonly UnitConfigProvider $configProvider
    ) {
    }

    /**
     * @param array<int, array{build: UnitBuild, roster: string}> $rows
     */
    public function renderDivisionBuilds(Division $division, array $rows, int $totalPoints, int $unitCount): string
    {
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->setIsRemoteEnabled(true);

        $dompdf = new Dompdf($options);
        $html = $this->buildHtml($division, $rows, $totalPoints, $unitCount);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    /**
     * @param array<string, array<int, array{build: UnitBuild, roster: string}>> $divisionData
     */
    public function renderFactionBuilds(Faction $faction, array $divisionData, int $totalPoints, int $unitCount): string
    {
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->setIsRemoteEnabled(true);

        $dompdf = new Dompdf($options);
        $html = $this->buildFactionHtml($faction, $divisionData, $totalPoints, $unitCount);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    /**
     * @param array<int, array{build: UnitBuild, roster: string}> $rowsData
     */
    private function buildHtml(Division $division, array $rowsData, int $totalPoints, int $unitCount): string
    {
        $rows = '';
        $usedOptions = [];
        // group by roster
        $byRoster = [];
        foreach ($rowsData as $item) {
            $rosterName = $item['roster'] ?? 'Roster';
            $byRoster[$rosterName][] = $item;
        }
        foreach ($byRoster as $rosterName => $items) {
            $rosterTotal = 0;
            foreach ($items as $it) {
                $rosterTotal += $it['build']->getTotalCost();
            }
            $rows .= sprintf('<tr><td colspan="4" style="background:#eef2ff;font-weight:bold;">Roster: %s (Total %d pts)</td></tr>', $this->e($rosterName), $rosterTotal);
            foreach ($items as $item) {
                /** @var UnitBuild $build */
                $build = $item['build'];
                $usedOptions = array_unique(array_merge($usedOptions, $this->collectUsedOptions($build)));
                $optionsText = $this->formatOptions($build);
                $rows .= sprintf(
                    '<tr><td>%s</td><td>%s</td><td>%d pts</td><td>%s</td><td>%s</td></tr>',
                    $this->e($build->getUnitTemplate()->getName()),
                    $this->e($build->getExperience()),
                    $build->getTotalCost(),
                    $optionsText,
                    $this->e($rosterName)
                );
            }
        }

        if (!$rows) {
            $rows = '<tr><td colspan="4" style="text-align:center;color:#555;">No units added.</td></tr>';
        }

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; margin: 20px; }
        h1 { margin-bottom: 4px; }
        h2 { margin-top: 0; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ddd; padding: 8px; font-size: 12px; }
        th { background: #f2f2f2; text-align: left; }
        .meta { margin: 6px 0 0; color: #444; font-size: 12px; }
    </style>
</head>
<body>
    <h1>Army Export</h1>
    <h2>Army: {$this->e($division->getFaction()?->getName() ?? '')}</h2>
    <h3>Division: {$this->e($division->getName())}</h3>
    <p class="meta">Units: {$unitCount} &nbsp; | &nbsp; Total points: {$totalPoints}</p>
    <table>
        <thead>
            <tr>
                <th>Unit</th>
                <th>Experience</th>
                <th>Cost</th>
                <th>Options</th>
            </tr>
        </thead>
        <tbody>
            {$rows}
        </tbody>
    </table>
    {$this->factionRulesHtml($division->getFaction())}
    {$this->weaponReferenceHtml()}
    {$this->optionReferenceHtml($usedOptions)}
</body>
</html>
HTML;
    }

    private function formatOptions(UnitBuild $build): string
    {
        $config = $build->getConfiguration();
        $options = $config['options'] ?? [];
        $template = $build->getUnitTemplate();
        $templateConfig = $this->configProvider->getConfig($template) ?? [];

        $baseSize = $templateConfig['base_size'] ?? 0;
        $extraAllowance = $templateConfig['extra_allowance'] ?? 0;
        $menCount = $baseSize
            + (int)($options['extra_inexperienced'] ?? 0)
            + (int)($options['extra_regular'] ?? 0)
            + (int)($options['extra_veteran'] ?? 0);

        $parts = [];

        // Composition details with NCO always present (if base > 0)
        $experienceLabel = ucfirst(strtolower($build->getExperience()));
        $ncoWeapon = !empty($options['nco_smg']) ? 'SMG' : 'Rifle';
        if ($menCount > 0) {
            $soldiers = max(0, $menCount - 1);
            $parts[] = sprintf('1x NCO %s', $ncoWeapon);
            if ($soldiers > 0) {
                // weapon breakdown
                $smgCount = min((int)($options['men_smg_count'] ?? 0), $soldiers);
                $lmgCount = (int)($options['lmg_count'] ?? 0);
                // ensure lmg doesn't exceed remaining soldiers
                $lmgCount = min($lmgCount, max(0, $soldiers - $smgCount));
                $rifleCount = max(0, $soldiers - $smgCount - $lmgCount);
                if ($rifleCount > 0) {
                    $parts[] = sprintf('%dx %s Rifle', $rifleCount, $experienceLabel);
                }
                if ($smgCount > 0) {
                    $parts[] = sprintf('%dx %s SMG', $smgCount, $experienceLabel);
                }
                if ($lmgCount > 0) {
                    $parts[] = sprintf('%dx %s LMG (requires loader)', $lmgCount, $experienceLabel);
                }
            }
        }

        // Officer sidearm/SMG
        if (!empty($options['officer_smg'])) {
            $parts[] = 'Officer with SMG';
        }
        if (!empty($options['officer_pistol'])) {
            $parts[] = 'Officer with pistol';
        }

        // Special trainings
        if (!empty($options['sas_training'])) {
            $parts[] = 'SAS training';
        }

        // Medic special text
        if ($template->getName() === 'Field Medic') {
            $parts[] = 'Healing aura 12"';
        }

        // Mortar/machine-gun variants (heavy platoon)
        if (!empty($options['mortar_type'])) {
            $parts[] = 'Mortar: ' . $this->e(ucfirst($options['mortar_type']));
        }
        if (!empty($options['mg_variant'])) {
            $parts[] = 'Machine-gun: ' . $this->e(strtoupper($options['mg_variant']));
        }

        // Vehicle options
        if (!empty($options['add_lkm'])) {
            $parts[] = 'LKM';
        }
        if (!empty($options['pintle_hmg'])) {
            $parts[] = 'Pintle HMG';
        }
        if (!empty($options['smoke'])) {
            $parts[] = 'Smoke dischargers';
        }
        if (!empty($options['improved_armour'])) {
            $parts[] = 'Improved armour';
        }

        return $parts ? implode('<br>', array_map([$this, 'e'], $parts)) : '—';
    }

    /**
     * @param array<string, array<string, array<int, UnitBuild>>> $divisionData
     */
    private function buildFactionHtml(Faction $faction, array $divisionData, int $totalPoints, int $unitCount): string
    {
        $rows = '';
        $usedOptions = [];
        foreach ($divisionData as $divisionName => $rowsData) {
            $rows .= sprintf('<tr><td colspan="4" style="background:#f2f2f2;font-weight:bold;">%s</td></tr>', $this->e($divisionName));
            // group by roster within division
            $byRoster = [];
            foreach ($rowsData as $item) {
                $rosterName = $item['roster'] ?? 'Roster';
                $byRoster[$rosterName][] = $item;
            }
            foreach ($byRoster as $rosterName => $items) {
                $rosterTotal = 0;
                foreach ($items as $it) {
                    $rosterTotal += $it['build']->getTotalCost();
                }
                $rows .= sprintf('<tr><td colspan="4" style="background:#eef2ff;font-weight:bold;">Roster: %s (Total %d pts)</td></tr>', $this->e($rosterName), $rosterTotal);
                foreach ($items as $item) {
                    /** @var UnitBuild $build */
                    $build = $item['build'];
                    $usedOptions = array_unique(array_merge($usedOptions, $this->collectUsedOptions($build)));
                    $rows .= sprintf(
                        '<tr><td>%s</td><td>%s</td><td>%d pts</td><td>%s</td></tr>',
                        $this->e($build->getUnitTemplate()->getName()),
                        $this->e($build->getExperience()),
                        $build->getTotalCost(),
                        $this->formatOptions($build)
                    );
                }
            }
        }

        if (!$rows) {
            $rows = '<tr><td colspan="4" style="text-align:center;color:#555;">No units added.</td></tr>';
        }

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; margin: 20px; }
        h1 { margin-bottom: 4px; }
        h2 { margin-top: 0; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ddd; padding: 8px; font-size: 12px; }
        th { background: #f2f2f2; text-align: left; }
        .meta { margin: 6px 0 0; color: #444; font-size: 12px; }
    </style>
</head>
<body>
    <h1>Roster Export</h1>
    <h2>Faction: {$this->e($faction->getName())}</h2>
    <p class="meta">Units: {$unitCount} &nbsp; | &nbsp; Total points: {$totalPoints}</p>
    <table>
        <thead>
            <tr>
                <th>Unit</th>
                <th>Experience</th>
                <th>Cost</th>
                <th>Options</th>
            </tr>
        </thead>
        <tbody>
            {$rows}
        </tbody>
    </table>
    {$this->factionRulesHtml($faction)}
    {$this->weaponReferenceHtml()}
    {$this->optionReferenceHtml($usedOptions)}
</body>
</html>
HTML;
    }

    private function factionRulesHtml(Faction $faction): string
    {
        $name = strtolower($faction->getName() ?? '');
        if (str_contains($name, 'brit') || str_contains($name, 'uk')) {
            return <<<HTML
    <h3>British Army Special Rules</h3>
    <ul>
        <li><strong>Fix Bayonets!</strong> Extra attack die per 3 models in close quarters.</li>
        <li><strong>Artillery Support.</strong> Roll two dice on artillery/smoke barrage, pick the highest.</li>
        <li><strong>Come on, Lads!</strong> Commander morale bonus +1 (unit’s cover save -1).</li>
    </ul>
HTML;
        }
        if (str_contains($name, 'usa') || str_contains($name, 'us ')) {
            return <<<HTML
    <h3>US Army Special Rules</h3>
    <ul>
        <li><strong>Fire and Maneuver.</strong> Re-roll one die on Advance order shooting.</li>
        <li><strong>Air/Artillery Liaison.</strong> Forward observers gain +1 to call in support.</li>
    </ul>
HTML;
        }
        if (str_contains($name, 'zsr') || str_contains($name, 'ussr') || str_contains($name, 'soviet')) {
            return <<<HTML
    <h3>Soviet Army Special Rules</h3>
    <ul>
        <li><strong>Quantity Has a Quality.</strong> Inexperienced infantry +1 to morale if within 6" of officer.</li>
        <li><strong>Not One Step Back.</strong> Re-roll failed order test once per game for one unit.</li>
    </ul>
HTML;
        }
        if (str_contains($name, 'jpn') || str_contains($name, 'japan') || str_contains($name, 'ija')) {
            return <<<HTML
    <h3>Japanese Army Special Rules</h3>
    <ul>
        <li><strong>Banzai!</strong> If shot at and not destroyed, may test to charge the attacker.</li>
        <li><strong>Fanatic.</strong> Infantry treat failed morale of 1-2 as passed when assaulting.</li>
    </ul>
HTML;
        }
        return '';
    }

    private function weaponReferenceHtml(): string
    {
        return <<<HTML
    <h3>Weapon Reference</h3>
    <table style="width:100%; border-collapse: collapse; margin-top:6px;">
        <thead>
            <tr>
                <th style="border:1px solid #ddd;padding:6px;background:#f7f7f7;">Weapon</th>
                <th style="border:1px solid #ddd;padding:6px;background:#f7f7f7;">Range</th>
                <th style="border:1px solid #ddd;padding:6px;background:#f7f7f7;">Traits</th>
            </tr>
        </thead>
        <tbody>
            <tr><td style="border:1px solid #ddd;padding:6px;">Pistol</td><td style="border:1px solid #ddd;padding:6px;">12"</td><td style="border:1px solid #ddd;padding:6px;">—</td></tr>
            <tr><td style="border:1px solid #ddd;padding:6px;">SMG</td><td style="border:1px solid #ddd;padding:6px;">12"</td><td style="border:1px solid #ddd;padding:6px;">Assault</td></tr>
            <tr><td style="border:1px solid #ddd;padding:6px;">Shotgun</td><td style="border:1px solid #ddd;padding:6px;">12"</td><td style="border:1px solid #ddd;padding:6px;">+1 to hit at point blank</td></tr>
            <tr><td style="border:1px solid #ddd;padding:6px;">Rifle</td><td style="border:1px solid #ddd;padding:6px;">28"</td><td style="border:1px solid #ddd;padding:6px;">—</td></tr>
            <tr><td style="border:1px solid #ddd;padding:6px;">Assault Rifle</td><td style="border:1px solid #ddd;padding:6px;">24"</td><td style="border:1px solid #ddd;padding:6px;">Assault</td></tr>
            <tr><td style="border:1px solid #ddd;padding:6px;">LMG</td><td style="border:1px solid #ddd;padding:6px;">36"</td><td style="border:1px solid #ddd;padding:6px;">Team/Loader</td></tr>
            <tr><td style="border:1px solid #ddd;padding:6px;">MMG</td><td style="border:1px solid #ddd;padding:6px;">36"</td><td style="border:1px solid #ddd;padding:6px;">Fixed, Team</td></tr>
            <tr><td style="border:1px solid #ddd;padding:6px;">HMG</td><td style="border:1px solid #ddd;padding:6px;">36"</td><td style="border:1px solid #ddd;padding:6px;">Fixed, Team, +1 pen</td></tr>
            <tr><td style="border:1px solid #ddd;padding:6px;">Sniper</td><td style="border:1px solid #ddd;padding:6px;">36"</td><td style="border:1px solid #ddd;padding:6px;">Ignore cover penalty, pick model</td></tr>
            <tr><td style="border:1px solid #ddd;padding:6px;">AT Rifle</td><td style="border:1px solid #ddd;padding:6px;">24"</td><td style="border:1px solid #ddd;padding:6px;">+2 pen</td></tr>
            <tr><td style="border:1px solid #ddd;padding:6px;">Bazooka/PIAT</td><td style="border:1px solid #ddd;padding:6px;">24"</td><td style="border:1px solid #ddd;padding:6px;">HEAT</td></tr>
            <tr><td style="border:1px solid #ddd;padding:6px;">Panzerfaust</td><td style="border:1px solid #ddd;padding:6px;">12"</td><td style="border:1px solid #ddd;padding:6px;">One-shot HEAT</td></tr>
            <tr><td style="border:1px solid #ddd;padding:6px;">Light Mortar</td><td style="border:1px solid #ddd;padding:6px;">12-60"</td><td style="border:1px solid #ddd;padding:6px;">Indirect, Smoke</td></tr>
            <tr><td style="border:1px solid #ddd;padding:6px;">Medium Mortar</td><td style="border:1px solid #ddd;padding:6px;">12-72"</td><td style="border:1px solid #ddd;padding:6px;">Indirect, Smoke</td></tr>
            <tr><td style="border:1px solid #ddd;padding:6px;">Heavy Mortar</td><td style="border:1px solid #ddd;padding:6px;">12-84"</td><td style="border:1px solid #ddd;padding:6px;">Indirect, Smoke</td></tr>
        </tbody>
    </table>
HTML;
    }

    private function optionReferenceHtml(array $used): string
    {
        if (!$used) {
            return '';
        }
        $map = [
            'officer_smg' => ['Officer SMG', 'Officer replaces rifle with SMG (12", Assault).'],
            'officer_pistol' => ['Officer pistol', 'Officer takes pistol (12").'],
            'sas_training' => ['SAS training', '+1 die in close quarters.'],
            'healing_aura' => ['Healing aura', '12" save on 6; medic cannot fight except self-defence.'],
            'mortar_type' => ['Mortar types', 'Light 12-60", Medium 12-72", Heavy 12-84" (Indirect, Smoke).'],
            'mg_variant' => ['MMG/HMG', 'MMG 36", Fixed/Team; HMG 36", Fixed/Team, +1 pen.'],
            'add_lkm' => ['LKM', 'Adds hull/coaxial MMG (36", Team).'],
            'pintle_hmg' => ['Pintle HMG', '36", Team; exposes the gunner.'],
            'smoke' => ['Smoke dischargers', 'Once per game place smoke template for LOS blocking.'],
            'improved_armour' => ['Improved armour', '+1 to vehicle damage resistance.'],
            'nco_smg' => ['NCO SMG', 'NCO replaces rifle with SMG.'],
            'men_smg_count' => ['SMG replacements', 'Men replace rifles with SMG.'],
            'lmg_count' => ['LMG count', 'Add LMG (loader required).'],
        ];

        $rows = '';
        foreach ($used as $key) {
            if (!isset($map[$key])) {
                continue;
            }
            [$label, $desc] = $map[$key];
            $rows .= sprintf(
                '<tr><td style="border:1px solid #ddd;padding:6px;">%s</td><td style="border:1px solid #ddd;padding:6px;">%s</td></tr>',
                $this->e($label),
                $this->e($desc)
            );
        }

        if ($rows === '') {
            return '';
        }

        return <<<HTML
    <h3>Special Rules & Options</h3>
    <table style="width:100%; border-collapse: collapse; margin-top:6px;">
        <thead>
            <tr>
                <th style="border:1px solid #ddd;padding:6px;background:#f7f7f7;">Option</th>
                <th style="border:1px solid #ddd;padding:6px;background:#f7f7f7;">Effect</th>
            </tr>
        </thead>
        <tbody>
            {$rows}
        </tbody>
    </table>
HTML;
    }

    /**
     * @return string[]
     */
    private function collectUsedOptions(\App\Entity\UnitBuild $build): array
    {
        $config = $build->getConfiguration();
        $options = $config['options'] ?? [];
        $used = [];
        foreach ($options as $key => $value) {
            if (is_bool($value) && $value) {
                $used[] = $key;
                continue;
            }
            if (is_numeric($value) && (int)$value > 0) {
                $used[] = $key;
                continue;
            }
            if (is_string($value) && $value !== '' && $value !== 'none' && $value !== '0') {
                $used[] = $key;
                continue;
            }
        }
        return $used;
    }

    private function e(?string $text): string
    {
        return htmlspecialchars($text ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function isBritish(Division $division): bool
    {
        $faction = $division->getFaction();
        if (!$faction) {
            return false;
        }
        return stripos($faction->getName(), 'brit') !== false || stripos($faction->getName(), 'uk') !== false;
    }

    private function isBritishFaction(Faction $faction): bool
    {
        return stripos($faction->getName(), 'brit') !== false || stripos($faction->getName(), 'uk') !== false;
    }
}
