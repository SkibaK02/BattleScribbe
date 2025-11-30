<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251130140500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create unit_build table to store saved unit configurations';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE unit_build (id INT AUTO_INCREMENT NOT NULL, owner_id INT NOT NULL, unit_template_id INT NOT NULL, experience VARCHAR(50) NOT NULL, configuration JSON NOT NULL, total_cost INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_D7E1C2457E3C61F9 (owner_id), INDEX IDX_D7E1C2452F8324D5 (unit_template_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE unit_build ADD CONSTRAINT FK_D7E1C2457E3C61F9 FOREIGN KEY (owner_id) REFERENCES app_user (id)');
        $this->addSql('ALTER TABLE unit_build ADD CONSTRAINT FK_D7E1C2452F8324D5 FOREIGN KEY (unit_template_id) REFERENCES unit_template (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE unit_build');
    }
}

