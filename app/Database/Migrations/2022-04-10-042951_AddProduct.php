<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddProduct extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'product_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'mem_idx' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => false,
            ],
            'category_code_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
            'product_name' => [
                'type' => 'varchar',
                'constraint' => 255,
                'null' => false,                
                'comment'=> 'item name'
            ],
            'price' => [
                'type' => 'decimal',
                'constraint' => '10,2',
                'null' => false,
                'comment'=> 'price'
            ],
            'views' => [
                'type' => 'int',
                'constraint' => 11,
                'null' => false, 
                'default'=>0,
                'comment'=> 'click uv view'
            ],
            'content' => [
                'type' => 'varchar',
                'constraint' => 2000,
                'null' => false,
                'default'=>'',
                'comment'=> 'text'
            ],
            'product_latitude' => [
                'type' => 'decimal',
                'constraint' => '12,6',
                'null' => false,
                'default'=>1.292861,
                'comment'=> '위도 기준은 싱가폴시청'
            ],
            'product_longitude' => [
                'type' => 'decimal',
                'constraint' => '12,6',
                'null' => false,
                'default'=>103.852689,
                'comment'=> '경도 기준은 싱가폴시청'
            ],
            'station' => [
                'type' => 'varchar',
                'constraint' => 512,
                'null' => false,                
                'comment'=> 'local name'
            ],
            'status' => [
                'type' => 'int',
                'constraint' => 11,
                'null' => false, 
                'default'=>0,
                'comment'=> '0 wait 1 selling 2 reserved 3 sold out 4 delete'
            ],
            'offer_price' => [
                'type' => 'int',
                'constraint' => 11,
                'null' => false, 
                'default'=>0,
                'comment'=> '0 not nego 1 over nego price'
            ],
            'offer_yn' => [
                'type' => 'int',
                'constraint' => 11,
                'null' => false, 
                'default'=>1,
                'comment'=> '0 offer off 1 offer on'
            ],
            'created_tick' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => false, 
                'default'=>0,
                'comment'=> 'create tick time'
            ],
            'deleted_at datetime default current_timestamp',
            'updated_at datetime default current_timestamp',
            'created_at datetime default current_timestamp',
        ]);

        $this->forge->addPrimaryKey('product_id');
        $this->forge->createTable('product');
    }

    public function down()
    {
        $this->forge->dropTable('product');
    }
}
