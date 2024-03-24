<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240323223511 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add full-text index to content column in tag_translations table';
    }

    public function up(Schema $schema): void
    {
        // Adding a full-text index to the 'content' column of the 'tag_translations' table
        $this->addSql('ALTER TABLE tag_translations ADD FULLTEXT idx_fulltext_content (content)');
    }

    public function down(Schema $schema): void
    {
        // Removing the full-text index from the 'content' column of the 'tag_translations' table
        $this->addSql('ALTER TABLE tag_translations DROP INDEX idx_fulltext_content');
    }
}
