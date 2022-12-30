<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddBadges extends Migration
{
    //badges
    public function up()
    {
        $this->forge->addField([
            'badges_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'badges_image' => [
                'type' => 'VARCHAR',
                'constraint' => '500',
                'null' => false
            ],
            'badges_title' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => false
            ],
            'badges_description' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => false
            ],
            'badges_point' => [
                'type' => 'int',
                'constraint' => 11,
                'null' => false,
                'default'=>0
            ],
            'created_at datetime default current_timestamp',
        ]);
        $this->forge->addPrimaryKey('badges_id');
        $this->forge->createTable('badges');
    }

    public function down()
    {
        $this->forge->dropTable('badges');
    }
}
