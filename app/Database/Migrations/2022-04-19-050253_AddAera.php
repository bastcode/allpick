<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAera extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'aera_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'aera_step1' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => false,
                'comment'=> '지역구룹 1단 '
            ],
            'etc' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => false,
                'comment'=> '기타등등 용도'
            ],
            'created_at datetime default current_timestamp',
        ]);
        $this->forge->addPrimaryKey('aera_id');
        $this->forge->createTable('aera');
    }

    public function down()
    {
        $this->forge->dropTable('aera');
    }
}
