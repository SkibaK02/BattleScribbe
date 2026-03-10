<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260307132000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename Armoured Platoon vehicles to faction-specific tanks/transports with adjusted costs';
    }

    public function up(Schema $schema): void
    {
        $updates = [
            // UK (division 2)
            ['division' => 2, 'old' => 'Light Transport', 'new' => 'Universal Carrier', 'desc' => 'Light tracked carrier; transports up to 8.', 'cost' => 55],
            ['division' => 2, 'old' => 'Light Tank', 'new' => 'Stuart Light Tank', 'desc' => 'Stuart light tank.', 'cost' => 90],
            ['division' => 2, 'old' => 'Medium Tank', 'new' => 'Cromwell Medium Tank', 'desc' => 'Cromwell cruiser/medium tank.', 'cost' => 120],
            ['division' => 2, 'old' => 'Heavy Tank', 'new' => 'Churchill Heavy Tank', 'desc' => 'Infantry tank with heavy armour.', 'cost' => 160],

            // USA (division 6)
            ['division' => 6, 'old' => 'Light Transport', 'new' => 'M3 Half-track', 'desc' => 'Armoured half-track; transports up to 8.', 'cost' => 70],
            ['division' => 6, 'old' => 'Light Tank', 'new' => 'M3 Stuart Light Tank', 'desc' => 'Stuart light tank.', 'cost' => 90],
            ['division' => 6, 'old' => 'Medium Tank', 'new' => 'M4 Sherman Medium Tank', 'desc' => 'M4 Sherman medium tank.', 'cost' => 120],
            ['division' => 6, 'old' => 'Heavy Tank', 'new' => 'M26 Pershing Heavy Tank', 'desc' => 'Pershing heavy/medium tank.', 'cost' => 170],

            // ZSRR (division 10)
            ['division' => 10, 'old' => 'Light Transport', 'new' => 'M5 Half-track', 'desc' => 'Lend-lease half-track; transports up to 8.', 'cost' => 70],
            ['division' => 10, 'old' => 'Light Tank', 'new' => 'T-70 Light Tank', 'desc' => 'T-70 light tank.', 'cost' => 85],
            ['division' => 10, 'old' => 'Medium Tank', 'new' => 'T-34/85 Medium Tank', 'desc' => 'T-34/85 medium tank.', 'cost' => 125],
            ['division' => 10, 'old' => 'Heavy Tank', 'new' => 'IS-2 Heavy Tank', 'desc' => 'IS-2 breakthrough heavy tank.', 'cost' => 175],

            // JPN (division 14)
            ['division' => 14, 'old' => 'Light Transport', 'new' => 'Type 1 Ho-Ha', 'desc' => 'Japanese APC; transports up to 8.', 'cost' => 65],
            ['division' => 14, 'old' => 'Light Tank', 'new' => 'Type 95 Ha-Go Light Tank', 'desc' => 'Ha-Go light tank.', 'cost' => 80],
            ['division' => 14, 'old' => 'Medium Tank', 'new' => 'Type 97 Chi-Ha Medium Tank', 'desc' => 'Chi-Ha medium tank.', 'cost' => 110],
            ['division' => 14, 'old' => 'Heavy Tank', 'new' => 'Type 4 Chi-To Heavy Tank', 'desc' => 'Chi-To heavy tank.', 'cost' => 150],
        ];

        foreach ($updates as $row) {
            $this->connection->executeStatement(
                'UPDATE unit_template SET name = :new, description = :desc, base_cost = :cost WHERE division_id = :div AND name = :old',
                [
                    'new' => $row['new'],
                    'desc' => $row['desc'],
                    'cost' => $row['cost'],
                    'div' => $row['division'],
                    'old' => $row['old'],
                ]
            );
        }
    }

    public function down(Schema $schema): void
    {
        // best-effort rollback to generic names/costs
        $rollbacks = [
            ['division' => 2, 'old' => 'Universal Carrier', 'new' => 'Light Transport', 'desc' => 'Light transport; carries up to 8 models.', 'cost' => 60],
            ['division' => 2, 'old' => 'Stuart Light Tank', 'new' => 'Light Tank', 'desc' => 'Light tank chassis.', 'cost' => 90],
            ['division' => 2, 'old' => 'Cromwell Medium Tank', 'new' => 'Medium Tank', 'desc' => 'Medium tank chassis.', 'cost' => 120],
            ['division' => 2, 'old' => 'Churchill Heavy Tank', 'new' => 'Heavy Tank', 'desc' => 'Heavy tank chassis.', 'cost' => 160],

            ['division' => 6, 'old' => 'M3 Half-track', 'new' => 'Light Transport', 'desc' => 'Light transport; carries up to 8 models.', 'cost' => 60],
            ['division' => 6, 'old' => 'M3 Stuart Light Tank', 'new' => 'Light Tank', 'desc' => 'Light tank chassis.', 'cost' => 90],
            ['division' => 6, 'old' => 'M4 Sherman Medium Tank', 'new' => 'Medium Tank', 'desc' => 'Medium tank chassis.', 'cost' => 120],
            ['division' => 6, 'old' => 'M26 Pershing Heavy Tank', 'new' => 'Heavy Tank', 'desc' => 'Heavy tank chassis.', 'cost' => 160],

            ['division' => 10, 'old' => 'M5 Half-track', 'new' => 'Light Transport', 'desc' => 'Light transport; carries up to 8 models.', 'cost' => 60],
            ['division' => 10, 'old' => 'T-70 Light Tank', 'new' => 'Light Tank', 'desc' => 'Light tank chassis.', 'cost' => 90],
            ['division' => 10, 'old' => 'T-34/85 Medium Tank', 'new' => 'Medium Tank', 'desc' => 'Medium tank chassis.', 'cost' => 120],
            ['division' => 10, 'old' => 'IS-2 Heavy Tank', 'new' => 'Heavy Tank', 'desc' => 'Heavy tank chassis.', 'cost' => 160],

            ['division' => 14, 'old' => 'Type 1 Ho-Ha', 'new' => 'Light Transport', 'desc' => 'Light transport; carries up to 8 models.', 'cost' => 60],
            ['division' => 14, 'old' => 'Type 95 Ha-Go Light Tank', 'new' => 'Light Tank', 'desc' => 'Light tank chassis.', 'cost' => 90],
            ['division' => 14, 'old' => 'Type 97 Chi-Ha Medium Tank', 'new' => 'Medium Tank', 'desc' => 'Medium tank chassis.', 'cost' => 120],
            ['division' => 14, 'old' => 'Type 4 Chi-To Heavy Tank', 'new' => 'Heavy Tank', 'desc' => 'Heavy tank chassis.', 'cost' => 160],
        ];

        foreach ($rollbacks as $row) {
            $this->connection->executeStatement(
                'UPDATE unit_template SET name = :new, description = :desc, base_cost = :cost WHERE division_id = :div AND name = :old',
                [
                    'new' => $row['new'],
                    'desc' => $row['desc'],
                    'cost' => $row['cost'],
                    'div' => $row['division'],
                    'old' => $row['old'],
                ]
            );
        }
    }
}
