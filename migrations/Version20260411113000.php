<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260411113000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les preferences repechage/classement au joueur';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE joueur ADD activer_repechage TINYINT(1) DEFAULT 1 NOT NULL, ADD activer_classement_perdants TINYINT(1) DEFAULT 1 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE joueur DROP activer_repechage, DROP activer_classement_perdants');
    }
}
