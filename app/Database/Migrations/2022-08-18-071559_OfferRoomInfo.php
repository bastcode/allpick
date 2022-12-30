<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class OfferRoomInfo extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'offer_room_info_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'product_offer_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => false,
                'comment'=> '상품챗방id'
            ],
            'transaction_code' => [
                'type' => 'int',
                'constraint' => 11,  
                'default' => 0,
                'comment'=> '0 아무것도아님 2 예약 3거래완료'
            ],            
            'created_at datetime default current_timestamp',
        ]);

        $this->forge->addPrimaryKey('offer_room_info_id');
        $this->forge->createTable('offer_room_info');
    }

    public function down()
    {
        $this->forge->dropTable('offer_room_info');
    }
}
