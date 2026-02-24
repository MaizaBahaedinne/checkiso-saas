<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Seeds the two built-in tenant roles (org.admin, org.member)
 * and assigns org.admin to every existing membership that is
 * already marked as is_default = 1 (i.e. org creators).
 */
class SeedBaseRoles extends Migration
{
    public function up()
    {
        $now = date('Y-m-d H:i:s');

        $this->db->table('roles')->insertBatch([
            [
                'scope'       => 'tenant',
                'code'        => 'org.admin',
                'name'        => 'Organisation Admin',
                'description' => 'Full control over the organisation settings, members, and standards.',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'scope'       => 'tenant',
                'code'        => 'org.member',
                'name'        => 'Organisation Member',
                'description' => 'Read and contribute access within the organisation.',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
        ]);

        // Assign org.admin to every membership created by the org founder
        // (is_default = 1 means they were the first member / creator)
        $adminRole = $this->db->table('roles')->where('code', 'org.admin')->get()->getRowArray();
        if (! $adminRole) {
            return;
        }

        $founderMemberships = $this->db->table('memberships')
            ->where('is_default', 1)
            ->get()->getResultArray();

        foreach ($founderMemberships as $m) {
            // Skip if already has a role assigned
            $exists = $this->db->table('membership_roles')
                ->where('membership_id', $m['id'])
                ->countAllResults();
            if ($exists === 0) {
                $this->db->table('membership_roles')->insert([
                    'membership_id' => $m['id'],
                    'role_id'       => $adminRole['id'],
                ]);
            }
        }
    }

    public function down()
    {
        // Remove role assignments for org.admin and org.member
        $roles = $this->db->table('roles')
            ->whereIn('code', ['org.admin', 'org.member'])
            ->get()->getResultArray();

        foreach ($roles as $r) {
            $this->db->table('membership_roles')->where('role_id', $r['id'])->delete();
        }

        $this->db->table('roles')->whereIn('code', ['org.admin', 'org.member'])->delete();
    }
}
