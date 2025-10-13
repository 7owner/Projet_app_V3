<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251001190918 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE rendez_vous DROP FOREIGN KEY FK_65E8AA0A8EAE3863');
        $this->addSql('ALTER TABLE rendez_vous DROP FOREIGN KEY FK_65E8AA0AF6BD1646');
        $this->addSql('ALTER TABLE rendez_vous_image DROP FOREIGN KEY FK_89DE2C8E3DA5256D');
        $this->addSql('ALTER TABLE rendez_vous_image DROP FOREIGN KEY FK_89DE2C8E91EF7EAA');
        $this->addSql('DROP TABLE rendez_vous');
        $this->addSql('DROP TABLE rendez_vous_image');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE rendez_vous (id INT AUTO_INCREMENT NOT NULL, site_id INT NOT NULL, intervention_id INT DEFAULT NULL, sujet VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, date_rdv DATE NOT NULL, heure_rdv TIME DEFAULT NULL, description LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, statut VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'Planifie\' NOT NULL COLLATE `utf8mb4_unicode_ci`, date_debut DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, date_fin DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, INDEX IDX_65E8AA0A8EAE3863 (intervention_id), INDEX IDX_65E8AA0AF6BD1646 (site_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE rendez_vous_image (id INT AUTO_INCREMENT NOT NULL, rendez_vous_id INT NOT NULL, image_id INT NOT NULL, INDEX IDX_89DE2C8E3DA5256D (image_id), INDEX IDX_89DE2C8E91EF7EAA (rendez_vous_id), UNIQUE INDEX rendez_vous_image_unique (rendez_vous_id, image_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE rendez_vous ADD CONSTRAINT FK_65E8AA0A8EAE3863 FOREIGN KEY (intervention_id) REFERENCES intervention (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE rendez_vous ADD CONSTRAINT FK_65E8AA0AF6BD1646 FOREIGN KEY (site_id) REFERENCES site (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE rendez_vous_image ADD CONSTRAINT FK_89DE2C8E3DA5256D FOREIGN KEY (image_id) REFERENCES images (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE rendez_vous_image ADD CONSTRAINT FK_89DE2C8E91EF7EAA FOREIGN KEY (rendez_vous_id) REFERENCES rendez_vous (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
