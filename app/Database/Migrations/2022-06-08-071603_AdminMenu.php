<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AdminMenu extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'admin_menu_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],            
            'title' => [
                'type' => 'varchar',
                'constraint' => '255',
                'comment'=> '메뉴미입력'
            ],
            'depth' => [
                'type' => 'int',
                'constraint' => 11,
                'default'=>0,
                'comment'=> '관리자레벨'
            ],
            'tag' => [
                'type' => 'varchar',
                'constraint' => '255',
                'comment'=> 'admin menu fillter'
            ],
            'href' => [
                'type' => 'varchar',
                'constraint' => '255',
                'comment'=> '# 은 최상단메뉴 하단은 주소'
            ],
            'is_hide' => [
                'type' => 'int',
                'constraint' => 11,
                'default'=>0,
                'comment'=> '메뉴 숨김여부 0오픈 1숨김'
            ],
            'created_at datetime default current_timestamp',
        ]);

        $this->forge->addPrimaryKey('admin_menu_id');
        $this->forge->createTable('admin_menu');
    }

    public function down()
    {
        $this->forge->dropTable('admin_menu');
    }
}
