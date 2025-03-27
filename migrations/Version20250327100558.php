<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250327100558 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE portrait (id INT AUTO_INCREMENT NOT NULL, article_id INT NOT NULL, description LONGTEXT NOT NULL, nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL, is_active TINYINT(1) NOT NULL, semaine_du DATE NOT NULL, INDEX IDX_954034FB7294869C (article_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE portrait ADD CONSTRAINT FK_954034FB7294869C FOREIGN KEY (article_id) REFERENCES article (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE portrait DROP FOREIGN KEY FK_954034FB7294869C');
        $this->addSql('DROP TABLE portrait');
    }
}
