<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class BoardThumbnail extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'board_thumbnail_id' => [
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
                'comment'=> '게시글 code'
            ],            
            'img_url' => [
                'type' => 'varchar',
                'constraint' => '255',
                'comment'=> '이미지 수조'
            ],            
            'created_at datetime default current_timestamp',
        ]);

        $this->forge->addPrimaryKey('board_thumbnail_id');
        $this->forge->addForeignKey('board_id', 'board', 'board_id');
        $this->forge->createTable('board_thumbnail');
    }

    public function down()
    {
        $this->forge->dropTable('board_thumbnail');
    }
}
