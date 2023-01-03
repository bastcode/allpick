<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMemberKeywords extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'member_keywords_id' => [
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
            'keyword' => [
                'type' => 'varchar',
                'constraint' => '1024',
                'null' => false,
            ],
            'created_at datetime default current_timestamp',
        ]);
        $this->forge->addPrimaryKey('member_keywords_id');
        $this->forge->addForeignKey('member_id', 'member', 'member_id');
        $this->forge->createTable('member_keywords');
    }

    public function down()
    {
        $this->forge->dropTable('member_keywords');
    }
}
