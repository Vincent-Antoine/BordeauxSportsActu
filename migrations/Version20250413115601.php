<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250413115601 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE team ADD scorenco_match_id INT DEFAULT NULL, ADD scorenco_ranking_id INT DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C4E0A61F989D9B62 ON team (slug)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_C4E0A61F989D9B62 ON team');
        $this->addSql('ALTER TABLE team DROP scorenco_match_id, DROP scorenco_ranking_id');
    }
}
