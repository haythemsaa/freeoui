<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Restaurant', 'name_ar' => 'مطعم', 'icon' => 'restaurant', 'color' => '#FF6B6B'],
            ['name' => 'Shopping', 'name_ar' => 'تسوق', 'icon' => 'shopping_bag', 'color' => '#4ECDC4'],
            ['name' => 'Loisirs', 'name_ar' => 'ترفيه', 'icon' => 'sports_esports', 'color' => '#45B7D1'],
            ['name' => 'Beauté & Bien-être', 'name_ar' => 'جمال وعافية', 'icon' => 'spa', 'color' => '#F7B731'],
            ['name' => 'Santé', 'name_ar' => 'صحة', 'icon' => 'local_hospital', 'color' => '#26DE81'],
            ['name' => 'Sport', 'name_ar' => 'رياضة', 'icon' => 'fitness_center', 'color' => '#FD79A8'],
            ['name' => 'Éducation', 'name_ar' => 'تعليم', 'icon' => 'school', 'color' => '#A29BFE'],
            ['name' => 'Services', 'name_ar' => 'خدمات', 'icon' => 'build', 'color' => '#636E72'],
            ['name' => 'Voyage', 'name_ar' => 'سفر', 'icon' => 'flight', 'color' => '#00B894'],
            ['name' => 'Divertissement', 'name_ar' => 'تسلية', 'icon' => 'movie', 'color' => '#FF7675'],
        ];

        foreach ($categories as $category) {
            DB::table('categories')->insert(array_merge($category, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
