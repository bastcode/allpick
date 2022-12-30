<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Board extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'board_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'admin_account_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'comment'=> '관리자 계_정 idx'
            ],
            'type' => [
                'type' => 'int',
                'constraint' => '11',
                'default' => 1,
                'comment'=> '1 notice , 2 event, 3 pop, 4 etc '
            ],
            'title' => [
                'type' => 'varchar',
                'constraint' => '255',
                'comment'=> '계시글 제목'
            ],
            'content' => [
                'type' => 'text',                
                'comment'=> '계시글 본문'
            ],            
            'created_at datetime default current_timestamp',
        ]);

        $this->forge->addPrimaryKey('board_id');
        $this->forge->createTable('board');
    }

    public function down()
    {
        $this->forge->dropTable('board');
    }
}
