<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251130131500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed default factions (UK, USA, ZSRR, JPN) with base divisions';
    }

    public function up(Schema $schema): void
    {
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        foreach ($this->getSeedData() as $factionName => $config) {
            $factionId = $this->connection->fetchOne('SELECT id FROM faction WHERE name = :name', ['name' => $factionName]);

            if (!$factionId) {
                $this->connection->insert('faction', [
                    'name' => $factionName,
                    'description' => $config['description'],
                    'icon' => $config['icon'],
                    'created_at' => $now,
                ]);

                $factionId = (int) $this->connection->lastInsertId();
            }

            foreach ($config['divisions'] as $divisionName => $divisionDescription) {
                $exists = $this->connection->fetchOne(
                    'SELECT id FROM division WHERE name = :name AND faction_id = :faction',
                    ['name' => $divisionName, 'faction' => $factionId]
                );

                if ($exists) {
                    continue;
                }

                $this->connection->insert('division', [
                    'name' => $divisionName,
                    'description' => $divisionDescription,
                    'faction_id' => $factionId,
                    'created_at' => $now,
                ]);
            }
        }
    }

    public function down(Schema $schema): void
    {
        foreach (array_keys($this->getSeedData()) as $factionName) {
            $factionId = $this->connection->fetchOne('SELECT id FROM faction WHERE name = :name', ['name' => $factionName]);

            if (!$factionId) {
                continue;
            }

            $this->connection->delete('division', ['faction_id' => $factionId]);
            $this->connection->delete('faction', ['id' => $factionId]);
        }
    }

    /**
     * @return array<string, array{name: string, description: string, icon: string, divisions: array<string, string>}>
     */
    private function getSeedData(): array
    {
        $commonDivisions = [
            'Rifle Platoon' => 'Standard infantry platoon used to build core rosters.',
            'Armoured Platoon' => 'Armoured/vehicle platoon for mechanised assaults.',
            'Engineer Platoon' => 'Engineering specialists (fortifications, demolitions, support).',
            'Heavy Platoon' => 'Heavy weapons support platoon (fire support / AT / artillery).',
        ];

        return [
            'United Kingdom' => [
                'description' => 'Commonwealth forces with elite commandos and regular divisions.',
                'icon' => '🇬🇧',
                'divisions' => $commonDivisions,
            ],
            'United States' => [
                'description' => 'United States Army with mechanised formations and versatile infantry.',
                'icon' => '🇺🇸',
                'divisions' => $commonDivisions,
            ],
            'Soviet Union' => [
                'description' => 'Red Army formations with mass infantry and powerful armoured units.',
                'icon' => '🇷🇺',
                'divisions' => $commonDivisions,
            ],
            'Japan' => [
                'description' => 'Imperial Japanese Army with fast assault troops and skilled engineers.',
                'icon' => '🇯🇵',
                'divisions' => $commonDivisions,
            ],
            'Germany' => [
                'description' => 'Wehrmacht/Panzer divisions with combined arms.',
                'icon' => '🇩🇪',
                'divisions' => $commonDivisions,
            ],
        ];
    }
}


