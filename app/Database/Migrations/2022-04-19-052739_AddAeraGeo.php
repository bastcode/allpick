<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAeraGeo extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'aera_geo_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'aera_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'default'=>1,
                'comment'=> '지역 그룹 코드, 디폴트 1 싱가포르 차후 기획에서 사용 '
            ],
            'aera_step2' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => false,
                'comment'=> '지역 이름'
            ],
            'latitude' => [
                'type' => 'decimal',
                'constraint' => '10,7',
                'null' => false,
                'default'=>1.292861
            ],
            'longitude' => [
                'type' => 'decimal',
                'constraint' => '10,7',
                'null' => false,
                'default'=>103.852689
            ],
            'created_at datetime default current_timestamp',
        ]);
        $this->forge->addPrimaryKey('aera_geo_id');
        $this->forge->addForeignKey('aera_id', 'aera', 'aera_id');
        $this->forge->createTable('aera_geo');
    }

    public function down()
    {
        $this->forge->dropTable('aera_geo');
    }
}
