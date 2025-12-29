<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Gestore',
            'email' => 'gestore@example.com',
            'password' => Hash::make('password'),
            'role' => 'gestore',
            'weekly_hours' => 40,
        ]);
    }
}
