<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Drop old control_assessments and create the quiz-based gap tables:
 *
 *  gap_sessions  — one session per (tenant, standard_version); tracks overall state
 *  gap_answers   — one answer per (session, control); stores chosen option, score, justification
 *  control_questions — quiz questions for each control (global catalogue)
 *  control_choices   — answer choices per question (with trap flags, score weights, manual-review flag)
 */
class CreateGapQuizTables extends Migration
{
    public function up()
    {
        // ── Drop old table if it exists ────────────────────────────────────
        $this->forge->dropTable('control_assessments', true);

        // ── control_questions ─────────────────────────────────────────────
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'control_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'question'   => ['type' => 'TEXT'],         // The question text shown to the user
            'hint'       => ['type' => 'TEXT', 'null' => true], // Optional guidance / context
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('control_id', false, true); // one question per control
        $this->forge->addForeignKey('control_id', 'controls', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('control_questions', true, ['ENGINE' => 'InnoDB']);

        // ── control_choices ───────────────────────────────────────────────
        $this->forge->addField([
            'id'          => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'question_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'choice_key'  => ['type' => 'VARCHAR', 'constraint' => 10],   // a, b, c, d, other
            'label'       => ['type' => 'VARCHAR', 'constraint' => 500],  // The answer text
            // Score contribution when this choice is selected (0-100)
            'score_pct'   => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
            // Derived compliance status for this choice
            'status'      => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'non_conforme'],
            // This choice is intentionally misleading (trap)
            'is_trap'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            // User must provide a written justification if they pick this
            'requires_justification' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            // Needs manual review by an admin (e.g. "Autre / Other" option)
            'is_manual_review'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'sort_order'  => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['question_id', 'choice_key'], false, true);
        $this->forge->addForeignKey('question_id', 'control_questions', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('control_choices', true, ['ENGINE' => 'InnoDB']);

        // ── gap_sessions ──────────────────────────────────────────────────
        $this->forge->addField([
            'id'                  => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'tenant_id'           => ['type' => 'BIGINT', 'unsigned' => true],
            'standard_version_id' => ['type' => 'BIGINT', 'unsigned' => true],
            // draft | submitted
            'status'              => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'draft'],
            'total_controls'      => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'answered_controls'   => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'score'               => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true],
            'submitted_by'        => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'submitted_at'        => ['type' => 'DATETIME', 'null' => true],
            'created_at'          => ['type' => 'DATETIME', 'null' => true],
            'updated_at'          => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'standard_version_id'], false, true);
        $this->forge->addForeignKey('tenant_id',           'tenants',           'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('standard_version_id', 'standard_versions', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('gap_sessions', true, ['ENGINE' => 'InnoDB']);

        // ── gap_answers ───────────────────────────────────────────────────
        $this->forge->addField([
            'id'            => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'session_id'    => ['type' => 'BIGINT', 'unsigned' => true],
            'control_id'    => ['type' => 'BIGINT', 'unsigned' => true],
            'choice_id'     => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'choice_key'    => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            // Computed score at save time (copied from choice)
            'score_pct'     => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true],
            // Computed status at save time
            'status'        => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            // Justification text (required when choice.requires_justification = 1)
            'justification' => ['type' => 'TEXT', 'null' => true],
            // Manual text when "Autre" is chosen
            'other_text'    => ['type' => 'TEXT', 'null' => true],
            // Needs manual review
            'is_manual_review' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'answered_by'   => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['session_id', 'control_id'], false, true);
        $this->forge->addForeignKey('session_id',  'gap_sessions',      'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('control_id',  'controls',          'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('choice_id',   'control_choices',   'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('gap_answers', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('gap_answers',        true);
        $this->forge->dropTable('gap_sessions',       true);
        $this->forge->dropTable('control_choices',    true);
        $this->forge->dropTable('control_questions',  true);
        // Restore old simple table
        $this->forge->addField([
            'id'                  => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'tenant_id'           => ['type' => 'BIGINT', 'unsigned' => true],
            'standard_version_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'control_id'          => ['type' => 'BIGINT', 'unsigned' => true],
            'status'              => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'notes'               => ['type' => 'TEXT', 'null' => true],
            'assessed_by'         => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'assessed_at'         => ['type' => 'DATETIME', 'null' => true],
            'created_at'          => ['type' => 'DATETIME', 'null' => true],
            'updated_at'          => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'control_id'], false, true);
        $this->forge->createTable('control_assessments', true, ['ENGINE' => 'InnoDB']);
    }
}
