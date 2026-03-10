<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260307134000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed Germany rifle and heavy platoon unit templates';
    }

    public function up(Schema $schema): void
    {
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        $factionId = $this->connection->fetchOne('SELECT id FROM faction WHERE name = :name', ['name' => 'Germany']);
        if (!$factionId) {
            return;
        }

        $divisionIds = $this->connection->fetchAllAssociative("
            SELECT id, name FROM division WHERE faction_id = :faction AND name IN ('Rifle Platoon', 'Heavy Platoon')
        ", ['faction' => $factionId]);

        $rifleId = null;
        $heavyId = null;
        foreach ($divisionIds as $row) {
            if ($row['name'] === 'Rifle Platoon') {
                $rifleId = (int) $row['id'];
            }
            if ($row['name'] === 'Heavy Platoon') {
                $heavyId = (int) $row['id'];
            }
        }

        if ($rifleId) {
            $rifleTemplates = [
                ['name' => 'Platoon Commander', 'type' => 'Platoon Commander', 'base_cost' => 50, 'description' => 'Platoon leader.', 'min_size' => 1, 'max_size' => 1, 'experience' => 'Veteran'],
                ['name' => 'Rifle Section', 'type' => 'Infantry', 'base_cost' => 60, 'description' => 'Standard infantry section.', 'min_size' => 8, 'max_size' => 10, 'experience' => 'Standard'],
                ['name' => 'Field Medic', 'type' => 'Medic', 'base_cost' => 30, 'description' => 'Medical support.', 'min_size' => 1, 'max_size' => 2, 'experience' => 'Standard'],
            ];
            foreach ($rifleTemplates as $tpl) {
                $exists = $this->connection->fetchOne(
                    'SELECT id FROM unit_template WHERE name = :name AND division_id = :division',
                    ['name' => $tpl['name'], 'division' => $rifleId]
                );
                if ($exists) {
                    continue;
                }
                $this->connection->insert('unit_template', [
                    'name' => $tpl['name'],
                    'type' => $tpl['type'],
                    'base_cost' => $tpl['base_cost'],
                    'description' => $tpl['description'],
                    'min_size' => $tpl['min_size'],
                    'max_size' => $tpl['max_size'],
                    'experience' => $tpl['experience'],
                    'division_id' => $rifleId,
                    'created_at' => $now,
                ]);
            }
        }

        if ($heavyId) {
            $heavyTemplates = [
                ['name' => 'Platoon Commander', 'type' => 'HQ', 'base_cost' => 50, 'description' => 'Heavy platoon command element.', 'min_size' => 1, 'max_size' => 3, 'experience' => 'Regular'],
                ['name' => 'Mortar Team', 'type' => 'Support', 'base_cost' => 50, 'description' => 'Mortar section.', 'min_size' => 3, 'max_size' => 3, 'experience' => 'Regular'],
                ['name' => 'MMG Team', 'type' => 'Support', 'base_cost' => 55, 'description' => 'Medium machine-gun team.', 'min_size' => 3, 'max_size' => 3, 'experience' => 'Regular'],
            ];
            foreach ($heavyTemplates as $tpl) {
                $exists = $this->connection->fetchOne(
                    'SELECT id FROM unit_template WHERE name = :name AND division_id = :division',
                    ['name' => $tpl['name'], 'division' => $heavyId]
                );
                if ($exists) {
                    continue;
                }
                $this->connection->insert('unit_template', [
                    'name' => $tpl['name'],
                    'type' => $tpl['type'],
                    'base_cost' => $tpl['base_cost'],
                    'description' => $tpl['description'],
                    'min_size' => $tpl['min_size'],
                    'max_size' => $tpl['max_size'],
                    'experience' => $tpl['experience'],
                    'division_id' => $heavyId,
                    'created_at' => $now,
                ]);
            }
        }
    }

    public function down(Schema $schema): void
    {
        $factionId = $this->connection->fetchOne('SELECT id FROM faction WHERE name = :name', ['name' => 'Germany']);
        if (!$factionId) {
            return;
        }
        $divisionIds = $this->connection->fetchFirstColumn("
            SELECT id FROM division WHERE faction_id = :faction AND name IN ('Rifle Platoon', 'Heavy Platoon')
        ", ['faction' => $factionId]);

        if ($divisionIds) {
            $this->connection->executeQuery(
                'DELETE FROM unit_template WHERE division_id IN (?) AND name IN (\'Platoon Commander\', \'Rifle Section\', \'Field Medic\', \'Mortar Team\', \'MMG Team\')',
                [$divisionIds],
                [\Doctrine\DBAL\Connection::PARAM_INT_ARRAY]
            );
        }
    }
}
