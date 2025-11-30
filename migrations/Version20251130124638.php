<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251130124638 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE roster ADD owner_id INT NOT NULL');
        $this->addSql('ALTER TABLE roster ADD CONSTRAINT FK_60B9ADF97E3C61F9 FOREIGN KEY (owner_id) REFERENCES app_user (id)');
        $this->addSql('CREATE INDEX IDX_60B9ADF97E3C61F9 ON roster (owner_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE roster DROP FOREIGN KEY FK_60B9ADF97E3C61F9');
        $this->addSql('DROP INDEX IDX_60B9ADF97E3C61F9 ON roster');
        $this->addSql('ALTER TABLE roster DROP owner_id');
    }
}
