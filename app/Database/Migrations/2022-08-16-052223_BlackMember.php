<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class BlackMember extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'balck_member_id' => [
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
                'comment'=> '회원 id'
            ],
            'judgment_code' => [
                'type' => 'int',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
                'comment'=> '집행코드 400 불도덕함 900 풀려남'
            ],
            'message' => [
                'type' => 'varchar',
                'constraint' => '2000',
                'comment'=> '사유'
            ],
            'created_at datetime default current_timestamp',
        ]);

        $this->forge->addPrimaryKey('balck_member_id');
        $this->forge->addForeignKey('member_id', 'member', 'member_id');
        $this->forge->createTable('balck_member');
    }

    public function down()
    {
        $this->forge->dropTable('balck_member');
    }
}
