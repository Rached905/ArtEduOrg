<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour ajouter les tables d'événements, tickets et sponsors
 */
final class Version20251218000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des tables pour les événements, tickets et sponsors';
    }

    public function up(Schema $schema): void
    {
        // Table event
        $this->addSql('CREATE TABLE event (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, start_date DATETIME NOT NULL, end_date DATETIME DEFAULT NULL, location VARCHAR(255) DEFAULT NULL, type VARCHAR(255) DEFAULT NULL, status VARCHAR(255) DEFAULT NULL, capacity INT DEFAULT NULL, price NUMERIC(10, 2) DEFAULT NULL, tickets_sold INT NOT NULL, latitude NUMERIC(10, 7) DEFAULT NULL, longitude NUMERIC(10, 7) DEFAULT NULL, address VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        
        // Table sponsor
        $this->addSql('CREATE TABLE sponsor (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, phone VARCHAR(50) NOT NULL, city VARCHAR(100) DEFAULT NULL, type VARCHAR(255) NOT NULL, logo VARCHAR(255) DEFAULT NULL, website LONGTEXT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        
        // Table ticket
        $this->addSql('CREATE TABLE ticket (id INT AUTO_INCREMENT NOT NULL, event_id INT NOT NULL, price DOUBLE PRECISION NOT NULL, seat_number VARCHAR(255) DEFAULT NULL, issued_at DATETIME NOT NULL, buyer_name VARCHAR(255) NOT NULL, buyer_email VARCHAR(255) NOT NULL, quantity INT NOT NULL, unique_token VARCHAR(64) NOT NULL, checked_in_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_97A0ADA3A76ED395 (unique_token), INDEX IDX_97A0ADA371F7E88B (event_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        
        // Table sponsorship
        $this->addSql('CREATE TABLE sponsorship (id INT AUTO_INCREMENT NOT NULL, sponsor_id INT NOT NULL, description LONGTEXT NOT NULL, type VARCHAR(255) NOT NULL, sponsorship_type VARCHAR(255) NOT NULL, amount DOUBLE PRECISION NOT NULL, INDEX IDX_C0F10CD412F7FB51 (sponsor_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        
        // Table sponsor_contract
        $this->addSql('CREATE TABLE sponsor_contract (id INT AUTO_INCREMENT NOT NULL, sponsor_id INT NOT NULL, contract_number VARCHAR(100) NOT NULL, signed_at DATETIME NOT NULL, expires_at DATETIME NOT NULL, level VARCHAR(255) NOT NULL, terms LONGTEXT NOT NULL, INDEX IDX_711766EB12F7FB51 (sponsor_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        
        // Table event_review
        $this->addSql('CREATE TABLE event_review (id INT AUTO_INCREMENT NOT NULL, event_id INT NOT NULL, rating INT NOT NULL, comment VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, INDEX IDX_1B8E8B8C71F7E88B (event_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        
        // Table de liaison event_sponsor
        $this->addSql('CREATE TABLE event_sponsor (event_id INT NOT NULL, sponsor_id INT NOT NULL, INDEX IDX_9250D27B71F7E88B (event_id), INDEX IDX_9250D27B12F7FB51 (sponsor_id), PRIMARY KEY(event_id, sponsor_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        
        // Contraintes de clés étrangères
        $this->addSql('ALTER TABLE ticket ADD CONSTRAINT FK_97A0ADA371F7E88B FOREIGN KEY (event_id) REFERENCES event (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sponsorship ADD CONSTRAINT FK_C0F10CD412F7FB51 FOREIGN KEY (sponsor_id) REFERENCES sponsor (id)');
        $this->addSql('ALTER TABLE sponsor_contract ADD CONSTRAINT FK_711766EB12F7FB51 FOREIGN KEY (sponsor_id) REFERENCES sponsor (id)');
        $this->addSql('ALTER TABLE event_review ADD CONSTRAINT FK_1B8E8B8C71F7E88B FOREIGN KEY (event_id) REFERENCES event (id)');
        $this->addSql('ALTER TABLE event_sponsor ADD CONSTRAINT FK_9250D27B71F7E88B FOREIGN KEY (event_id) REFERENCES event (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE event_sponsor ADD CONSTRAINT FK_9250D27B12F7FB51 FOREIGN KEY (sponsor_id) REFERENCES sponsor (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ticket DROP FOREIGN KEY FK_97A0ADA371F7E88B');
        $this->addSql('ALTER TABLE sponsorship DROP FOREIGN KEY FK_C0F10CD412F7FB51');
        $this->addSql('ALTER TABLE sponsor_contract DROP FOREIGN KEY FK_711766EB12F7FB51');
        $this->addSql('ALTER TABLE event_review DROP FOREIGN KEY FK_1B8E8B8C71F7E88B');
        $this->addSql('ALTER TABLE event_sponsor DROP FOREIGN KEY FK_9250D27B71F7E88B');
        $this->addSql('ALTER TABLE event_sponsor DROP FOREIGN KEY FK_9250D27B12F7FB51');
        $this->addSql('DROP TABLE event');
        $this->addSql('DROP TABLE sponsor');
        $this->addSql('DROP TABLE ticket');
        $this->addSql('DROP TABLE sponsorship');
        $this->addSql('DROP TABLE sponsor_contract');
        $this->addSql('DROP TABLE event_review');
        $this->addSql('DROP TABLE event_sponsor');
    }
}


