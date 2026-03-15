<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour ajouter la relation vendeur à la table sale
 */
final class Version20251218010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la colonne vendeur_id à la table sale pour associer les ventes aux vendeurs';
    }

    public function up(Schema $schema): void
    {
        // Ajouter la colonne vendeur_id si elle n'existe pas déjà
        $this->addSql('ALTER TABLE sale ADD COLUMN IF NOT EXISTS vendeur_id INT DEFAULT NULL');
        
        // Ajouter la clé étrangère si elle n'existe pas déjà
        $this->addSql('ALTER TABLE sale ADD CONSTRAINT FK_E54BC005858C065E FOREIGN KEY (vendeur_id) REFERENCES users (id) ON DELETE SET NULL');
        
        // Ajouter l'index si nécessaire
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_E54BC005858C065E ON sale (vendeur_id)');
    }

    public function down(Schema $schema): void
    {
        // Supprimer la clé étrangère et l'index
        $this->addSql('ALTER TABLE sale DROP FOREIGN KEY FK_E54BC005858C065E');
        $this->addSql('DROP INDEX IDX_E54BC005858C065E ON sale');
        
        // Supprimer la colonne
        $this->addSql('ALTER TABLE sale DROP COLUMN vendeur_id');
    }
}

