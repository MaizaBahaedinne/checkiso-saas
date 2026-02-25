<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Creates the tenant_standards pivot table.
 * Allows an organisation to subscribe to one or more standard versions.
 */
class CreateTenantStandards extends Migration
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
            'subscribed_by' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
                'comment'  => 'user_id who added this standard',
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
        $this->forge->addKey(['tenant_id', 'standard_version_id'], false, true);
        $this->forge->addForeignKey('tenant_id',           'tenants',           'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('standard_version_id', 'standard_versions', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('tenant_standards', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('tenant_standards', true);
    }
}
