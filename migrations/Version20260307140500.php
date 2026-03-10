<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260307140500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add army_instance table and link roster_division to army instances';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE army_instance (id INT AUTO_INCREMENT NOT NULL, owner_id INT NOT NULL, faction_id INT NOT NULL, name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_ARMY_INSTANCE_OWNER (owner_id), INDEX IDX_ARMY_INSTANCE_FACTION (faction_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE army_instance ADD CONSTRAINT FK_ARMY_INSTANCE_OWNER FOREIGN KEY (owner_id) REFERENCES app_user (id)');
        $this->addSql('ALTER TABLE army_instance ADD CONSTRAINT FK_ARMY_INSTANCE_FACTION FOREIGN KEY (faction_id) REFERENCES faction (id)');

        $this->addSql('ALTER TABLE roster_division ADD army_instance_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE roster_division ADD CONSTRAINT FK_ROSTER_DIVISION_ARMY_INSTANCE FOREIGN KEY (army_instance_id) REFERENCES army_instance (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_ROSTER_DIVISION_ARMY_INSTANCE ON roster_division (army_instance_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE roster_division DROP FOREIGN KEY FK_ROSTER_DIVISION_ARMY_INSTANCE');
        $this->addSql('DROP INDEX IDX_ROSTER_DIVISION_ARMY_INSTANCE ON roster_division');
        $this->addSql('ALTER TABLE roster_division DROP army_instance_id');

        $this->addSql('DROP TABLE army_instance');
    }
}
