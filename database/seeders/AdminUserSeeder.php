<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::create([
            'firstname' => 'Chichebem',
            'lastname' => 'Jibunoh',
            'email' => 'chijibson@gmail.com',
            'password' => bcrypt('123456'),
            'phone' => '07033248431',
            'role' => 3,
        ]);
    }
}
