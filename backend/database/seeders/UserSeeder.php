<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'first_name' => 'Ahmed',
                'last_name' => 'Ben Salem',
                'phone_number' => '+21620123456',
                'country_code' => '+216',
                'email' => 'ahmed@example.com',
                'password' => Hash::make('password123'),
                'city_id' => 1,
                'governorate_id' => 1,
                'language' => 'fr',
                'is_active' => true,
                'points_balance' => 150,
                'level' => 'gold',
            ],
            [
                'first_name' => 'Fatma',
                'last_name' => 'Trabelsi',
                'phone_number' => '+21620123457',
                'country_code' => '+216',
                'email' => 'fatma@example.com',
                'password' => Hash::make('password123'),
                'city_id' => 1,
                'governorate_id' => 1,
                'language' => 'ar',
                'is_active' => true,
                'points_balance' => 50,
                'level' => 'silver',
            ],
            [
                'first_name' => 'Mohamed',
                'last_name' => 'Khaled',
                'phone_number' => '+21620123458',
                'country_code' => '+216',
                'email' => 'mohamed@example.com',
                'password' => Hash::make('password123'),
                'city_id' => 2,
                'governorate_id' => 2,
                'language' => 'fr',
                'is_active' => true,
                'points_balance' => 250,
                'level' => 'platinum',
            ],
        ];

        foreach ($users as $user) {
            DB::table('users')->insert(array_merge($user, [
                'phone_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
