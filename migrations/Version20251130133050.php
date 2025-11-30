<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251130133050 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed unit templates for Rifle Platoon divisions';
    }

    public function up(Schema $schema): void
    {
        $divisions = $this->connection->fetchAllAssociative("SELECT id FROM division WHERE name = 'Rifle Platoon'");

        if (!$divisions) {
            return;
        }

        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        $slots = $this->getSlotData();

        foreach ($divisions as $division) {
            $divisionId = (int) $division['id'];

            foreach ($slots as $type => $templates) {
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
                        'type' => $type,
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
    }

    public function down(Schema $schema): void
    {
        foreach ($this->getSlotData() as $templates) {
            foreach ($templates as $template) {
                $this->connection->executeQuery(
                    'DELETE FROM unit_template WHERE name = :name',
                    ['name' => $template['name']]
                );
            }
        }
    }

    /**
     * @return array<string, array<int, array{name: string, description: string, base_cost: int, min_size: int, max_size: int, experience: string}>>
     */
    private function getSlotData(): array
    {
        return [
            'Platoon Commander' => [
                [
                    'name' => 'Platoon Commander',
                    'description' => 'Oficer dowodzący plutonem, zapewnia bonusy do dowodzenia i morale.',
                    'base_cost' => 50,
                    'min_size' => 1,
                    'max_size' => 1,
                    'experience' => 'Veteran',
                ],
            ],
            'Infantry' => [
                ['name' => 'Rifle Section', 'description' => 'Standardowa sekcja piechoty wyposażona w karabiny i LMG.', 'base_cost' => 60, 'min_size' => 8, 'max_size' => 10, 'experience' => 'Standard'],
                ['name' => 'Home Guard Section', 'description' => 'Terytorialne oddziały obrony, niższe wyszkolenie, ale liczne.', 'base_cost' => 45, 'min_size' => 8, 'max_size' => 12, 'experience' => 'Rekrut'],
                ['name' => 'Airborne Section', 'description' => 'Wysoce wyszkoleni spadochroniarze gotowi do zadań specjalnych.', 'base_cost' => 75, 'min_size' => 8, 'max_size' => 10, 'experience' => 'Veteran'],
                ['name' => 'Airlanding Rifle Section', 'description' => 'Oddział szybowcowy z dodatkowym uzbrojeniem wsparcia.', 'base_cost' => 70, 'min_size' => 8, 'max_size' => 10, 'experience' => 'Veteran'],
                ['name' => 'Chindit Section', 'description' => 'Lekka piechota dżunglowa wyszkolona do operacji dalekiego zwiadu.', 'base_cost' => 65, 'min_size' => 8, 'max_size' => 10, 'experience' => 'Veteran'],
                ['name' => 'Commando Subsection', 'description' => 'Sekcja komandosów zdolna do uderzeń na tyłach wroga.', 'base_cost' => 80, 'min_size' => 7, 'max_size' => 10, 'experience' => 'Veteran'],
                ['name' => 'Royal Navy Commando Subsection', 'description' => 'Morska odmiana komandosów, specjalizują się w desantach.', 'base_cost' => 82, 'min_size' => 7, 'max_size' => 10, 'experience' => 'Veteran'],
                ['name' => 'Commando Motorcycle Detachment', 'description' => 'Mobilna sekcja komandosów wykorzystująca motocykle.', 'base_cost' => 70, 'min_size' => 5, 'max_size' => 8, 'experience' => 'Veteran'],
                ['name' => 'SAS Infantry Section', 'description' => 'Elitarna sekcja SAS przygotowana do operacji specjalnych.', 'base_cost' => 90, 'min_size' => 6, 'max_size' => 8, 'experience' => 'Veteran'],
                ['name' => 'Corps of Military Police', 'description' => 'Oddziały wojskowej policji do zabezpieczania tyłów.', 'base_cost' => 55, 'min_size' => 6, 'max_size' => 8, 'experience' => 'Standard'],
            ],
            'Company Commander' => [
                ['name' => 'Company Commander', 'description' => 'Wyższy oficer wspierający koordynację plutonów.', 'base_cost' => 60, 'min_size' => 1, 'max_size' => 1, 'experience' => 'Veteran'],
            ],
            'Medic' => [
                ['name' => 'Field Medic', 'description' => 'Zespół medyczny podnoszący przeżywalność jednostek.', 'base_cost' => 30, 'min_size' => 1, 'max_size' => 2, 'experience' => 'Standard'],
            ],
            'Chaplain' => [
                ['name' => 'Military Chaplain', 'description' => 'Wspiera morale i pozwala przerzucać testy paniki.', 'base_cost' => 25, 'min_size' => 1, 'max_size' => 1, 'experience' => 'Standard'],
            ],
            'Forward Observer' => [
                ['name' => 'Forward Observer', 'description' => 'Koordynuje ostrzał artyleryjski i wsparcie lotnicze.', 'base_cost' => 40, 'min_size' => 1, 'max_size' => 2, 'experience' => 'Standard'],
            ],
            'Sniper' => [
                ['name' => 'Sniper Team', 'description' => 'Para snajperska eliminująca cele wysokiej wartości.', 'base_cost' => 65, 'min_size' => 2, 'max_size' => 2, 'experience' => 'Veteran'],
            ],
            'Anti-tank' => [
                ['name' => 'PIAT Team', 'description' => 'Drużyna przeciwpancerna uzbrojona w PIAT.', 'base_cost' => 50, 'min_size' => 2, 'max_size' => 3, 'experience' => 'Standard'],
            ],
            'Light Mortar' => [
                ['name' => 'Light Mortar Team', 'description' => 'Lekki moździerz wspierający natarcie.', 'base_cost' => 35, 'min_size' => 2, 'max_size' => 3, 'experience' => 'Standard'],
            ],
            'Transports' => [
                ['name' => 'Universal Carrier', 'description' => 'Lekki transporter piechoty i sprzętu.', 'base_cost' => 55, 'min_size' => 1, 'max_size' => 1, 'experience' => 'Standard'],
            ],
        ];
    }
}


