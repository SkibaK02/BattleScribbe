<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260307150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove unused roster/weapon/special_rule tables and related join tables';
    }

    public function up(Schema $schema): void
    {
        // Drop join tables first to avoid FK errors
        $this->addSql('DROP TABLE IF EXISTS unit_template_special_rule');
        $this->addSql('DROP TABLE IF EXISTS unit_template_weapon');
        $this->addSql('DROP TABLE IF EXISTS roster_unit_weapon');
        $this->addSql('DROP TABLE IF EXISTS roster_unit');
        $this->addSql('DROP TABLE IF EXISTS roster');
        $this->addSql('DROP TABLE IF EXISTS weapon');
        $this->addSql('DROP TABLE IF EXISTS special_rule');
    }

    public function down(Schema $schema): void
    {
        // Recreate tables (copied from initial schema) to allow rollback
        $this->addSql('CREATE TABLE special_rule (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, type VARCHAR(50) DEFAULT NULL, point_modifier INT DEFAULT NULL, created_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE weapon (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, type VARCHAR(100) NOT NULL, strength INT NOT NULL, armor_penetration INT DEFAULT NULL, `range` INT DEFAULT NULL, cost INT DEFAULT NULL, description LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE roster (id INT AUTO_INCREMENT NOT NULL, faction_id INT NOT NULL, division_id INT NOT NULL, name VARCHAR(255) NOT NULL, points_limit INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_60B9ADF94448F8DA (faction_id), INDEX IDX_60B9ADF941859289 (division_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE roster_unit (id INT AUTO_INCREMENT NOT NULL, unit_template_id INT NOT NULL, roster_id INT NOT NULL, custom_cost INT NOT NULL, quantity INT NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_D4C921849EBD659E (unit_template_id), INDEX IDX_D4C9218475404483 (roster_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE roster_unit_weapon (id INT AUTO_INCREMENT NOT NULL, roster_unit_id INT NOT NULL, weapon_id INT NOT NULL, quantity INT NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_6D9AFC73EE5A194F (roster_unit_id), INDEX IDX_6D9AFC7395B82273 (weapon_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE unit_template_special_rule (unit_template_id INT NOT NULL, special_rule_id INT NOT NULL, INDEX IDX_32B4027B9EBD659E (unit_template_id), INDEX IDX_32B4027BAC425FC4 (special_rule_id), PRIMARY KEY(unit_template_id, special_rule_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE unit_template_weapon (unit_template_id INT NOT NULL, weapon_id INT NOT NULL, INDEX IDX_EFA3A7AC9EBD659E (unit_template_id), INDEX IDX_EFA3A7AC95B82273 (weapon_id), PRIMARY KEY(unit_template_id, weapon_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE roster ADD CONSTRAINT FK_60B9ADF94448F8DA FOREIGN KEY (faction_id) REFERENCES faction (id)');
        $this->addSql('ALTER TABLE roster ADD CONSTRAINT FK_60B9ADF941859289 FOREIGN KEY (division_id) REFERENCES division (id)');
        $this->addSql('ALTER TABLE roster_unit ADD CONSTRAINT FK_D4C921849EBD659E FOREIGN KEY (unit_template_id) REFERENCES unit_template (id)');
        $this->addSql('ALTER TABLE roster_unit ADD CONSTRAINT FK_D4C9218475404483 FOREIGN KEY (roster_id) REFERENCES roster (id)');
        $this->addSql('ALTER TABLE roster_unit_weapon ADD CONSTRAINT FK_6D9AFC73EE5A194F FOREIGN KEY (roster_unit_id) REFERENCES roster_unit (id)');
        $this->addSql('ALTER TABLE roster_unit_weapon ADD CONSTRAINT FK_6D9AFC7395B82273 FOREIGN KEY (weapon_id) REFERENCES weapon (id)');
        $this->addSql('ALTER TABLE unit_template_special_rule ADD CONSTRAINT FK_32B4027B9EBD659E FOREIGN KEY (unit_template_id) REFERENCES unit_template (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unit_template_special_rule ADD CONSTRAINT FK_32B4027BAC425FC4 FOREIGN KEY (special_rule_id) REFERENCES special_rule (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unit_template_weapon ADD CONSTRAINT FK_EFA3A7AC9EBD659E FOREIGN KEY (unit_template_id) REFERENCES unit_template (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unit_template_weapon ADD CONSTRAINT FK_EFA3A7AC95B82273 FOREIGN KEY (weapon_id) REFERENCES weapon (id) ON DELETE CASCADE');
    }
}
