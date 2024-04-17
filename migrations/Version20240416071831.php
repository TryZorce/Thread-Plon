<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240416071831 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE category CHANGE crated_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE response DROP FOREIGN KEY FK_response_thread');
        $this->addSql('ALTER TABLE response DROP FOREIGN KEY FK_response_user');
        $this->addSql('ALTER TABLE thread DROP FOREIGN KEY FK_thread_user');
        $this->addSql('ALTER TABLE thread_category DROP FOREIGN KEY FK_thread_category_category');
        $this->addSql('ALTER TABLE thread_category DROP FOREIGN KEY FK_thread_category_thread');
        $this->addSql('ALTER TABLE vote DROP FOREIGN KEY FK_vote_response');
        $this->addSql('ALTER TABLE vote DROP FOREIGN KEY FK_vote_user');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE response ADD CONSTRAINT FK_response_thread FOREIGN KEY (thread_id_id) REFERENCES thread (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE category CHANGE created_at crated_at DATETIME NOT NULL');
    }
}
