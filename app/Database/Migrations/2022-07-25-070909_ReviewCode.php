<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ReviewCode extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'review_code_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'reviews_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => false,
                'comment'=> '리뷰아이디'
            ],                      
            'choice' => [
                'type' => 'decimal',
                'constraint' => '2.1',                          
                'comment'=> '선택문항'
            ],
            'type' => [
                'type' => 'int',
                'constraint' => '11',
                'comment'=> '1 : 2 good or bad'
            ],            
            'created_at datetime default current_timestamp',
        ]);

        $this->forge->addPrimaryKey('review_code_id');
        $this->forge->createTable('review_code');
    }

    public function down()
    {
        $this->forge->dropTable('review_code');
    }
}
