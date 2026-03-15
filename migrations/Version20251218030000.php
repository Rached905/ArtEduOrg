<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour ajouter la colonne user_id à la table event_review
 */
final class Version20251218030000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la colonne user_id à la table event_review pour associer les avis aux utilisateurs';
    }

    public function up(Schema $schema): void
    {
        // Ajouter la colonne user_id si elle n'existe pas déjà
        $this->addSql('ALTER TABLE event_review ADD COLUMN IF NOT EXISTS user_id INT DEFAULT NULL');
        
        // Ajouter la clé étrangère si elle n'existe pas déjà
        $this->addSql('ALTER TABLE event_review ADD CONSTRAINT FK_EVENT_REVIEW_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL');
        
        // Ajouter l'index si nécessaire
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_EVENT_REVIEW_USER ON event_review (user_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE event_review DROP FOREIGN KEY FK_EVENT_REVIEW_USER');
        $this->addSql('DROP INDEX IDX_EVENT_REVIEW_USER ON event_review');
        $this->addSql('ALTER TABLE event_review DROP user_id');
    }
}

