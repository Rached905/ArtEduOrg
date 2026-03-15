<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add notification and sale_favorite tables for notifications and œuvres favoris.
 */
final class Version20260315120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add notification and sale_favorite tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, sale_id INT NOT NULL, type VARCHAR(32) NOT NULL, read_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_BF5476CA_A76ED395 (user_id), INDEX IDX_BF5476CA4A7E4868 (sale_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA_A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA4A7E4868 FOREIGN KEY (sale_id) REFERENCES sale (id) ON DELETE CASCADE');

        $this->addSql('CREATE TABLE sale_favorite (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, sale_id INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX user_sale_unique (user_id, sale_id), INDEX IDX_12345_A76ED395 (user_id), INDEX IDX_12345_4A7E4868 (sale_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE sale_favorite ADD CONSTRAINT FK_12345_A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sale_favorite ADD CONSTRAINT FK_12345_4A7E4868 FOREIGN KEY (sale_id) REFERENCES sale (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CA_A76ED395');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CA4A7E4868');
        $this->addSql('DROP TABLE notification');
        $this->addSql('ALTER TABLE sale_favorite DROP FOREIGN KEY FK_12345_A76ED395');
        $this->addSql('ALTER TABLE sale_favorite DROP FOREIGN KEY FK_12345_4A7E4868');
        $this->addSql('DROP TABLE sale_favorite');
    }
}
