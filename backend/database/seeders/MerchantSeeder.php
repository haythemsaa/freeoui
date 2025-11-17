<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MerchantSeeder extends Seeder
{
    public function run(): void
    {
        $merchants = [
            [
                'name' => 'Restaurant Le Gourmet',
                'email' => 'legourmet@example.com',
                'password' => Hash::make('password123'),
                'phone_number' => '+21671234567',
                'address' => 'Avenue Habib Bourguiba, Tunis',
                'latitude' => 36.8065,
                'longitude' => 10.1815,
                'city_id' => 1,
                'governorate_id' => 1,
                'subscription_plan' => 'premium',
                'is_active' => true,
            ],
            [
                'name' => 'Café des Délices',
                'email' => 'cafedelices@example.com',
                'password' => Hash::make('password123'),
                'phone_number' => '+21671234568',
                'address' => 'La Marsa, Tunis',
                'latitude' => 36.8783,
                'longitude' => 10.3250,
                'city_id' => 1,
                'governorate_id' => 1,
                'subscription_plan' => 'basic',
                'is_active' => true,
            ],
            [
                'name' => 'Sport Plus Gym',
                'email' => 'sportplus@example.com',
                'password' => Hash::make('password123'),
                'phone_number' => '+21671234569',
                'address' => 'Centre Ville, Sfax',
                'latitude' => 34.7406,
                'longitude' => 10.7603,
                'city_id' => 2,
                'governorate_id' => 2,
                'subscription_plan' => 'premium',
                'is_active' => true,
            ],
            [
                'name' => 'Beauty Spa',
                'email' => 'beautyspa@example.com',
                'password' => Hash::make('password123'),
                'phone_number' => '+21671234570',
                'address' => 'Sousse Corniche',
                'latitude' => 35.8256,
                'longitude' => 10.6369,
                'city_id' => 3,
                'governorate_id' => 3,
                'subscription_plan' => 'basic',
                'is_active' => true,
            ],
            [
                'name' => 'Pizza Express',
                'email' => 'pizzaexpress@example.com',
                'password' => Hash::make('password123'),
                'phone_number' => '+21671234571',
                'address' => 'Ariana Centre',
                'latitude' => 36.8625,
                'longitude' => 10.1956,
                'city_id' => 4,
                'governorate_id' => 4,
                'subscription_plan' => 'basic',
                'is_active' => true,
            ],
        ];

        foreach ($merchants as $merchant) {
            $geom = DB::raw("ST_SetSRID(ST_MakePoint({$merchant['longitude']}, {$merchant['latitude']}), 4326)");

            DB::table('merchants')->insert([
                'name' => $merchant['name'],
                'email' => $merchant['email'],
                'password' => $merchant['password'],
                'phone_number' => $merchant['phone_number'],
                'address' => $merchant['address'],
                'latitude' => $merchant['latitude'],
                'longitude' => $merchant['longitude'],
                'geom' => $geom,
                'city_id' => $merchant['city_id'],
                'governorate_id' => $merchant['governorate_id'],
                'subscription_plan' => $merchant['subscription_plan'],
                'is_active' => $merchant['is_active'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
