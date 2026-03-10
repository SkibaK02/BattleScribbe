<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260307140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update faction names/descriptions to full titles';
    }

    public function up(Schema $schema): void
    {
        $map = [
            'UK' => ['United Kingdom', 'Commonwealth forces with elite commandos and regular divisions.', '🇬🇧'],
            'USA' => ['United States', 'United States Army with mechanised formations and versatile infantry.', '🇺🇸'],
            'ZSRR' => ['Soviet Union', 'Red Army formations with mass infantry and powerful armoured units.', '🇷🇺'],
            'JPN' => ['Japan', 'Imperial Japanese Army with fast assault troops and skilled engineers.', '🇯🇵'],
            'Germany' => ['Germany', 'Wehrmacht/Panzer divisions with combined arms.', '🇩🇪'],
        ];

        foreach ($map as $old => [$newName, $desc, $icon]) {
            $this->connection->executeStatement(
                'UPDATE faction SET name = :new, description = :desc, icon = :icon WHERE name = :old',
                ['new' => $newName, 'desc' => $desc, 'icon' => $icon, 'old' => $old]
            );
        }
    }

    public function down(Schema $schema): void
    {
        $map = [
            'United Kingdom' => ['UK', 'Commonwealth forces with elite commandos and regular divisions.', '🇬🇧'],
            'United States' => ['USA', 'United States Army with mechanised formations and versatile infantry.', '🇺🇸'],
            'Soviet Union' => ['ZSRR', 'Red Army formations with mass infantry and powerful armoured units.', '🇷🇺'],
            'Japan' => ['JPN', 'Imperial Japanese Army with fast assault troops and skilled engineers.', '🇯🇵'],
            'Germany' => ['Germany', 'Wehrmacht/Panzer divisions with combined arms.', '🇩🇪'],
        ];

        foreach ($map as $old => [$newName, $desc, $icon]) {
            $this->connection->executeStatement(
                'UPDATE faction SET name = :new, description = :desc, icon = :icon WHERE name = :old',
                ['new' => $newName, 'desc' => $desc, 'icon' => $icon, 'old' => $old]
            );
        }
    }
}
