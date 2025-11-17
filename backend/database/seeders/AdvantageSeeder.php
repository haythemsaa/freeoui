<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdvantageSeeder extends Seeder
{
    public function run(): void
    {
        $merchants = DB::table('merchants')->get();
        $categories = DB::table('categories')->get();

        $advantages = [
            [
                'title' => '-20% sur tous les plats',
                'title_ar' => 'تخفيض 20% على جميع الأطباق',
                'description' => 'Profitez de 20% de réduction sur l\'ensemble de notre carte',
                'description_ar' => 'استفد من تخفيض 20% على جميع قائمة الطعام',
                'type' => 'percentage',
                'discount_percentage' => 20,
                'merchant_id' => 1,
                'category_id' => 1,
                'days_available' => [1, 2, 3, 4, 5],
                'usage_limit' => 100,
            ],
            [
                'title' => '2 Cafés pour le prix d\'un',
                'title_ar' => 'قهوتان بسعر واحدة',
                'description' => 'Commandez 2 cafés et ne payez qu\'un seul',
                'description_ar' => 'اطلب قهوتين وادفع ثمن واحدة فقط',
                'type' => '2for1',
                'merchant_id' => 2,
                'category_id' => 1,
                'days_available' => [0, 6],
                'time_from' => '09:00',
                'time_until' => '12:00',
                'usage_limit' => 50,
            ],
            [
                'title' => 'Réduction de 30 TND sur l\'abonnement mensuel',
                'title_ar' => 'تخفيض 30 دينار على الاشتراك الشهري',
                'description' => 'Économisez 30 TND sur votre abonnement gym mensuel',
                'description_ar' => 'وفر 30 دينار على اشتراكك الشهري في الصالة الرياضية',
                'type' => 'fixed_amount',
                'discount_amount' => 30.00,
                'merchant_id' => 3,
                'category_id' => 6,
                'days_available' => [1, 2, 3, 4, 5],
                'usage_limit' => 20,
            ],
            [
                'title' => 'Manucure gratuite',
                'title_ar' => 'عناية بالأظافر مجانية',
                'description' => 'Manucure offerte pour tout soin beauté',
                'description_ar' => 'عناية بالأظافر مجانية مع أي خدمة تجميل',
                'type' => 'free_item',
                'merchant_id' => 4,
                'category_id' => 4,
                'days_available' => [0, 1, 2, 3, 4, 5, 6],
                'usage_limit' => 30,
            ],
            [
                'title' => '-15% sur toute commande',
                'title_ar' => 'تخفيض 15% على كل طلب',
                'description' => '15% de réduction sur votre commande pizza',
                'description_ar' => 'تخفيض 15% على طلب البيتزا',
                'type' => 'percentage',
                'discount_percentage' => 15,
                'merchant_id' => 5,
                'category_id' => 1,
                'days_available' => [0, 1, 2, 3, 4, 5, 6],
                'time_from' => '18:00',
                'time_until' => '23:00',
                'usage_limit' => 200,
            ],
        ];

        foreach ($advantages as $advantage) {
            DB::table('advantages')->insert([
                'title' => $advantage['title'],
                'title_ar' => $advantage['title_ar'],
                'description' => $advantage['description'],
                'description_ar' => $advantage['description_ar'],
                'type' => $advantage['type'],
                'discount_percentage' => $advantage['discount_percentage'] ?? null,
                'discount_amount' => $advantage['discount_amount'] ?? null,
                'valid_from' => Carbon::now(),
                'valid_until' => Carbon::now()->addMonths(3),
                'days_available' => json_encode($advantage['days_available']),
                'time_from' => $advantage['time_from'] ?? null,
                'time_until' => $advantage['time_until'] ?? null,
                'merchant_id' => $advantage['merchant_id'],
                'category_id' => $advantage['category_id'],
                'terms_conditions' => 'Offre valable une seule fois par utilisateur. Non cumulable avec d\'autres promotions.',
                'terms_conditions_ar' => 'العرض صالح مرة واحدة لكل مستخدم. لا يمكن دمجه مع عروض أخرى.',
                'usage_limit' => $advantage['usage_limit'],
                'usage_count' => rand(0, $advantage['usage_limit'] / 2),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
