<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDocuments extends Migration
{
    public function up(): void
    {
        $this->db->disableForeignKeyChecks();

        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'tenant_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'description' => [
                'type'    => 'TEXT',
                'null'    => true,
                'default' => null,
            ],
            'category' => [
                'type'       => 'ENUM',
                'constraint' => ['policy', 'procedure', 'evidence', 'template', 'other'],
                'default'    => 'other',
            ],
            'file_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'file_path' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
            ],
            'file_size' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'default'  => 0,
            ],
            'mime_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'default'    => null,
            ],
            'linked_control_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
                'default'  => null,
            ],
            'linked_action_plan_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
                'default'  => null,
            ],
            'uploaded_by' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
                'default'  => null,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('tenant_id');
        $this->forge->addKey('category');
        $this->forge->addKey('linked_control_id');
        $this->forge->addKey('linked_action_plan_id');

        $this->forge->addForeignKey('tenant_id',              'tenants',      'id', 'CASCADE',  'CASCADE');
        $this->forge->addForeignKey('uploaded_by',            'users',        'id', 'SET NULL', 'SET NULL');
        $this->forge->addForeignKey('linked_control_id',      'controls',     'id', 'SET NULL', 'SET NULL');
        $this->forge->addForeignKey('linked_action_plan_id',  'action_plans', 'id', 'SET NULL', 'SET NULL');

        $this->forge->createTable('documents', true, ['ENGINE' => 'InnoDB']);

        $this->db->enableForeignKeyChecks();
    }

    public function down(): void
    {
        $this->db->disableForeignKeyChecks();
        $this->forge->dropTable('documents', true);
        $this->db->enableForeignKeyChecks();
    }
}
