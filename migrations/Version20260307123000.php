<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260307123000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ensure special squads exist for USA/USSR/Japan rifle platoons';
    }

    public function up(Schema $schema): void
    {
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        // USA Marines
        $this->insertIfMissing('Marines Squad', 'Infantry', 80, 'US Marines infantry squad.', 5, 10, 'Regular', '%usa%', '%rifle platoon%', $now);
        // USSR Veterans
        $this->insertIfMissing('Veteran Squad', 'Infantry', 90, 'Doświadczony oddział piechoty.', 6, 10, 'Veteran', '%zsr%', '%rifle platoon%', $now);
        // Japan IJA
        $this->insertIfMissing('IJA Infantry Squad', 'Infantry', 85, 'Oddział piechoty IJA.', 5, 12, 'Regular', '%jpn%', '%rifle platoon%', $now);
    }

    public function down(Schema $schema): void
    {
        $this->connection->executeQuery('DELETE FROM unit_template WHERE name IN (:names)', [
            'names' => ['Marines Squad', 'Veteran Squad', 'IJA Infantry Squad'],
        ], [
            'names' => \Doctrine\DBAL\ParameterType::ASCII,
        ]);
    }

    private function insertIfMissing(
        string $name,
        string $type,
        int $baseCost,
        string $description,
        int $minSize,
        int $maxSize,
        string $experience,
        string $factionLike,
        string $divisionLike,
        string $now
    ): void {
        $divisionIds = $this->connection->fetchFirstColumn("
            SELECT d.id
            FROM division d
            INNER JOIN faction f ON f.id = d.faction_id
            WHERE LOWER(f.name) LIKE :faction
              AND LOWER(d.name) LIKE :division
        ", ['faction' => $factionLike, 'division' => $divisionLike]);

        if (!$divisionIds) {
            return;
        }

        foreach ($divisionIds as $divisionId) {
            $exists = $this->connection->fetchOne(
                'SELECT id FROM unit_template WHERE name = :name AND division_id = :division',
                ['name' => $name, 'division' => $divisionId]
            );
            if ($exists) {
                continue;
            }

            $this->connection->insert('unit_template', [
                'name' => $name,
                'type' => $type,
                'base_cost' => $baseCost,
                'description' => $description,
                'min_size' => $minSize,
                'max_size' => $maxSize,
                'experience' => $experience,
                'division_id' => $divisionId,
                'created_at' => $now,
            ]);
        }
    }
}
