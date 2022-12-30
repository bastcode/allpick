<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class MemberPushToken extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'member_push_toekn_id' => [
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
            'device_id' => [
                'type' => 'varchar',
                'constraint' => 512,
                'null' => false
            ],
            'type' => [
                'type' => 'int',
                'constraint' => 11,
                'null' => false,
                'default'=>1,
                'comment'=> 'service type 1 web 2 goolge 3facebook 4apple '
            ],
            'created_at datetime default current_timestamp',
        ]);

        $this->forge->addPrimaryKey('member_push_toekn_id');
        $this->forge->addForeignKey('member_id', 'member', 'member_id');
        $this->forge->createTable('member_push_toekn');
    }

    public function down()
    {
        $this->forge->dropTable('member_push_toekn');
    }
}
