<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * One assessment row per (tenant, control).
 * Unique key on (tenant_id, control_id) enforced at DB level.
 * Scores: conforme=100, partiel=50, non_conforme=0, na=excluded.
 */
class CreateControlAssessments extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'tenant_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'standard_version_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'control_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            // conforme | partiel | non_conforme | na
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'assessed_by' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'assessed_at' => [
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
        // One assessment per tenant+control
        $this->forge->addKey(['tenant_id', 'control_id'], false, true);
        $this->forge->addKey(['tenant_id', 'standard_version_id']);
        $this->forge->addForeignKey('tenant_id',           'tenants',           'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('standard_version_id', 'standard_versions', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('control_id',          'controls',          'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('control_assessments', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('control_assessments', true);
    }
}
