<?php

namespace Database\Seeders;

use App\Models\Governorate;
use App\Models\City;
use Illuminate\Database\Seeder;

class GovernorateSeeder extends Seeder
{
    public function run(): void
    {
        $governorates = [
            'Tunis' => ['Tunis', 'La Marsa', 'Carthage', 'Ariana', 'Ben Arous'],
            'Sfax' => ['Sfax', 'Sakiet Ezzit', 'Sakiet Eddaier'],
            'Sousse' => ['Sousse', 'Hammam Sousse', 'Port El Kantaoui'],
            'Nabeul' => ['Nabeul', 'Hammamet', 'Kelibia', 'Korba'],
            'Bizerte' => ['Bizerte', 'Menzel Bourguiba', 'Ras Jebel'],
            'Monastir' => ['Monastir', 'Moknine', 'Jemmal'],
            'Gabès' => ['Gabès', 'Mareth'],
            'Kairouan' => ['Kairouan', 'Haffouz'],
            'Gafsa' => ['Gafsa', 'Metlaoui'],
            'Médenine' => ['Médenine', 'Zarzis', 'Djerba'],
            'Mahdia' => ['Mahdia', 'Ksour Essaf'],
            'Béja' => ['Béja', 'Testour'],
            'Jendouba' => ['Jendouba', 'Tabarka'],
            'Kasserine' => ['Kasserine', 'Sbeitla'],
            'Kébili' => ['Kébili', 'Douz'],
            'Kef' => ['Le Kef', 'Dahmani'],
            'Sidi Bouzid' => ['Sidi Bouzid', 'Regueb'],
            'Siliana' => ['Siliana', 'Makthar'],
            'Tataouine' => ['Tataouine', 'Remada'],
            'Tozeur' => ['Tozeur', 'Nefta'],
            'Zaghouan' => ['Zaghouan', 'Bir Mcherga'],
            'Manouba' => ['Manouba', 'Oued Ellil'],
            'Ben Arous' => ['Ben Arous', 'Radès', 'Hammam Lif'],
            'Ariana' => ['Ariana', 'Ettadhamen', 'Raoued'],
        ];

        foreach ($governorates as $governorateName => $cities) {
            $governorate = Governorate::create([
                'name_fr' => $governorateName,
                'name_ar' => $governorateName, // Would need proper Arabic names
                'code' => strtoupper(substr($governorateName, 0, 3)),
            ]);

            foreach ($cities as $cityName) {
                City::create([
                    'governorate_id' => $governorate->id,
                    'name_fr' => $cityName,
                    'name_ar' => $cityName, // Would need proper Arabic names
                    'postal_code' => rand(1000, 9999),
                ]);
            }
        }

        $this->command->info('Governorates and cities seeded successfully!');
    }
}
