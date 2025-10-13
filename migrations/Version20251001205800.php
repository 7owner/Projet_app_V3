<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251001205800 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE rendez_vous_image (id INT AUTO_INCREMENT NOT NULL, rendez_vous_id INT NOT NULL, image_id INT NOT NULL, INDEX IDX_89DE2C8E91EF7EAA (rendez_vous_id), INDEX IDX_89DE2C8E3DA5256D (image_id), UNIQUE INDEX rendez_vous_image_unique (rendez_vous_id, image_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE rendez_vous_image ADD CONSTRAINT FK_89DE2C8E91EF7EAA FOREIGN KEY (rendez_vous_id) REFERENCES rendezvous (id)');
        $this->addSql('ALTER TABLE rendez_vous_image ADD CONSTRAINT FK_89DE2C8E3DA5256D FOREIGN KEY (image_id) REFERENCES images (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE rendez_vous_image DROP FOREIGN KEY FK_89DE2C8E91EF7EAA');
        $this->addSql('ALTER TABLE rendez_vous_image DROP FOREIGN KEY FK_89DE2C8E3DA5256D');
        $this->addSql('DROP TABLE rendez_vous_image');
    }
}
