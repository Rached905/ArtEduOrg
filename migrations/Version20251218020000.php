<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251218020000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add acheteur_id column to sale table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sale ADD acheteur_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE sale ADD CONSTRAINT FK_E54BC00796A7BB5F FOREIGN KEY (acheteur_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_E54BC00796A7BB5F ON sale (acheteur_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sale DROP FOREIGN KEY FK_E54BC00796A7BB5F');
        $this->addSql('DROP INDEX IDX_E54BC00796A7BB5F ON sale');
        $this->addSql('ALTER TABLE sale DROP acheteur_id');
    }
}

