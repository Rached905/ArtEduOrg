<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create app_settings table for admin-configurable application parameters.
 */
final class Version20251218100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create app_settings table for admin paramètres';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE app_settings (
            id INT AUTO_INCREMENT NOT NULL,
            site_name VARCHAR(255) DEFAULT NULL,
            contact_email VARCHAR(255) DEFAULT NULL,
            maintenance_mode TINYINT(1) DEFAULT 0 NOT NULL,
            footer_text LONGTEXT DEFAULT NULL,
            items_per_page INT DEFAULT 12 NOT NULL,
            meta_description VARCHAR(500) DEFAULT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE app_settings');
    }
}
