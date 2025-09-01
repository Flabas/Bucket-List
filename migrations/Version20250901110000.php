<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250901110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Category entity and relation to Wish; add image column to wish if missing.';
    }

    public function up(Schema $schema): void
    {
        // Create category table if not exists
        if (!$schema->hasTable('category')) {
            $category = $schema->createTable('category');
            $category->addColumn('id', 'integer', ['autoincrement' => true]);
            $category->addColumn('name', 'string', ['length' => 50, 'notnull' => true]);
            $category->setPrimaryKey(['id']);
            $category->addUniqueIndex(['name'], 'UNIQ_CATEGORY_NAME');
        }

        // Ensure wish table has needed columns/relations
        if ($schema->hasTable('wish')) {
            $wish = $schema->getTable('wish');

            if (!$wish->hasColumn('image')) {
                $wish->addColumn('image', 'string', ['length' => 255, 'notnull' => false]);
            }

            if (!$wish->hasColumn('category_id')) {
                // nullable pour compatibilité avec des données existantes
                $wish->addColumn('category_id', 'integer', ['notnull' => false]);
            }

            // Add index for FK if missing
            if (!$wish->hasIndex('IDX_WISH_CATEGORY')) {
                $wish->addIndex(['category_id'], 'IDX_WISH_CATEGORY');
            }

            // Add FK if missing
            $hasFk = false;
            foreach ($wish->getForeignKeys() as $fk) {
                if ($fk->getLocalColumns() === ['category_id'] && $fk->getForeignTableName() === 'category') {
                    $hasFk = true;
                    break;
                }
            }
            if (!$hasFk) {
                // ON DELETE SET NULL pour éviter les erreurs si une catégorie est supprimée
                $wish->addForeignKeyConstraint('category', ['category_id'], ['id'], ['onDelete' => 'SET NULL'], 'FK_WISH_CATEGORY');
            }
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('wish')) {
            $wish = $schema->getTable('wish');

            // Drop FK if exists
            foreach ($wish->getForeignKeys() as $fkName => $fk) {
                if ($fk->getLocalColumns() === ['category_id'] && $fk->getForeignTableName() === 'category') {
                    $wish->removeForeignKey($fkName);
                }
            }

            if ($wish->hasIndex('IDX_WISH_CATEGORY')) {
                $wish->dropIndex('IDX_WISH_CATEGORY');
            }

            if ($wish->hasColumn('category_id')) {
                $wish->dropColumn('category_id');
            }

            // Ne pas supprimer la colonne image si elle existait avant cette migration
            // (on ne peut pas le savoir ici sans état précédent fiable). Nous la laissons en place.
        }

        if ($schema->hasTable('category')) {
            $schema->dropTable('category');
        }
    }
}
