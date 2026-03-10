<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260307124000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update division descriptions for Rifle, Armoured, Heavy Platoons';
    }

    public function up(Schema $schema): void
    {
        $updates = [
            'Rifle Platoon' => 'Standard infantry platoon used to build core rosters.',
            'Armoured Platoon' => 'Armoured/vehicle platoon for mechanised assaults.',
            'Heavy Platoon' => 'Heavy weapons support platoon (fire support / AT / artillery).',
        ];

        foreach ($updates as $name => $desc) {
            $this->connection->update('division', ['description' => $desc], ['name' => $name]);
        }
    }

    public function down(Schema $schema): void
    {
        // no-op rollback
    }
}
