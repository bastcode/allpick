<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Company extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'company_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'company_name' => [
                'type' => 'varchar',
                'constraint' => 255,                
                'comment'=> '회사명'
            ],
            'phone_number' => [
                'type' => 'varchar',
                'constraint' => 20,                
                'comment'=> '번호'
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
            'created_at datetime default current_timestamp',
        ]);
        $this->forge->addPrimaryKey('company_id');
        $this->forge->createTable('company');
        
        // $this->forge->addForeignKey('member_id', 'member', 'member_id');
    }

    public function down()
    {
        $this->forge->dropTable('company');
    }
}
