<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251130135500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Translate faction descriptions to English';
    }

    public function up(Schema $schema): void
    {
        $descriptions = [
            'UK' => 'Commonwealth forces with elite commandos and regular divisions.',
            'USA' => 'United States Army with mechanised formations and versatile infantry.',
            'ZSRR' => 'Red Army formations with mass infantry and powerful armored units.',
            'JPN' => 'Imperial Japanese Army with fast assault troops and skilled engineers.',
        ];

        foreach ($descriptions as $name => $description) {
            $this->addSql('UPDATE faction SET description = :description WHERE name = :name', [
                'description' => $description,
                'name' => $name,
            ]);
        }
    }

    public function down(Schema $schema): void
    {
        // no-op: translations do not need to be reverted
    }
}


