<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251125114511 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE evenement (id UUID NOT NULL, type_event_id INT NOT NULL, photo_id INT DEFAULT NULL, titre VARCHAR(255) NOT NULL, description TEXT NOT NULL, debut DATE DEFAULT NULL, fin DATE DEFAULT NULL, date_publication TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_B26681EBC08CF77 ON evenement (type_event_id)');
        $this->addSql('CREATE INDEX IDX_B26681E7E9E4C8C ON evenement (photo_id)');
        $this->addSql('COMMENT ON COLUMN evenement.id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE evenement ADD CONSTRAINT FK_B26681EBC08CF77 FOREIGN KEY (type_event_id) REFERENCES type_event (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE evenement ADD CONSTRAINT FK_B26681E7E9E4C8C FOREIGN KEY (photo_id) REFERENCES photo (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE evenement DROP CONSTRAINT FK_B26681EBC08CF77');
        $this->addSql('ALTER TABLE evenement DROP CONSTRAINT FK_B26681E7E9E4C8C');
        $this->addSql('DROP TABLE evenement');
    }
}
