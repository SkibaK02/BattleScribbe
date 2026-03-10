<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260307121500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed IJA Infantry Squad into JPN Rifle Platoon divisions';
    }

    public function up(Schema $schema): void
    {
        $divisionIds = $this->connection->fetchFirstColumn("
            SELECT d.id
            FROM division d
            INNER JOIN faction f ON f.id = d.faction_id
            WHERE LOWER(f.name) LIKE :name
              AND d.name = 'Rifle Platoon'
        ", ['name' => '%jpn%']);

        if (!$divisionIds) {
            return;
        }

        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        foreach ($divisionIds as $divisionId) {
            $exists = $this->connection->fetchOne(
                'SELECT id FROM unit_template WHERE name = :name AND division_id = :division',
                ['name' => 'IJA Infantry Squad', 'division' => $divisionId]
            );
            if ($exists) {
                continue;
            }

            $this->connection->insert('unit_template', [
                'name' => 'IJA Infantry Squad',
                'type' => 'Infantry',
                'base_cost' => 85,
                'description' => 'Oddział piechoty IJA.',
                'min_size' => 5,
                'max_size' => 12,
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
            ['name' => 'IJA Infantry Squad']
        );
    }
}
