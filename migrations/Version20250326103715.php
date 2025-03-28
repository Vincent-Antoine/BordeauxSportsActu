<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250326103715 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE article_team (article_id INT NOT NULL, team_id INT NOT NULL, INDEX IDX_74A2211E7294869C (article_id), INDEX IDX_74A2211E296CD8AE (team_id), PRIMARY KEY(article_id, team_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE article_team ADD CONSTRAINT FK_74A2211E7294869C FOREIGN KEY (article_id) REFERENCES article (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE article_team ADD CONSTRAINT FK_74A2211E296CD8AE FOREIGN KEY (team_id) REFERENCES team (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE article_team DROP FOREIGN KEY FK_74A2211E7294869C');
        $this->addSql('ALTER TABLE article_team DROP FOREIGN KEY FK_74A2211E296CD8AE');
        $this->addSql('DROP TABLE article_team');
    }
}
