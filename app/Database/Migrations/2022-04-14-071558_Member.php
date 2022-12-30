<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Member extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'member_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'hash_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'null' => false,
            ],
            'sns_id' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => false,
            ],
            'sns_type' => [
                'type' => 'INT',
                'constraint' => 2,
                'null' => false,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => false
            ],
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => false,
            ],
            'verified_email' => [
                'type' => 'INT',
                'constraint' => 1,
                'null' => false,
                'default'=>0
            ],
            'locale' => [
                'type' => 'VARCHAR',
                'constraint' => '25',
                'null' => true
            ],
            'picture' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true
            ],
            'status' => [
                'type' => 'INT',
                'constraint' => 2,
                'null' => false,
            ],
            'updated_at' => [
                'type' => 'datetime',
                'null' => true,
            ],            
            'created_at datetime default current_timestamp',
        ]);
        $this->forge->addPrimaryKey('member_id');
        $this->forge->createTable('member');
    }

    public function down()
    {
        $this->forge->dropTable('member');
    }
}
