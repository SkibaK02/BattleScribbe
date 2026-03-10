<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260307130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed Heavy Platoon with commander, mortar team and MMG team';
    }

    public function up(Schema $schema): void
    {
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $templates = [
            [
                'name' => 'Platoon Commander',
                'type' => 'HQ',
                'base_cost' => 50,
                'description' => 'Heavy platoon command element (no intelligence training).',
                'min_size' => 1,
                'max_size' => 3,
                'experience' => 'Regular',
            ],
            [
                'name' => 'Mortar Team',
                'type' => 'Support',
                'base_cost' => 50,
                'description' => 'Mortar section that can be upgraded to medium/heavy; has smoke.',
                'min_size' => 3,
                'max_size' => 3,
                'experience' => 'Regular',
            ],
            [
                'name' => 'MMG Team',
                'type' => 'Support',
                'base_cost' => 55,
                'description' => 'Medium machine-gun team; can upgrade to HMG (CKM).',
                'min_size' => 3,
                'max_size' => 3,
                'experience' => 'Regular',
            ],
        ];

        $divisionIds = $this->connection->fetchFirstColumn("
            SELECT id FROM division WHERE LOWER(name) = 'heavy platoon'
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
            SELECT id FROM division WHERE LOWER(name) = 'heavy platoon'
        ");

        if (!$divisionIds) {
            return;
        }

        $names = ['Platoon Commander', 'Mortar Team', 'MMG Team'];

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
