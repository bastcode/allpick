<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddProductThumbnail extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'product_thumbnail_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'product_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => false
            ],
            'img_url' => [
                'type' => 'varchar',
                'constraint' => '500',
                'null' => false
            ],            
            'is_first' => [
                'type' => 'int',
                'constraint' => 11,
                'null' => false,
                'default'=>0
            ],
            'created_at datetime default current_timestamp',
        ]);
        $this->forge->addPrimaryKey('product_thumbnail_id');
        $this->forge->addForeignKey('product_id', 'product', 'product_id');
        $this->forge->createTable('product_thumbnail');
    }

    public function down()
    {
        $this->forge->dropTable('product_thumbnail');
    }
}
