<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class MemberAlarm extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'alarm_id' => [
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
            'all_alert' => [
                'type' => 'int',
                'constraint' => 11,
                'null' => false,
                'default'=>1,
                'comment'=> 'all alert on off'
            ],
            'new_message' => [
                'type' => 'int',
                'constraint' => 11,
                'null' => false,
                'default'=>0,
                'comment'=> 'chat message'
            ],
            'announcements' => [
                'type' => 'int',
                'constraint' => 11,
                'null' => false,
                'default'=>0,
                'comment'=> 'notice  '
            ],
            'other' => [
                'type' => 'int',
                'constraint' => 11,
                'null' => false,
                'default'=>0,
                'comment'=> '기타등등~'
            ],
            'keyword' => [
                'type' => 'int',
                'constraint' => 11,
                'null' => false,
                'default'=>0,
                'comment'=> 'my key word hit'
            ],
            'init_setting' => [
                'type' => 'int',
                'constraint' => 11,
                'null' => false,
                'default'=>0,
                'comment'=> '0 no set 1 set'
            ],
            'created_at datetime default current_timestamp',
        ]);

        $this->forge->addPrimaryKey('alarm_id');
        $this->forge->addForeignKey('member_id', 'member', 'member_id');
        $this->forge->createTable('member_alarm');
    }

    public function down()
    {
        $this->forge->dropTable('member_alarm');
    }
}
