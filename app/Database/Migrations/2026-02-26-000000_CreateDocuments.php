<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Creates the wiki-style documents table and its version history table.
 */
class CreateDocuments extends Migration
{
    public function up(): void
    {
        $this->db->disableForeignKeyChecks();

        // ── documents ────────────────────────────────────────────────────────
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id'       => ['type' => 'BIGINT',   'constraint' => 20, 'unsigned' => true],
            'title'           => ['type' => 'VARCHAR',  'constraint' => 255],
            'slug'            => ['type' => 'VARCHAR',  'constraint' => 255],
            'category'        => ['type' => 'ENUM', 'constraint' => ['policy','procedure','guide','reference','template','other'], 'default' => 'other'],
            'description'     => ['type' => 'TEXT',     'null' => true, 'default' => null],
            'content'         => ['type' => 'LONGTEXT', 'null' => true, 'default' => null],
            'current_version' => ['type' => 'SMALLINT', 'unsigned' => true, 'default' => 1],
            'created_by'      => ['type' => 'BIGINT',   'constraint' => 20, 'unsigned' => true, 'null' => true, 'default' => null],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('tenant_id');
        $this->forge->addKey('slug');
        $this->forge->addForeignKey('tenant_id',  'tenants', 'id', 'CASCADE',  'CASCADE');
        $this->forge->addForeignKey('created_by', 'users',   'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('documents', true, ['ENGINE' => 'InnoDB']);

        // ── document_versions ────────────────────────────────────────────────
        $this->forge->addField([
            'id'             => ['type' => 'BIGINT',   'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'document_id'    => ['type' => 'BIGINT',   'constraint' => 20, 'unsigned' => true],
            'version_number' => ['type' => 'SMALLINT', 'unsigned' => true],
            'title'          => ['type' => 'VARCHAR',  'constraint' => 255],
            'content'        => ['type' => 'LONGTEXT', 'null' => true, 'default' => null],
            'change_summary' => ['type' => 'VARCHAR',  'constraint' => 500, 'null' => true, 'default' => null],
            'changed_by'     => ['type' => 'BIGINT',   'constraint' => 20, 'unsigned' => true, 'null' => true, 'default' => null],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['document_id', 'version_number']);
        $this->forge->addForeignKey('document_id', 'documents', 'id', 'CASCADE',  'CASCADE');
        $this->forge->addForeignKey('changed_by',  'users',     'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('document_versions', true, ['ENGINE' => 'InnoDB']);

        $this->db->enableForeignKeyChecks();
    }

    public function down(): void
    {
        $this->db->disableForeignKeyChecks();
        $this->forge->dropTable('document_versions', true);
        $this->forge->dropTable('documents', true);
        $this->db->enableForeignKeyChecks();
    }
}
