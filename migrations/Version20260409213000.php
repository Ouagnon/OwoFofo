<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260409213000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute un trigger de purge des donnees de progression quand une partie passe a l etat terminee';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TRIGGER IF EXISTS trigger_purge_partie_terminee');
        $this->addSql("CREATE TRIGGER trigger_purge_partie_terminee AFTER UPDATE ON partie FOR EACH ROW DELETE FROM manche WHERE id_partie = NEW.id AND NEW.etat_partie = 'terminee' AND OLD.etat_partie <> 'terminee'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TRIGGER IF EXISTS trigger_purge_partie_terminee');
    }
}
