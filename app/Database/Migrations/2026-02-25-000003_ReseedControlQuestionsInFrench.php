<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Reseeds control_questions and control_choices with French content.
 *
 * The original seed (000002_SeedControlQuestions) was applied when the
 * question text was still in English. This migration:
 *   1. Clears all quiz + gap data (FK-safe via FOREIGN_KEY_CHECKS=0)
 *   2. Re-invokes migration 000002's up() which is now fully in French
 */
class ReseedControlQuestionsInFrench extends Migration
{
    public function up(): void
    {
        // ── Step 1 : clear existing data ───────────────────────────────────
        $this->db->query('SET FOREIGN_KEY_CHECKS = 0');
        $this->db->table('gap_answers')->truncate();
        $this->db->table('gap_sessions')->truncate();
        $this->db->table('control_choices')->truncate();
        $this->db->table('control_questions')->truncate();
        $this->db->query('SET FOREIGN_KEY_CHECKS = 1');

        // ── Step 2 : re-run the French seed from migration 000002 ──────────
        // We instantiate SeedControlQuestions without triggering its
        // constructor (which would open a second DB connection) and inject
        // our current $this->db via reflection.
        $seederClass = SeedControlQuestions::class;
        $reflection  = new \ReflectionClass($seederClass);
        $seeder      = $reflection->newInstanceWithoutConstructor();

        // Inject the shared DB connection (property lives on the parent class)
        $dbProp = $reflection->getParentClass()->getProperty('db');
        $dbProp->setAccessible(true);
        $dbProp->setValue($seeder, $this->db);

        $seeder->up();
    }

    public function down(): void
    {
        // Reverting this reseed is the same as reverting the original seed
        $this->db->query('SET FOREIGN_KEY_CHECKS = 0');
        $this->db->table('gap_answers')->truncate();
        $this->db->table('gap_sessions')->truncate();
        $this->db->table('control_choices')->truncate();
        $this->db->table('control_questions')->truncate();
        $this->db->query('SET FOREIGN_KEY_CHECKS = 1');
    }
}
