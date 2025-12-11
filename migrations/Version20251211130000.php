<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251211130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des contraintes UNIQUE sur le champ email des tables client et user';
    }

    public function up(Schema $schema): void
    {
        // Ajout de la contrainte UNIQUE sur client.email
        $this->addSql('ALTER TABLE client ADD CONSTRAINT UNIQ_CLIENT_EMAIL UNIQUE (email)');

        // Ajout de la contrainte UNIQUE sur user.email
        $this->addSql('ALTER TABLE user ADD CONSTRAINT UNIQ_USER_EMAIL UNIQUE (email)');
    }

    public function down(Schema $schema): void
    {
        // Suppression de la contrainte UNIQUE sur client.email
        $this->addSql('ALTER TABLE client DROP INDEX UNIQ_CLIENT_EMAIL');

        // Suppression de la contrainte UNIQUE sur user.email
        $this->addSql('ALTER TABLE user DROP INDEX UNIQ_USER_EMAIL');
    }
}
