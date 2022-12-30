<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AdminAccount extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'admin_account_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],            
            'acc' => [
                'type' => 'varchar',
                'constraint' => '255',
                'comment'=> '계정 아이디'
            ],
            'nickname' => [
                'type' => 'varchar',
                'constraint' => '255',
                'comment'=> '관리자이름'
            ],
            'pwhash' => [
                'type' => 'varchar',
                'constraint' => '512',
                'comment'=> '계정 비밀번호(해시값)'
            ],
            'permissions' => [
                'type' => 'text',
                'comment'=> '관리자 계정 권한 정보(메뉴 권한)'
            ],
            'otpkey' => [
                'type' => 'varchar',
                'constraint' => '50',
                'comment'=> 'otp 기능 사용을 위한 key google'
            ],
            'lv' => [
                'type' => 'int',
                'constraint' => 11,
                'default'=>0,
                'comment'=> '관리자레벨'
            ],
            'created_at datetime default current_timestamp',
        ]);

        $this->forge->addPrimaryKey('admin_account_id');
        $this->forge->createTable('admin_account');
    }

    public function down()
    {
        $this->forge->dropTable('admin_account');
    }
}
