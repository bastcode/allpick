<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Jwt extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'jwt_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'member_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => false
            ],
            'token' => [
                'type' => 'varchar',
                'constraint' => 512,
                'null' => false
            ],
            'type' => [
                'type' => 'int',
                'constraint' => 11,
                'null' => false,
                'default'=>1,
                'comment'=> '1 auth 2 ext login 3 service '
            ],
            'created_at datetime default current_timestamp',
        ]);

        $this->forge->addPrimaryKey('jwt_id');
        $this->forge->addForeignKey('member_id', 'member', 'member_id');
        $this->forge->createTable('jwt');
    }

    public function down()
    {
        $this->forge->dropTable('jwt');
    }
}
