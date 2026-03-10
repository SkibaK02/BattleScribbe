<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260307134500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Panzergrenadier Squad to Germany Rifle Platoon';
    }

    public function up(Schema $schema): void
    {
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        $divisionId = $this->connection->fetchOne("
            SELECT d.id
            FROM division d
            INNER JOIN faction f ON f.id = d.faction_id
            WHERE f.name = 'Germany' AND d.name = 'Rifle Platoon'
        ");

        if (!$divisionId) {
            return;
        }

        $exists = $this->connection->fetchOne(
            'SELECT id FROM unit_template WHERE name = :name AND division_id = :division',
            ['name' => 'Panzergrenadier Squad', 'division' => $divisionId]
        );
        if ($exists) {
            return;
        }

        $this->connection->insert('unit_template', [
            'name' => 'Panzergrenadier Squad',
            'type' => 'Infantry',
            'base_cost' => 80,
            'description' => 'Mechanised infantry squad with SMG/LMG options.',
            'min_size' => 5,
            'max_size' => 10,
            'experience' => 'Regular',
            'division_id' => $divisionId,
            'created_at' => $now,
        ]);
    }

    public function down(Schema $schema): void
    {
        $divisionId = $this->connection->fetchOne("
            SELECT d.id
            FROM division d
            INNER JOIN faction f ON f.id = d.faction_id
            WHERE f.name = 'Germany' AND d.name = 'Rifle Platoon'
        ");

        if (!$divisionId) {
            return;
        }

        $this->connection->executeStatement(
            'DELETE FROM unit_template WHERE name = :name AND division_id = :division',
            ['name' => 'Panzergrenadier Squad', 'division' => $divisionId]
        );
    }
}
