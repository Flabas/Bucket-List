<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250901111000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed des catégories de base (Travel & Adventure, Sport, Entertainment, Human Relations, Others).';
    }

    public function up(Schema $schema): void
    {
        // Requêtes portables (fonctionnent sur MySQL/MariaDB/PostgreSQL/SQLite)
        $this->addSql("INSERT INTO category (name) SELECT 'Travel & Adventure' WHERE NOT EXISTS (SELECT 1 FROM category WHERE name = 'Travel & Adventure')");
        $this->addSql("INSERT INTO category (name) SELECT 'Sport' WHERE NOT EXISTS (SELECT 1 FROM category WHERE name = 'Sport')");
        $this->addSql("INSERT INTO category (name) SELECT 'Entertainment' WHERE NOT EXISTS (SELECT 1 FROM category WHERE name = 'Entertainment')");
        $this->addSql("INSERT INTO category (name) SELECT 'Human Relations' WHERE NOT EXISTS (SELECT 1 FROM category WHERE name = 'Human Relations')");
        $this->addSql("INSERT INTO category (name) SELECT 'Others' WHERE NOT EXISTS (SELECT 1 FROM category WHERE name = 'Others')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM category WHERE name IN ('Travel & Adventure','Sport','Entertainment','Human Relations','Others')");
    }
}
