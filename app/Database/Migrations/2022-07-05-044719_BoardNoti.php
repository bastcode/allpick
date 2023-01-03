<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class BoardNoti extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'board_noti_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'board_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => false,
                'comment'=> '게시글 idx'
            ],
            'member_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'comment'=> '회원아이디'
            ],                        
            'created_at datetime default current_timestamp',
        ]);

        $this->forge->addPrimaryKey('board_noti_id');
        $this->forge->addForeignKey('board_id', 'board', 'board_id');
        $this->forge->createTable('board_noti');
    }

    public function down()
    {
        $this->forge->dropTable('board_noti');
    }
}
