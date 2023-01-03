<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMemberBadgesLog extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'member_badges_log_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'member_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => false,
            ],
            'title' => [
                'type' => 'varchar',
                'constraint' => '100',
                'null' => false
            ],            
            'get_point' => [
                'type' => 'int',
                'constraint' => 11,
                'null' => false,
                'default'=>0
            ],
            'created_at datetime default current_timestamp',
        ]);
        $this->forge->addPrimaryKey('member_badges_log_id');
        $this->forge->addForeignKey('member_id', 'member', 'member_id');
        $this->forge->createTable('member_badges_log');
    }

    public function down()
    {
        $this->forge->dropTable('member_badges_log');
    }
}
