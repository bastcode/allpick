<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AdminMenu extends Seeder
{
    public function run()
    {
        $fileName = APPPATH.'Database/Seeds/admin_menu.json';
        $json = json_decode(file_get_contents($fileName), true);
        
        foreach($json as $key => $val)
        {
            
            $this->db->table('admin_menu')->insert($val);            
        }
    }
}
