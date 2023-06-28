<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230628093433 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_81398E099395C3F3 ON customer');
        $this->addSql('ALTER TABLE customer ADD last_name VARCHAR(255) DEFAULT NULL, DROP customer_id, CHANGE customer_name first_name VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE customer ADD customer_id INT NOT NULL, ADD customer_name VARCHAR(255) DEFAULT NULL, DROP first_name, DROP last_name');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_81398E099395C3F3 ON customer (customer_id)');
    }
}
