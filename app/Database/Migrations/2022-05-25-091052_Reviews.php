<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Reviews extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'reviews_id' => [
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
            'member_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => false,
                'comment'=> '구매한 사람 id'
            ],
            'star_point' => [
                'type' => 'decimal',
                'constraint' => '2.1',                          
                'comment'=> '별점수'
            ],
            'good_choice' => [
                'type' => 'varchar',
                'constraint' => '20',  
                'comment'=> '선택문항 1,2,3,4'
            ],
            'bad_choice' => [
                'type' => 'varchar',
                'constraint' => '20',  
                'comment'=> '선택문항 1,2,3,4'
            ],
            'review_content' => [
                'type' => 'varchar',
                'constraint' => '2000',
                'comment'=> '리뷰 내용'
            ],
            'picture' => [
                'type' => 'varchar',
                'constraint' => '255',
                'comment'=> '등록사진 1개만'
            ],
            'created_at datetime default current_timestamp',
        ]);

        $this->forge->addPrimaryKey('reviews_id');
        $this->forge->addForeignKey('product_id', 'product', 'product_id');
        $this->forge->createTable('reviews');
    }

    public function down()
    {
        $this->forge->dropTable('reviews');
    }
}
