<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMemberships extends Migration
{
    public function up()
    {
        // Links a user to a tenant (and optionally to a company/org unit).
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
            'user_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'company_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'org_unit_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'active',
            ],
            'is_default' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
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
        $this->forge->addKey(['tenant_id', 'user_id'], false, true);
        $this->forge->addKey(['tenant_id', 'company_id']);
        $this->forge->addKey(['tenant_id', 'org_unit_id']);

        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('org_unit_id', 'org_units', 'id', 'SET NULL', 'CASCADE');

        $this->forge->createTable('memberships', true, ['ENGINE' => 'InnoDB']);

        // Role assignment per membership (tenant context)
        $this->forge->addField([
            'membership_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'role_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
        ]);

        $this->forge->addKey(['membership_id', 'role_id'], true);
        $this->forge->addForeignKey('membership_id', 'memberships', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('role_id', 'roles', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('membership_roles', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('membership_roles', true);
        $this->forge->dropTable('memberships', true);
    }
}
