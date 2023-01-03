<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Category extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'category_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'origin_code' => [
                'type' => 'varchar',
                'constraint' => 10,
                'null' => false,
                'default'=>'100',
                'comment'=> '구분용 3자리 코드'
            ],
            'category_name' => [
                'type' => 'varchar',
                'constraint' => 255,
                'null' => false,
                'default'=>'',
                'comment'=> '카테고리 이름 1단'
            ],            
            'created_at datetime default current_timestamp',
        ]);

        $this->forge->addPrimaryKey('category_id');
        $this->forge->createTable('category');
    }

    public function down()
    {
        $this->forge->dropTable('category');
    }
}
