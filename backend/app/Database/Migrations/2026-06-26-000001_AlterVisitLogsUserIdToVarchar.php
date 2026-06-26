<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterVisitLogsUserIdToVarchar extends Migration
{
    public function up(): void
    {
        $this->forge->modifyColumn('visit_logs', [
            'user_id' => [
                'name'       => 'user_id',
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
                'default'    => null,
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->modifyColumn('visit_logs', [
            'user_id' => [
                'name'       => 'user_id',
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
                'default'    => null,
            ],
        ]);
    }
}
