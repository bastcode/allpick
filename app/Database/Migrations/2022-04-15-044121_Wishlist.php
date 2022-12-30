<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Wishlist extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'wish_id' => [
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
            'product_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'null' => false,
            ],
            'created_at datetime default current_timestamp',
        ]);
        $this->forge->addPrimaryKey('wish_id');
        $this->forge->addForeignKey('member_id', 'member', 'member_id');
        // CONSTRAINT `wishlist_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `member`(`member_id`)
        $this->forge->createTable('wishlist');
    }

    public function down()
    {
        $this->forge->dropTable('wishlist');
    }
}
