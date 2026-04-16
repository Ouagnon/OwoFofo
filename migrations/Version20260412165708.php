<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260412165708 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'No-op: brouillon deja ajoute par Version20260412101000';
    }

    public function up(Schema $schema): void
    {
        // no-op
    }

    public function down(Schema $schema): void
    {
        // no-op
    }
}
