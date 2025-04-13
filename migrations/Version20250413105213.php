<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250413105213 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout du champ slug unique sur la table team (nullable pour éviter les conflits)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE team ADD slug VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C4E0A61F989D9B62 ON team (slug)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_C4E0A61F989D9B62 ON team');
        $this->addSql('ALTER TABLE team DROP slug');
    }
}
