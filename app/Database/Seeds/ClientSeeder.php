<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

use Faker\Factory;

class ClientSeeder extends Seeder
{
    public function run()
    {
        // for ($i = 0; $i < 10; $i++) { //to add 10 clients. Change limit as desired
             
        // }
        $fileName = APPPATH.'Database/Seeds/aera.json';
        $json = json_decode(file_get_contents($fileName), true);

        foreach($json as $key => $val)
        {
            $this->db->table('aera')->insert($val);
        }
        
    }

    private function generateClient(): array
    {
        $faker = Factory::create();
        return  [];
        // return [
        //     'name' => $faker->name(),
        //     'email' => $faker->email,
        //     'retainer_fee' => random_int(100000, 100000000)
        // ];
    }
}