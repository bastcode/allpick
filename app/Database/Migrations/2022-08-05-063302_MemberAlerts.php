<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class MemberAlerts extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'member_alerts_id' => [
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
                'comment'=> '구매한 사람 id'
            ],            
            'message' => [
                'type' => 'varchar',
                'constraint' => '2000',
                'comment'=> '푸쉬 메세지'
            ],            
            'created_at datetime default current_timestamp',
        ]);

        $this->forge->addPrimaryKey('member_alerts_id');
        // $this->forge->addForeignKey('product_id', 'product', 'product_id');
        $this->forge->addForeignKey('member_id', 'member', 'member_id');
        $this->forge->createTable('member_alerts');
    }

    public function down()
    {
        $this->forge->dropTable('member_alerts');
    }
}
