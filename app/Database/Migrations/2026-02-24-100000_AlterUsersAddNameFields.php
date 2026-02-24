<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterUsersAddNameFields extends Migration
{
    public function up()
    {
        // Add first_name and last_name, drop display_name
        $this->forge->addColumn('users', [
            'first_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'email',
            ],
            'last_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'first_name',
            ],
        ]);

        $this->forge->dropColumn('users', 'display_name');
    }

    public function down()
    {
        $this->forge->addColumn('users', [
            'display_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
                'after'      => 'email',
            ],
        ]);

        $this->forge->dropColumn('users', ['first_name', 'last_name']);
    }
}
