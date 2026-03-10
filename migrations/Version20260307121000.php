<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260307121000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed Soviet Veteran Squad into ZSRR Rifle Platoon divisions';
    }

    public function up(Schema $schema): void
    {
        $divisionIds = $this->connection->fetchFirstColumn("
            SELECT d.id
            FROM division d
            INNER JOIN faction f ON f.id = d.faction_id
            WHERE LOWER(f.name) LIKE :name
              AND d.name = 'Rifle Platoon'
        ", ['name' => '%zsr%']);

        if (!$divisionIds) {
            return;
        }

        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        foreach ($divisionIds as $divisionId) {
            $exists = $this->connection->fetchOne(
                'SELECT id FROM unit_template WHERE name = :name AND division_id = :division',
                ['name' => 'Veteran Squad', 'division' => $divisionId]
            );
            if ($exists) {
                continue;
            }

            $this->connection->insert('unit_template', [
                'name' => 'Veteran Squad',
                'type' => 'Infantry',
                'base_cost' => 90,
                'description' => 'Doświadczony oddział piechoty.',
                'min_size' => 6,
                'max_size' => 10,
                'experience' => 'Veteran',
                'division_id' => $divisionId,
                'created_at' => $now,
            ]);
        }
    }

    public function down(Schema $schema): void
    {
        $this->connection->executeQuery(
            'DELETE FROM unit_template WHERE name = :name',
            ['name' => 'Veteran Squad']
        );
    }
}
