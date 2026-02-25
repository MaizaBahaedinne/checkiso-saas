<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Creates the action_plans table.
 *
 * An action plan is a corrective task tied to a tenant, optionally linked to a
 * specific Gap session and/or control from the ISO hierarchy.
 */
class CreateActionPlans extends Migration
{
    public function up(): void
    {
        $this->db->query("
            CREATE TABLE action_plans (
                id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                tenant_id       BIGINT UNSIGNED NOT NULL,
                gap_session_id  BIGINT UNSIGNED NULL,
                control_id      BIGINT UNSIGNED NULL,
                title           VARCHAR(255)    NOT NULL,
                description     TEXT            NULL,
                owner_user_id   BIGINT UNSIGNED NULL,
                due_date        DATE            NULL,
                priority        ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
                status          ENUM('todo','in_progress','done') NOT NULL DEFAULT 'todo',
                created_by      BIGINT UNSIGNED NOT NULL,
                created_at      DATETIME        NULL,
                updated_at      DATETIME        NULL,
                deleted_at      DATETIME        NULL,
                PRIMARY KEY (id),
                INDEX idx_ap_tenant   (tenant_id),
                INDEX idx_ap_session  (gap_session_id),
                INDEX idx_ap_status   (status),
                INDEX idx_ap_owner    (owner_user_id),
                CONSTRAINT fk_ap_tenant      FOREIGN KEY (tenant_id)      REFERENCES tenants(id)      ON DELETE CASCADE,
                CONSTRAINT fk_ap_session     FOREIGN KEY (gap_session_id) REFERENCES gap_sessions(id) ON DELETE SET NULL,
                CONSTRAINT fk_ap_control     FOREIGN KEY (control_id)     REFERENCES controls(id)     ON DELETE SET NULL,
                CONSTRAINT fk_ap_owner       FOREIGN KEY (owner_user_id)  REFERENCES users(id)        ON DELETE SET NULL,
                CONSTRAINT fk_ap_created_by  FOREIGN KEY (created_by)     REFERENCES users(id)        ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(): void
    {
        $this->db->query('DROP TABLE IF EXISTS action_plans');
    }
}
