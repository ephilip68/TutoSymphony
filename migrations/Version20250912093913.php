<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250912093913 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE newsletter_subscriber (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE recipe DROP FOREIGN KEY FK_DA88B137F675F31B');
        $this->addSql('ALTER TABLE recipe CHANGE author_id author_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE recipe ADD CONSTRAINT FK_DA88B137F675F31B FOREIGN KEY (author_id) REFERENCES user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE newsletter_subscriber');
        $this->addSql('ALTER TABLE recipe DROP FOREIGN KEY FK_DA88B137F675F31B');
        $this->addSql('ALTER TABLE recipe CHANGE author_id author_id INT NOT NULL');
        $this->addSql('ALTER TABLE recipe ADD CONSTRAINT FK_DA88B137F675F31B FOREIGN KEY (author_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
