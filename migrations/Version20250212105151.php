<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250212105151 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_favorite_sport (user_id INT NOT NULL, team_id INT NOT NULL, added_at DATETIME NOT NULL, INDEX IDX_C7881BAFA76ED395 (user_id), INDEX IDX_C7881BAF296CD8AE (team_id), PRIMARY KEY(user_id, team_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_favorite_sport ADD CONSTRAINT FK_C7881BAFA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_favorite_sport ADD CONSTRAINT FK_C7881BAF296CD8AE FOREIGN KEY (team_id) REFERENCES team (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_favorite_sport DROP FOREIGN KEY FK_C7881BAFA76ED395');
        $this->addSql('ALTER TABLE user_favorite_sport DROP FOREIGN KEY FK_C7881BAF296CD8AE');
        $this->addSql('DROP TABLE user_favorite_sport');
    }
}
