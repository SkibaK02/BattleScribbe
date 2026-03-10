<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260307135000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add roster_division table and link unit_build to roster divisions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE roster_division (id INT AUTO_INCREMENT NOT NULL, owner_id INT NOT NULL, division_id INT NOT NULL, name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_ROSTER_DIVISION_OWNER (owner_id), INDEX IDX_ROSTER_DIVISION_DIVISION (division_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE roster_division ADD CONSTRAINT FK_ROSTER_DIVISION_OWNER FOREIGN KEY (owner_id) REFERENCES app_user (id)');
        $this->addSql('ALTER TABLE roster_division ADD CONSTRAINT FK_ROSTER_DIVISION_DIVISION FOREIGN KEY (division_id) REFERENCES division (id)');

        $this->addSql('ALTER TABLE unit_build ADD roster_division_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE unit_build ADD CONSTRAINT FK_UNIT_BUILD_ROSTER_DIVISION FOREIGN KEY (roster_division_id) REFERENCES roster_division (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_UNIT_BUILD_ROSTER_DIVISION ON unit_build (roster_division_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE unit_build DROP FOREIGN KEY FK_UNIT_BUILD_ROSTER_DIVISION');
        $this->addSql('DROP INDEX IDX_UNIT_BUILD_ROSTER_DIVISION ON unit_build');
        $this->addSql('ALTER TABLE unit_build DROP roster_division_id');

        $this->addSql('DROP TABLE roster_division');
    }
}
