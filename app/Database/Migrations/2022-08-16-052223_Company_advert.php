<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CompanyAdvert extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'company_advert_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'company_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => false,
                'comment'=> '회사코드'
            ],
            'deposit' => [
                'type' => 'BIGINT',
                'constraint' => 12,
                'null' => false,
                'default'=>0,
                'comment'=> '예치금'
            ],
            'advert_money' => [
                'type' => 'BIGINT',
                'constraint' => 12,
                'null' => false,
                'default'=>0,
                'comment'=> '광고사용비'
            ],
            'created_at datetime default current_timestamp',
        ]);
        $this->forge->addPrimaryKey('company_advert_id');
        $this->forge->createTable('company_advert');
        
        // $this->forge->addForeignKey('member_id', 'member', 'member_id');
    }

    public function down()
    {
        $this->forge->dropTable('company_advert');
    }
}
