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
            'Rifle Platoon' => 'Lekka piechota liniowa, uniwersalne oddziały zdolne do zabezpieczania punktów.',
            'Armoured Platoon' => 'Zmotoryzowane lub pancerne elementy szturmowe z pojazdami wsparcia.',
            'Engineer Platoon' => 'Specjaliści od umocnień, ładunków wybuchowych i operacji wsparcia.',
            'Heavy Platoon' => 'Ciężkie uzbrojenie: wsparcie ogniowe, działa, broń przeciwpancerna.',
        ];

        return [
            'UK' => [
                'description' => 'Siły Wspólnoty Brytyjskiej, elitarne jednostki komandosów i regularne dywizje.',
                'icon' => '🇬🇧',
                'divisions' => $commonDivisions,
            ],
            'USA' => [
                'description' => 'Armia Stanów Zjednoczonych, zmechanizowane oddziały i wszechstronna piechota.',
                'icon' => '🇺🇸',
                'divisions' => $commonDivisions,
            ],
            'ZSRR' => [
                'description' => 'Siły Armii Czerwonej, masowe formacje piechoty i potężne jednostki pancerne.',
                'icon' => '🇷🇺',
                'divisions' => $commonDivisions,
            ],
            'JPN' => [
                'description' => 'Cesarska Armia Japońska, szybkie oddziały szturmowe i wyszkoleni inżynierowie.',
                'icon' => '🇯🇵',
                'divisions' => $commonDivisions,
            ],
        ];
    }
}

