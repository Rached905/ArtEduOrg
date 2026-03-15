<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add buyer_email to sale for "Mes achats" by email.
 */
final class Version20251218110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add buyer_email column to sale table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE sale ADD buyer_email VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE sale DROP buyer_email');
    }
}
