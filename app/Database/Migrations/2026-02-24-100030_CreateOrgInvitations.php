<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Creates the org_invitations table.
 * A tenant admin sends an invitation to an email address.
 * The recipient clicks the unique token link to accept and join.
 */
class CreateOrgInvitations extends Migration
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
            'invited_by' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'comment'  => 'user_id of the sender',
            ],
            'email' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'role_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 80,
                'default'    => 'org.member',
            ],
            'token' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'comment'    => 'Unique random hex token',
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'pending',
                'comment'    => 'pending|accepted|cancelled|expired',
            ],
            'expires_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'accepted_at' => [
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
        $this->forge->addKey('token', false, true);
        $this->forge->addKey(['tenant_id', 'email']);
        $this->forge->addForeignKey('tenant_id',   'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('invited_by',  'users',   'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('org_invitations', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('org_invitations', true);
    }
}
