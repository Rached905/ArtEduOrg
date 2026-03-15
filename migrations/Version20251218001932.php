<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251218001932 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Migration pour les tables de ventes uniquement
        // Les autres tables (event, users, etc.) existent déjà dans la base de données
        // Utilisation de IF NOT EXISTS pour éviter les erreurs si les tables existent déjà
        
        $this->addSql('CREATE TABLE IF NOT EXISTS sale (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, amount DOUBLE PRECISION DEFAULT NULL, contact_info VARCHAR(255) DEFAULT NULL, location VARCHAR(255) DEFAULT NULL, image VARCHAR(255) DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, is_active TINYINT DEFAULT 1 NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        
        $this->addSql('CREATE TABLE IF NOT EXISTS sale_image (id INT AUTO_INCREMENT NOT NULL, path VARCHAR(255) NOT NULL, sort_order INT NOT NULL, is_primary TINYINT DEFAULT 0 NOT NULL, sale_id INT NOT NULL, INDEX IDX_15E15CDC4A7E4868 (sale_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        
        $this->addSql('CREATE TABLE IF NOT EXISTS sale_item (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, price DOUBLE PRECISION NOT NULL, type LONGTEXT NOT NULL, sale_id INT NOT NULL, INDEX IDX_A35551FB4A7E4868 (sale_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        
        $this->addSql('CREATE TABLE IF NOT EXISTS exchane_item (id INT AUTO_INCREMENT NOT NULL, description VARCHAR(255) DEFAULT NULL, estimated_value DOUBLE PRECISION DEFAULT NULL, quantity INT DEFAULT NULL, sale_id INT NOT NULL, INDEX IDX_494359624A7E4868 (sale_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event_sponsor DROP FOREIGN KEY FK_4DB607B71F7E88B');
        $this->addSql('ALTER TABLE event_sponsor DROP FOREIGN KEY FK_4DB607B12F7FB51');
        $this->addSql('ALTER TABLE event_review DROP FOREIGN KEY FK_4BDAF69471F7E88B');
        $this->addSql('ALTER TABLE exchane_item DROP FOREIGN KEY FK_494359624A7E4868');
        $this->addSql('ALTER TABLE password_reset_token DROP FOREIGN KEY FK_6B7BA4B6A76ED395');
        $this->addSql('ALTER TABLE sale_image DROP FOREIGN KEY FK_15E15CDC4A7E4868');
        $this->addSql('ALTER TABLE sale_item DROP FOREIGN KEY FK_A35551FB4A7E4868');
        $this->addSql('ALTER TABLE sponsor_contract DROP FOREIGN KEY FK_711766EB12F7FB51');
        $this->addSql('ALTER TABLE sponsorship DROP FOREIGN KEY FK_C0F10CD412F7FB51');
        $this->addSql('ALTER TABLE ticket DROP FOREIGN KEY FK_97A0ADA371F7E88B');
        $this->addSql('DROP TABLE event');
        $this->addSql('DROP TABLE event_sponsor');
        $this->addSql('DROP TABLE event_review');
        $this->addSql('DROP TABLE exchane_item');
        $this->addSql('DROP TABLE password_reset_token');
        $this->addSql('DROP TABLE reclamation');
        $this->addSql('DROP TABLE sale');
        $this->addSql('DROP TABLE sale_image');
        $this->addSql('DROP TABLE sale_item');
        $this->addSql('DROP TABLE sponsor');
        $this->addSql('DROP TABLE sponsor_contract');
        $this->addSql('DROP TABLE sponsorship');
        $this->addSql('DROP TABLE ticket');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
