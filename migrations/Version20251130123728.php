<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251130123728 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE division (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, faction_id INT NOT NULL, INDEX IDX_101747144448F8DA (faction_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE faction (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, icon VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE roster (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, points_limit INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, faction_id INT NOT NULL, division_id INT NOT NULL, INDEX IDX_60B9ADF94448F8DA (faction_id), INDEX IDX_60B9ADF941859289 (division_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE roster_unit (id INT AUTO_INCREMENT NOT NULL, custom_cost INT NOT NULL, quantity INT NOT NULL, created_at DATETIME NOT NULL, unit_template_id INT NOT NULL, roster_id INT NOT NULL, INDEX IDX_D4C921849EBD659E (unit_template_id), INDEX IDX_D4C9218475404483 (roster_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE roster_unit_weapon (id INT AUTO_INCREMENT NOT NULL, quantity INT NOT NULL, created_at DATETIME NOT NULL, roster_unit_id INT NOT NULL, weapon_id INT NOT NULL, INDEX IDX_6D9AFC73EE5A194F (roster_unit_id), INDEX IDX_6D9AFC7395B82273 (weapon_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE special_rule (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, type VARCHAR(50) DEFAULT NULL, point_modifier INT DEFAULT NULL, created_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE unit_template (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, type VARCHAR(100) NOT NULL, base_cost INT NOT NULL, description LONGTEXT DEFAULT NULL, min_size INT DEFAULT NULL, max_size INT DEFAULT NULL, experience VARCHAR(50) DEFAULT NULL, created_at DATETIME NOT NULL, division_id INT NOT NULL, INDEX IDX_E25EF51141859289 (division_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE unit_template_weapon (unit_template_id INT NOT NULL, weapon_id INT NOT NULL, INDEX IDX_EFA3A7AC9EBD659E (unit_template_id), INDEX IDX_EFA3A7AC95B82273 (weapon_id), PRIMARY KEY (unit_template_id, weapon_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE unit_template_special_rule (unit_template_id INT NOT NULL, special_rule_id INT NOT NULL, INDEX IDX_32B4027B9EBD659E (unit_template_id), INDEX IDX_32B4027BAC425FC4 (special_rule_id), PRIMARY KEY (unit_template_id, special_rule_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE weapon (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, type VARCHAR(100) NOT NULL, strength INT NOT NULL, armor_penetration INT DEFAULT NULL, `range` INT DEFAULT NULL, cost INT DEFAULT NULL, description LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE division ADD CONSTRAINT FK_101747144448F8DA FOREIGN KEY (faction_id) REFERENCES faction (id)');
        $this->addSql('ALTER TABLE roster ADD CONSTRAINT FK_60B9ADF94448F8DA FOREIGN KEY (faction_id) REFERENCES faction (id)');
        $this->addSql('ALTER TABLE roster ADD CONSTRAINT FK_60B9ADF941859289 FOREIGN KEY (division_id) REFERENCES division (id)');
        $this->addSql('ALTER TABLE roster_unit ADD CONSTRAINT FK_D4C921849EBD659E FOREIGN KEY (unit_template_id) REFERENCES unit_template (id)');
        $this->addSql('ALTER TABLE roster_unit ADD CONSTRAINT FK_D4C9218475404483 FOREIGN KEY (roster_id) REFERENCES roster (id)');
        $this->addSql('ALTER TABLE roster_unit_weapon ADD CONSTRAINT FK_6D9AFC73EE5A194F FOREIGN KEY (roster_unit_id) REFERENCES roster_unit (id)');
        $this->addSql('ALTER TABLE roster_unit_weapon ADD CONSTRAINT FK_6D9AFC7395B82273 FOREIGN KEY (weapon_id) REFERENCES weapon (id)');
        $this->addSql('ALTER TABLE unit_template ADD CONSTRAINT FK_E25EF51141859289 FOREIGN KEY (division_id) REFERENCES division (id)');
        $this->addSql('ALTER TABLE unit_template_weapon ADD CONSTRAINT FK_EFA3A7AC9EBD659E FOREIGN KEY (unit_template_id) REFERENCES unit_template (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unit_template_weapon ADD CONSTRAINT FK_EFA3A7AC95B82273 FOREIGN KEY (weapon_id) REFERENCES weapon (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unit_template_special_rule ADD CONSTRAINT FK_32B4027B9EBD659E FOREIGN KEY (unit_template_id) REFERENCES unit_template (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE unit_template_special_rule ADD CONSTRAINT FK_32B4027BAC425FC4 FOREIGN KEY (special_rule_id) REFERENCES special_rule (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE division DROP FOREIGN KEY FK_101747144448F8DA');
        $this->addSql('ALTER TABLE roster DROP FOREIGN KEY FK_60B9ADF94448F8DA');
        $this->addSql('ALTER TABLE roster DROP FOREIGN KEY FK_60B9ADF941859289');
        $this->addSql('ALTER TABLE roster_unit DROP FOREIGN KEY FK_D4C921849EBD659E');
        $this->addSql('ALTER TABLE roster_unit DROP FOREIGN KEY FK_D4C9218475404483');
        $this->addSql('ALTER TABLE roster_unit_weapon DROP FOREIGN KEY FK_6D9AFC73EE5A194F');
        $this->addSql('ALTER TABLE roster_unit_weapon DROP FOREIGN KEY FK_6D9AFC7395B82273');
        $this->addSql('ALTER TABLE unit_template DROP FOREIGN KEY FK_E25EF51141859289');
        $this->addSql('ALTER TABLE unit_template_weapon DROP FOREIGN KEY FK_EFA3A7AC9EBD659E');
        $this->addSql('ALTER TABLE unit_template_weapon DROP FOREIGN KEY FK_EFA3A7AC95B82273');
        $this->addSql('ALTER TABLE unit_template_special_rule DROP FOREIGN KEY FK_32B4027B9EBD659E');
        $this->addSql('ALTER TABLE unit_template_special_rule DROP FOREIGN KEY FK_32B4027BAC425FC4');
        $this->addSql('DROP TABLE division');
        $this->addSql('DROP TABLE faction');
        $this->addSql('DROP TABLE roster');
        $this->addSql('DROP TABLE roster_unit');
        $this->addSql('DROP TABLE roster_unit_weapon');
        $this->addSql('DROP TABLE special_rule');
        $this->addSql('DROP TABLE unit_template');
        $this->addSql('DROP TABLE unit_template_weapon');
        $this->addSql('DROP TABLE unit_template_special_rule');
        $this->addSql('DROP TABLE weapon');
    }
}
