<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddChatRoom extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'chat_room_log_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],            
            'offer_hash_id' => [
                'type' => 'varchar',
                'constraint' => 255,
                'null' => false,
                'comment'=> 'is room_id '
            ],
            'chat_member_hash' => [
                'type' => 'varchar',
                'constraint' => 255,
                'null' => false
            ],
            'chat_member_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'null' => false
            ],
            'chat_member_type' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
                'default'=>1,
                'comment'=> '1 owner 2 dealer'
            ],
            'picture' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'comment'=> 'chat member pocture'
            ],
            'message_type' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
                'comment'=> '1 text 2 image 3 mp4'
            ],
            'message' => [
                'type' => 'varchar',
                'constraint' => 500,
                'null' => false
            ],
            'created_at datetime default current_timestamp',
        ]);

        $this->forge->addPrimaryKey('chat_room_log_id');
        $this->forge->createTable('chat_room_log');
    }

    public function down()
    {
        $this->forge->dropTable('chat_room_log');
    }
}
