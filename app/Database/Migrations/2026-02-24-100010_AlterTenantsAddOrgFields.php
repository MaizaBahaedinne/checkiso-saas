<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterTenantsAddOrgFields extends Migration
{
    public function up()
    {
        // Enrich tenants with organisation details
        $this->forge->addColumn('tenants', [
            'sector' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'slug',
            ],
            'employees_range' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
                'comment'    => '1-10|11-50|51-200|201-500|500+',
                'after'      => 'sector',
            ],
            'address_line' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'employees_range',
            ],
            'city' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'address_line',
            ],
            'postal_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
                'after'      => 'city',
            ],
            'country_code' => [
                'type'       => 'CHAR',
                'constraint' => 2,
                'null'       => true,
                'after'      => 'postal_code',
            ],
            'website' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'country_code',
            ],
            'contact_email' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'website',
            ],
        ]);

        // Join requests: user asks to join an existing tenant
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
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'pending',
                'comment'    => 'pending|approved|rejected',
            ],
            'message' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'reviewed_by' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'reviewed_at' => [
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
        $this->forge->addKey(['tenant_id', 'user_id', 'status']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('join_requests', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('join_requests', true);
        $this->forge->dropColumn('tenants', [
            'sector', 'employees_range', 'address_line',
            'city', 'postal_code', 'country_code', 'website', 'contact_email',
        ]);
    }
}
