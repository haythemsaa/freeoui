<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name_fr' => 'Restaurants',
                'name_ar' => 'مطاعم',
                'icon' => 'restaurant',
                'color' => '#FF5722',
                'children' => ['Fast Food', 'Cuisine Traditionnelle', 'Pizza', 'Sushi', 'Café'],
            ],
            [
                'name_fr' => 'Shopping',
                'name_ar' => 'تسوق',
                'icon' => 'shopping_bag',
                'color' => '#9C27B0',
                'children' => ['Vêtements', 'Électronique', 'Maison & Déco', 'Cosmétiques', 'Bijouterie'],
            ],
            [
                'name_fr' => 'Beauté & Bien-être',
                'name_ar' => 'جمال ورفاهية',
                'icon' => 'spa',
                'color' => '#E91E63',
                'children' => ['Coiffure', 'Esthétique', 'Spa', 'Massage', 'Onglerie'],
            ],
            [
                'name_fr' => 'Loisirs',
                'name_ar' => 'ترفيه',
                'icon' => 'attractions',
                'color' => '#2196F3',
                'children' => ['Cinéma', 'Parc d\'attractions', 'Bowling', 'Escape Game', 'Karting'],
            ],
            [
                'name_fr' => 'Sport & Fitness',
                'name_ar' => 'رياضة ولياقة',
                'icon' => 'fitness_center',
                'color' => '#4CAF50',
                'children' => ['Salle de sport', 'Yoga', 'Piscine', 'Arts martiaux', 'Danse'],
            ],
            [
                'name_fr' => 'Automobile',
                'name_ar' => 'سيارات',
                'icon' => 'directions_car',
                'color' => '#607D8B',
                'children' => ['Lavage auto', 'Réparation', 'Pneus', 'Location', 'Accessoires'],
            ],
            [
                'name_fr' => 'Services',
                'name_ar' => 'خدمات',
                'icon' => 'build',
                'color' => '#FF9800',
                'children' => ['Plomberie', 'Électricité', 'Nettoyage', 'Déménagement', 'Jardinage'],
            ],
            [
                'name_fr' => 'Éducation',
                'name_ar' => 'تعليم',
                'icon' => 'school',
                'color' => '#00BCD4',
                'children' => ['Cours particuliers', 'Langues', 'Formation pro', 'Musique', 'Art'],
            ],
            [
                'name_fr' => 'Santé',
                'name_ar' => 'صحة',
                'icon' => 'local_hospital',
                'color' => '#F44336',
                'children' => ['Clinique', 'Dentiste', 'Pharmacie', 'Opticien', 'Laboratoire'],
            ],
            [
                'name_fr' => 'Voyage',
                'name_ar' => 'سفر',
                'icon' => 'flight',
                'color' => '#3F51B5',
                'children' => ['Hôtel', 'Agence de voyage', 'Location voiture', 'Excursions', 'Camping'],
            ],
        ];

        $sortOrder = 1;
        foreach ($categories as $categoryData) {
            $parent = Category::create([
                'name_fr' => $categoryData['name_fr'],
                'name_ar' => $categoryData['name_ar'],
                'icon' => $categoryData['icon'],
                'color' => $categoryData['color'],
                'is_active' => true,
                'sort_order' => $sortOrder++,
            ]);

            $childSortOrder = 1;
            foreach ($categoryData['children'] as $childName) {
                Category::create([
                    'parent_id' => $parent->id,
                    'name_fr' => $childName,
                    'name_ar' => $childName, // Would need proper Arabic names
                    'icon' => $categoryData['icon'],
                    'color' => $categoryData['color'],
                    'is_active' => true,
                    'sort_order' => $childSortOrder++,
                ]);
            }
        }

        $this->command->info('Categories seeded successfully!');
    }
}
