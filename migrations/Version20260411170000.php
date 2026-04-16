<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260411170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute l URL de couverture sur tournoi';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tournoi ADD url_couverture VARCHAR(2048) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tournoi DROP url_couverture');
    }
}
