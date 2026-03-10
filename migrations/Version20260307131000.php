<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260307131000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed Armoured Platoon with transport and tanks (LKM option)';
    }

    public function up(Schema $schema): void
    {
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $templates = [
            [
                'name' => 'Light Transport',
                'type' => 'Vehicle',
                'base_cost' => 60,
                'description' => 'Light transport; carries up to 8 models.',
                'min_size' => 1,
                'max_size' => 1,
                'experience' => 'Regular',
            ],
            [
                'name' => 'Light Tank',
                'type' => 'Vehicle',
                'base_cost' => 90,
                'description' => 'Light tank chassis.',
                'min_size' => 1,
                'max_size' => 1,
                'experience' => 'Regular',
            ],
            [
                'name' => 'Medium Tank',
                'type' => 'Vehicle',
                'base_cost' => 120,
                'description' => 'Medium tank chassis.',
                'min_size' => 1,
                'max_size' => 1,
                'experience' => 'Regular',
            ],
            [
                'name' => 'Heavy Tank',
                'type' => 'Vehicle',
                'base_cost' => 160,
                'description' => 'Heavy tank chassis.',
                'min_size' => 1,
                'max_size' => 1,
                'experience' => 'Regular',
            ],
        ];

        $divisionIds = $this->connection->fetchFirstColumn("
            SELECT id FROM division WHERE LOWER(name) = 'armoured platoon'
        ");

        if (!$divisionIds) {
            return;
        }

        foreach ($divisionIds as $divisionId) {
            foreach ($templates as $template) {
                $exists = $this->connection->fetchOne(
                    'SELECT id FROM unit_template WHERE name = :name AND division_id = :division',
                    ['name' => $template['name'], 'division' => $divisionId]
                );

                if ($exists) {
                    continue;
                }

                $this->connection->insert('unit_template', [
                    'name' => $template['name'],
                    'type' => $template['type'],
                    'base_cost' => $template['base_cost'],
                    'description' => $template['description'],
                    'min_size' => $template['min_size'],
                    'max_size' => $template['max_size'],
                    'experience' => $template['experience'],
                    'division_id' => $divisionId,
                    'created_at' => $now,
                ]);
            }
        }
    }

    public function down(Schema $schema): void
    {
        $divisionIds = $this->connection->fetchFirstColumn("
            SELECT id FROM division WHERE LOWER(name) = 'armoured platoon'
        ");

        if (!$divisionIds) {
            return;
        }

        $names = ['Light Transport', 'Light Tank', 'Medium Tank', 'Heavy Tank'];

        foreach ($divisionIds as $divisionId) {
            foreach ($names as $name) {
                $this->connection->executeQuery(
                    'DELETE FROM unit_template WHERE name = :name AND division_id = :division',
                    ['name' => $name, 'division' => $divisionId]
                );
            }
        }
    }
}
