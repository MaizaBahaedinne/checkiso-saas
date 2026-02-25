<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds lang_preference column to users table.
 * Stores the user's preferred UI language ('fr' or 'en').
 * Replaces the session-only language mechanism with a persistent preference.
 */
class AlterUsersAddLangPreference extends Migration
{
    public function up(): void
    {
        $this->db->query(
            "ALTER TABLE users
             ADD COLUMN lang_preference VARCHAR(5) NOT NULL DEFAULT 'fr'
             AFTER last_login_at"
        );
    }

    public function down(): void
    {
        $this->db->query('ALTER TABLE users DROP COLUMN lang_preference');
    }
}
