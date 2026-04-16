<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260413093000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Persiste les stats de duels et le classement final des parties terminees';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->getTable('element')->hasColumn('duels_joues_cumules')) {
            $this->addSql('ALTER TABLE element ADD duels_joues_cumules INT NOT NULL DEFAULT 0');
        }
        if (!$schema->getTable('element')->hasColumn('duels_gagnes_cumules')) {
            $this->addSql('ALTER TABLE element ADD duels_gagnes_cumules INT NOT NULL DEFAULT 0');
        }
        if (!$schema->getTable('element')->hasColumn('tournois_gagnes_cumules')) {
            $this->addSql('ALTER TABLE element ADD tournois_gagnes_cumules INT NOT NULL DEFAULT 0');
        }

        if (!$schema->getTable('partie')->hasColumn('stats_consolidees')) {
            $this->addSql('ALTER TABLE partie ADD stats_consolidees TINYINT(1) NOT NULL DEFAULT 0');
        }
        if (!$schema->getTable('partie')->hasColumn('classement_final_json')) {
            $this->addSql('ALTER TABLE partie ADD classement_final_json JSON DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->getTable('partie')->hasColumn('classement_final_json')) {
            $this->addSql('ALTER TABLE partie DROP classement_final_json');
        }
        if ($schema->getTable('partie')->hasColumn('stats_consolidees')) {
            $this->addSql('ALTER TABLE partie DROP stats_consolidees');
        }

        if ($schema->getTable('element')->hasColumn('tournois_gagnes_cumules')) {
            $this->addSql('ALTER TABLE element DROP tournois_gagnes_cumules');
        }
        if ($schema->getTable('element')->hasColumn('duels_gagnes_cumules')) {
            $this->addSql('ALTER TABLE element DROP duels_gagnes_cumules');
        }
        if ($schema->getTable('element')->hasColumn('duels_joues_cumules')) {
            $this->addSql('ALTER TABLE element DROP duels_joues_cumules');
        }
    }
}
