<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateStandardsCatalog extends Migration
{
    public function up()
    {
        // Standards catalog is global (not tenant-scoped)
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('code', false, true);
        $this->forge->createTable('standards', true, ['ENGINE' => 'InnoDB']);

        // Versioning (e.g. ISO27001 2022)
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'standard_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'version' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'published_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['standard_id', 'version'], false, true);
        $this->forge->addKey(['standard_id', 'is_active']);
        $this->forge->addForeignKey('standard_id', 'standards', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('standard_versions', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('standard_versions', true);
        $this->forge->dropTable('standards', true);
    }
}
