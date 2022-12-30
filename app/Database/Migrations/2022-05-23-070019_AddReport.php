<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddReport extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'report_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'report_category' => [
                'type' => 'INT',
                'constraint' => 11,
                'default'=>'1',
                'comment'=> '1 포스팅 2 유저 3 채팅 4 기타'
            ], 
            'member_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => false,
            ],            
            'email' => [
                'type' => 'varchar',
                'constraint' => 255,
                'null' => false,                
                'comment'=> 'send target email'
            ],
            'title' => [
                'type' => 'varchar',
                'constraint' => 100,
                'null' => false,                
                'comment'=> 'report title'
            ],
            'content' => [
                'type' => 'varchar',
                'constraint' => 1000,
                'null' => false,                
                'comment'=> 'report content'
            ],
            'uploads1' => [
                'type' => 'varchar',
                'constraint' => 512,
                'null' => false,                
                'comment'=> 'picture 1'
            ],
            'uploads2' => [
                'type' => 'varchar',
                'constraint' => 512,
                'null' => false,                
                'comment'=> 'picture 2'
            ],
            'report_hash' => [
                'type' => 'varchar',
                'constraint' => 255,
                'null' => false,                
                'comment'=> '신고대상 id hash'
            ],
            'product_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'default'=>0,      
            ],             
            'created_at datetime default current_timestamp',
        ]);

        $this->forge->addPrimaryKey('report_id');
        $this->forge->createTable('report');
    }

    public function down()
    {
        $this->forge->dropTable('report');
    }
}
