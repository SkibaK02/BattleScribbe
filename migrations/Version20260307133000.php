<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260307133000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Germany faction with base divisions and armoured vehicles';
    }

    public function up(Schema $schema): void
    {
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        // Insert Germany faction if missing
        $existing = $this->connection->fetchOne('SELECT id FROM faction WHERE name IN (:short, :long)', ['short' => 'Germany', 'long' => 'Germany']);
        if ($existing) {
            $this->connection->executeStatement('UPDATE faction SET name = :long WHERE id = :id', ['long' => 'Germany', 'id' => $existing]);
            $factionId = (int) $existing;
        } else {
            $this->connection->insert('faction', [
                'name' => 'Germany',
                'description' => 'Wehrmacht/Panzer divisions with combined arms.',
                'icon' => '🇩🇪',
                'created_at' => $now,
            ]);
            $factionId = (int) $this->connection->lastInsertId();
        }

        // Ensure base divisions
        $divisions = [
            'Rifle Platoon' => 'Line infantry elements.',
            'Armoured Platoon' => 'Panzer platoon with transport and tanks.',
            'Engineer Platoon' => 'Combat engineers and pioneers.',
            'Heavy Platoon' => 'Heavy weapons support platoon.',
        ];

        $armouredDivisionId = null;
        foreach ($divisions as $name => $desc) {
            $existingId = $this->connection->fetchOne(
                'SELECT id FROM division WHERE name = :name AND faction_id = :faction',
                ['name' => $name, 'faction' => $factionId]
            );
            if (!$existingId) {
                $this->connection->insert('division', [
                    'name' => $name,
                    'description' => $desc,
                    'faction_id' => $factionId,
                    'created_at' => $now,
                ]);
                $existingId = (int) $this->connection->lastInsertId();
            }
            if ($name === 'Armoured Platoon') {
                $armouredDivisionId = (int) $existingId;
            }
        }

        // Seed armoured vehicles for Germany
        if ($armouredDivisionId) {
            $templates = [
                ['name' => 'SdKfz 251/1 Half-track', 'type' => 'Vehicle', 'base_cost' => 70, 'description' => 'Armoured half-track; transports infantry.', 'min_size' => 1, 'max_size' => 1, 'experience' => 'Regular'],
                ['name' => 'Panzer II Light Tank', 'type' => 'Vehicle', 'base_cost' => 85, 'description' => 'Light reconnaissance tank.', 'min_size' => 1, 'max_size' => 1, 'experience' => 'Regular'],
                ['name' => 'Panzer IV Medium Tank', 'type' => 'Vehicle', 'base_cost' => 125, 'description' => 'Main medium tank.', 'min_size' => 1, 'max_size' => 1, 'experience' => 'Regular'],
                ['name' => 'Tiger I Heavy Tank', 'type' => 'Vehicle', 'base_cost' => 180, 'description' => 'Heavy breakthrough tank.', 'min_size' => 1, 'max_size' => 1, 'experience' => 'Regular'],
            ];

            foreach ($templates as $tpl) {
                $exists = $this->connection->fetchOne(
                    'SELECT id FROM unit_template WHERE name = :name AND division_id = :division',
                    ['name' => $tpl['name'], 'division' => $armouredDivisionId]
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
                    'division_id' => $armouredDivisionId,
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

        $this->connection->executeStatement('DELETE FROM unit_template WHERE division_id IN (SELECT id FROM division WHERE faction_id = :f)', ['f' => $factionId]);
        $this->connection->executeStatement('DELETE FROM division WHERE faction_id = :f', ['f' => $factionId]);
        $this->connection->executeStatement('DELETE FROM faction WHERE id = :f', ['f' => $factionId]);
    }
}
