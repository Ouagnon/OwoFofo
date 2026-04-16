<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260411153647 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tournoi DROP forcer_confrontation_themes, DROP gerer_impair, DROP activer_repechage, DROP activer_classement_perdants');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tournoi ADD forcer_confrontation_themes TINYINT(1) DEFAULT 1 NOT NULL, ADD gerer_impair TINYINT(1) DEFAULT 1 NOT NULL, ADD activer_repechage TINYINT(1) DEFAULT 1 NOT NULL, ADD activer_classement_perdants TINYINT(1) DEFAULT 1 NOT NULL');
    }
}
