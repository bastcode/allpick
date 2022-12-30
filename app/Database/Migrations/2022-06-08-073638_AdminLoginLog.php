<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AdminLoginLog extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'admin_login_log_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'admin_account_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => false,
                'comment'=> '관리자 계_정 idx'
            ],
            'nickname' => [
                'type' => 'varchar',
                'constraint' => '255',
                'comment'=> '이름'
            ],
            'action' => [
                'type' => 'varchar',
                'constraint' => '255',
                'comment'=> '무슨 행동을 했나'
            ],
            'created_at datetime default current_timestamp',
        ]);

        $this->forge->addPrimaryKey('admin_login_log_id');
        $this->forge->createTable('admin_login_log');
    }

    public function down()
    {
        $this->forge->dropTable('admin_login_log');
    }
}
