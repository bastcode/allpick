<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMemberGeo extends Migration
{
    public function up()
    {
        //default geo  city hill          
        $this->forge->addField([
            'member_geo_id' => [
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
            'latitude' => [
                'type' => 'decimal',
                'constraint' => '12,6',
                'null' => false,
                'default'=>1.292861
            ],
            'longitude' => [
                'type' => 'decimal',
                'constraint' => '12,6',
                'null' => false,
                'default'=>103.852689
            ],
            'station1' => [
                'type' => 'varchar',
                'constraint' => '255',
                'null' => false,
            ],
            'station2' => [
                'type' => 'varchar',
                'constraint' => '255',
                'null' => false,
            ],
            'distincton' => [
                'type' => 'int',
                'constraint' => '11',
                'default' => 500,
                'null' => false,
            ],
            'updated_at' => [
                'type' => 'datetime',
                'null' => true,
            ],
            'created_at datetime default current_timestamp',
        ]);
        $this->forge->addPrimaryKey('member_geo_id');
        $this->forge->addForeignKey('member_id', 'member', 'member_id');
        $this->forge->createTable('member_geo');
    }

    public function down()
    {
        $this->forge->dropTable('member_geo');
    }
}
