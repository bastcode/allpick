<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class Category extends Seeder
{
    public function run()
    {
        $fileName = APPPATH.'Database/Seeds/category.json';
        $json = json_decode(file_get_contents($fileName), true);
        $i = 100;
        foreach($json as $key => $val)
        {
            $val['origin_code'] = $i++;
            $this->db->table('category')->insert($val);            
        }

        $fileName = APPPATH.'Database/Seeds/category_sub.json';
        $json = json_decode(file_get_contents($fileName), true);
        $i = 100;
        foreach($json as $key => $val)
        {
            $val['category_sub_code'] = $i++;
            $this->db->table('category_code')->insert($val);
        }    
    }
}
