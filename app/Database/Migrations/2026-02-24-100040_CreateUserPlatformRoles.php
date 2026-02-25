<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Creates a user_platform_roles table for platform-scoped roles
 * (e.g. platform.admin) that are not tied to any specific tenant.
 *
 * Also seeds the platform.admin role and assigns it to user ID 1
 * (the very first registered user / super admin).
 */
class CreateUserPlatformRoles extends Migration
{
    public function up()
    {
        // ---- Table ---------------------------------------------------------
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'role_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['user_id', 'role_id'], false, true);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('role_id', 'roles', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('user_platform_roles', true, ['ENGINE' => 'InnoDB']);

        // ---- Seed platform.admin role -------------------------------------
        $now = date('Y-m-d H:i:s');
        $this->db->table('roles')->insert([
            'scope'       => 'platform',
            'code'        => 'platform.admin',
            'name'        => 'Platform Administrator',
            'description' => 'Full access to the CheckISO platform: all tenants, users, and configuration.',
            'created_at'  => $now,
            'updated_at'  => $now,
        ]);

        // ---- Assign platform.admin to the first user (ID = 1) ------------
        $role = $this->db->table('roles')
            ->where('code', 'platform.admin')
            ->get()->getRowArray();

        $firstUser = $this->db->table('users')
            ->orderBy('id', 'ASC')
            ->limit(1)
            ->get()->getRowArray();

        if ($role && $firstUser) {
            $this->db->table('user_platform_roles')->insert([
                'user_id'    => $firstUser['id'],
                'role_id'    => $role['id'],
                'created_at' => $now,
            ]);
        }
    }

    public function down()
    {
        $this->db->table('user_platform_roles')->truncate();
        $this->forge->dropTable('user_platform_roles', true);
        $this->db->table('roles')->where('code', 'platform.admin')->delete();
    }
}
