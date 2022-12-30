<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddProductOffer extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'product_offer_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'offer_hash_id' => [
                'type' => 'varchar',
                'constraint' => 255,
                'null' => false
            ],
            'product_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => false
            ],
            'dealer_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => false
            ],
            'offer_price' => [
                'type' => 'int',
                'constraint' => 11,
                'null' => false,
                'default'=>0,
                'comment'=> ''
            ],
            'offer_status' => [
                'type' => 'int',
                'constraint' => 11,
                'null' => false,
                'default'=>0,
                'comment'=> ''
            ],
            'bell_status_onner' => [
                'type' => 'int',
                'constraint' => 11,
                'null' => false,
                'default'=>0,
                'comment'=> '0 open 1 close '
            ],
            'bell_status_dealer' => [
                'type' => 'int',
                'constraint' => 11,
                'null' => false,
                'default'=>0,
                'comment'=> '0 open 1 close '
            ],
            'chat_status' => [
                'type' => 'int',
                'constraint' => 11,
                'null' => false,
                'default'=>0,
                'comment'=> '0 waiting  1 acception 2 reject 3 exit 4 delete 5 finish'
            ],
            'chat_status_dealer' => [
                'type' => 'int',
                'constraint' => 11,
                'null' => false,
                'default'=>0,
                'comment'=> '0 waiting  1 acception 2 reject 3 exit 4 delete 5 finish'
            ],
            'last_message' => [
                'type' => 'varchar',
                'constraint' => 255,
                'null' => false
            ],
            'updated_at datetime default current_timestamp',
            'created_at datetime default current_timestamp',
        ]);

        $this->forge->addPrimaryKey('product_offer_id');
        $this->forge->addForeignKey('product_id', 'product', 'product_id');
        $this->forge->createTable('product_offer');
    }

    public function down()
    {
        $this->forge->dropTable('product_offer');
    }
}
