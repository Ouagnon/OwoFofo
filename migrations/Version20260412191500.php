<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260412191500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la colonne brouillon sur tournoi si elle est absente';
    }

    public function up(Schema $schema): void
    {
        if ($schema->getTable('tournoi')->hasColumn('brouillon')) {
            return;
        }

        $this->addSql('ALTER TABLE tournoi ADD brouillon TINYINT(1) NOT NULL DEFAULT 1');
    }

    public function down(Schema $schema): void
    {
        if (!$schema->getTable('tournoi')->hasColumn('brouillon')) {
            return;
        }

        $this->addSql('ALTER TABLE tournoi DROP brouillon');
    }
}
