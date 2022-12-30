<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ProductPurchase extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'product_purchase_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'product_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => false,
                'comment'=> '구매완료된  상품 id'
            ],
            'price' => [
                'type' => 'decimal',
                'constraint' => '10,2',
                'null' => false,
                'comment'=> 'price'
            ],
            'onner_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => false,
                'comment'=> '판매한 사람 id'
            ],
            'dealer_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => false,
                'comment'=> '구매한 사람 id'
            ],
            'created_tick' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => false, 
                'default'=>0,
                'comment'=> '구매완료 시간'
            ],
            'created_at datetime default current_timestamp',
        ]);

        $this->forge->addPrimaryKey('product_purchase_id');
        $this->forge->addForeignKey('product_id', 'product', 'product_id');
        $this->forge->createTable('product_purchase');
    }

    public function down()
    {
        $this->forge->dropTable('product_purchase');
    }
}
