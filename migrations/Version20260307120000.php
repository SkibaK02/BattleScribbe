<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260307120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed US Marines Squad into USA Rifle Platoon divisions';
    }

    public function up(Schema $schema): void
    {
        $divisionIds = $this->connection->fetchFirstColumn("
            SELECT d.id
            FROM division d
            INNER JOIN faction f ON f.id = d.faction_id
            WHERE LOWER(f.name) LIKE :usa
              AND d.name = 'Rifle Platoon'
        ", ['usa' => '%usa%']);

        if (!$divisionIds) {
            return;
        }

        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        foreach ($divisionIds as $divisionId) {
            $exists = $this->connection->fetchOne(
                'SELECT id FROM unit_template WHERE name = :name AND division_id = :division',
                ['name' => 'Marines Squad', 'division' => $divisionId]
            );
            if ($exists) {
                continue;
            }

            $this->connection->insert('unit_template', [
                'name' => 'Marines Squad',
                'type' => 'Infantry',
                'base_cost' => 80,
                'description' => 'US Marines infantry squad.',
                'min_size' => 5,
                'max_size' => 10,
                'experience' => 'Regular',
                'division_id' => $divisionId,
                'created_at' => $now,
            ]);
        }
    }

    public function down(Schema $schema): void
    {
        $this->connection->executeQuery(
            'DELETE FROM unit_template WHERE name = :name',
            ['name' => 'Marines Squad']
        );
    }
}
