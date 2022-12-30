<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class Aera extends Seeder
{
    public function run()
    {
        $fileName = APPPATH.'Database/Seeds/aera.json';
        $json = json_decode(file_get_contents($fileName), true);
        foreach($json as $key => $val)
        {
            $this->db->table('aera')->insert($val);
        }

        $fileName = APPPATH.'Database/Seeds/aera_2.json';
        $json = json_decode(file_get_contents($fileName), true);
        foreach($json as $key => $val)
        {
            $this->db->table('aera_geo')->insert($val);
        }
    }
}

/*
php spark make:seeder aera
php spark db:seed aera
*/