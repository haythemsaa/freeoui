<?php

namespace Database\Seeders;

use App\Models\Achievement;
use Illuminate\Database\Seeder;

class AchievementSeeder extends Seeder
{
    public function run(): void
    {
        $achievements = [
            // Beginner Achievements
            [
                'name_fr' => 'Premier Pas',
                'name_ar' => 'الخطوة الأولى',
                'description_fr' => 'Créer votre compte FreeOui',
                'description_ar' => 'إنشاء حسابك على FreeOui',
                'icon' => 'account_circle',
                'points' => 100,
                'category' => 'onboarding',
                'criteria' => json_encode(['type' => 'register']),
            ],
            [
                'name_fr' => 'Explorateur',
                'name_ar' => 'مستكشف',
                'description_fr' => 'Consulter 10 avantages',
                'description_ar' => 'مشاهدة 10 عروض',
                'icon' => 'explore',
                'points' => 50,
                'category' => 'engagement',
                'criteria' => json_encode(['type' => 'view_advantages', 'count' => 10]),
            ],
            [
                'name_fr' => 'Premier Scan',
                'name_ar' => 'أول مسح',
                'description_fr' => 'Scanner votre premier QR code',
                'description_ar' => 'مسح أول رمز QR',
                'icon' => 'qr_code_scanner',
                'points' => 200,
                'category' => 'transaction',
                'criteria' => json_encode(['type' => 'first_scan']),
            ],

            // Engagement Achievements
            [
                'name_fr' => 'Favori',
                'name_ar' => 'المفضل',
                'description_fr' => 'Ajouter 5 avantages aux favoris',
                'description_ar' => 'إضافة 5 عروض إلى المفضلة',
                'icon' => 'favorite',
                'points' => 100,
                'category' => 'engagement',
                'criteria' => json_encode(['type' => 'favorites', 'count' => 5]),
            ],
            [
                'name_fr' => 'Critique',
                'name_ar' => 'ناقد',
                'description_fr' => 'Laisser 3 avis',
                'description_ar' => 'ترك 3 تقييمات',
                'icon' => 'rate_review',
                'points' => 150,
                'category' => 'engagement',
                'criteria' => json_encode(['type' => 'reviews', 'count' => 3]),
            ],
            [
                'name_fr' => 'Habitué',
                'name_ar' => 'معتاد',
                'description_fr' => 'Se connecter 7 jours consécutifs',
                'description_ar' => 'تسجيل الدخول لمدة 7 أيام متتالية',
                'icon' => 'event_available',
                'points' => 300,
                'category' => 'loyalty',
                'criteria' => json_encode(['type' => 'streak_days', 'count' => 7]),
            ],

            // Transaction Achievements
            [
                'name_fr' => 'Économe Bronze',
                'name_ar' => 'موفر برونزي',
                'description_fr' => 'Économiser 100 TND',
                'description_ar' => 'توفير 100 دينار',
                'icon' => 'savings',
                'points' => 500,
                'category' => 'savings',
                'criteria' => json_encode(['type' => 'savings', 'amount' => 100]),
            ],
            [
                'name_fr' => 'Économe Argent',
                'name_ar' => 'موفر فضي',
                'description_fr' => 'Économiser 500 TND',
                'description_ar' => 'توفير 500 دينار',
                'icon' => 'savings',
                'points' => 1000,
                'category' => 'savings',
                'criteria' => json_encode(['type' => 'savings', 'amount' => 500]),
            ],
            [
                'name_fr' => 'Économe Or',
                'name_ar' => 'موفر ذهبي',
                'description_fr' => 'Économiser 1000 TND',
                'description_ar' => 'توفير 1000 دينار',
                'icon' => 'savings',
                'points' => 2000,
                'category' => 'savings',
                'criteria' => json_encode(['type' => 'savings', 'amount' => 1000]),
            ],

            // Social Achievements
            [
                'name_fr' => 'Influenceur',
                'name_ar' => 'مؤثر',
                'description_fr' => 'Partager 5 avantages',
                'description_ar' => 'مشاركة 5 عروض',
                'icon' => 'share',
                'points' => 200,
                'category' => 'social',
                'criteria' => json_encode(['type' => 'shares', 'count' => 5]),
            ],
            [
                'name_fr' => 'Ambassadeur',
                'name_ar' => 'سفير',
                'description_fr' => 'Parrainer 3 amis',
                'description_ar' => 'دعوة 3 أصدقاء',
                'icon' => 'group_add',
                'points' => 500,
                'category' => 'social',
                'criteria' => json_encode(['type' => 'referrals', 'count' => 3]),
            ],

            // Advanced Achievements
            [
                'name_fr' => 'Acheteur Fréquent',
                'name_ar' => 'مشتري متكرر',
                'description_fr' => 'Effectuer 10 transactions',
                'description_ar' => 'إجراء 10 معاملات',
                'icon' => 'shopping_cart',
                'points' => 1000,
                'category' => 'transaction',
                'criteria' => json_encode(['type' => 'transactions', 'count' => 10]),
            ],
            [
                'name_fr' => 'VIP',
                'name_ar' => 'كبار الشخصيات',
                'description_fr' => 'Atteindre le niveau 10',
                'description_ar' => 'الوصول إلى المستوى 10',
                'icon' => 'workspace_premium',
                'points' => 5000,
                'category' => 'loyalty',
                'criteria' => json_encode(['type' => 'level', 'count' => 10]),
            ],
        ];

        foreach ($achievements as $achievement) {
            Achievement::create($achievement);
        }

        $this->command->info('Achievements seeded successfully!');
    }
}
