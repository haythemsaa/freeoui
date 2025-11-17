<?php

namespace Database\Seeders;

use App\Models\Challenge;
use Illuminate\Database\Seeder;

class ChallengeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $challenges = [
            // Weekly Challenges
            [
                'name' => 'Explorateur Hebdomadaire',
                'description' => 'Visitez 5 commerces différents cette semaine',
                'challenge_type' => 'visit_count',
                'criteria' => [
                    'target' => 5,
                    'time_window' => 'week',
                ],
                'reward_points' => 500,
                'badge_icon' => '🗺️',
                'starts_at' => now()->startOfWeek(),
                'ends_at' => now()->endOfWeek(),
                'is_active' => true,
                'is_recurring' => true,
                'recurrence_type' => 'weekly',
            ],
            [
                'name' => 'Acheteur Assidu',
                'description' => 'Dépensez 100 TND cette semaine',
                'challenge_type' => 'spend_amount',
                'criteria' => [
                    'target' => 100,
                    'currency' => 'TND',
                ],
                'reward_points' => 750,
                'badge_icon' => '💰',
                'starts_at' => now()->startOfWeek(),
                'ends_at' => now()->endOfWeek(),
                'is_active' => true,
                'is_recurring' => true,
                'recurrence_type' => 'weekly',
            ],
            [
                'name' => 'Série de Check-ins',
                'description' => 'Maintenez une série de 7 jours consécutifs',
                'challenge_type' => 'checkin_streak',
                'criteria' => [
                    'target' => 7,
                ],
                'reward_points' => 1000,
                'badge_icon' => '🔥',
                'starts_at' => now()->startOfWeek(),
                'ends_at' => now()->endOfWeek(),
                'is_active' => true,
                'is_recurring' => true,
                'recurrence_type' => 'weekly',
            ],

            // Monthly Challenges
            [
                'name' => 'Découvreur de Catégories',
                'description' => 'Visitez des commerces dans 5 catégories différentes',
                'challenge_type' => 'category_explore',
                'criteria' => [
                    'target' => 5,
                ],
                'reward_points' => 1500,
                'badge_icon' => '🎯',
                'starts_at' => now()->startOfMonth(),
                'ends_at' => now()->endOfMonth(),
                'is_active' => true,
                'is_recurring' => true,
                'recurrence_type' => 'monthly',
            ],
            [
                'name' => 'Voyageur Tunisien',
                'description' => 'Visitez des commerces dans 3 gouvernorats différents',
                'challenge_type' => 'governorate_explore',
                'criteria' => [
                    'target' => 3,
                ],
                'reward_points' => 2000,
                'badge_icon' => '🌍',
                'starts_at' => now()->startOfMonth(),
                'ends_at' => now()->endOfMonth(),
                'is_active' => true,
                'is_recurring' => true,
                'recurrence_type' => 'monthly',
            ],
            [
                'name' => 'Grand Explorateur',
                'description' => 'Effectuez 20 visites ce mois-ci',
                'challenge_type' => 'visit_count',
                'criteria' => [
                    'target' => 20,
                ],
                'reward_points' => 2500,
                'badge_icon' => '⭐',
                'starts_at' => now()->startOfMonth(),
                'ends_at' => now()->endOfMonth(),
                'is_active' => true,
                'is_recurring' => true,
                'recurrence_type' => 'monthly',
            ],
            [
                'name' => 'Super Acheteur',
                'description' => 'Dépensez 500 TND ce mois-ci',
                'challenge_type' => 'spend_amount',
                'criteria' => [
                    'target' => 500,
                    'currency' => 'TND',
                ],
                'reward_points' => 3000,
                'badge_icon' => '💎',
                'starts_at' => now()->startOfMonth(),
                'ends_at' => now()->endOfMonth(),
                'is_active' => true,
                'is_recurring' => true,
                'recurrence_type' => 'monthly',
            ],

            // Special/Limited Time Challenges
            [
                'name' => 'Challenge Ramadan',
                'description' => 'Visitez 10 restaurants pendant le mois de Ramadan',
                'challenge_type' => 'visit_count',
                'criteria' => [
                    'target' => 10,
                    'category_filter' => 'restaurant',
                ],
                'reward_points' => 5000,
                'badge_icon' => '🌙',
                'starts_at' => now(),
                'ends_at' => now()->addMonth(),
                'is_active' => false, // Activate during Ramadan
                'is_recurring' => false,
                'recurrence_type' => null,
            ],
            [
                'name' => 'Challenge Nouvel An',
                'description' => 'Commencez l\'année en force : 15 visites en janvier',
                'challenge_type' => 'visit_count',
                'criteria' => [
                    'target' => 15,
                ],
                'reward_points' => 4000,
                'badge_icon' => '🎉',
                'starts_at' => now(),
                'ends_at' => now()->addMonth(),
                'is_active' => false, // Activate in January
                'is_recurring' => false,
                'recurrence_type' => null,
            ],
            [
                'name' => 'Marathon des Économies',
                'description' => 'Économisez 50 TND grâce aux avantages ce mois-ci',
                'challenge_type' => 'spend_amount',
                'criteria' => [
                    'target' => 50,
                    'type' => 'savings',
                ],
                'reward_points' => 1000,
                'badge_icon' => '💸',
                'starts_at' => now()->startOfMonth(),
                'ends_at' => now()->endOfMonth(),
                'is_active' => true,
                'is_recurring' => true,
                'recurrence_type' => 'monthly',
            ],

            // Beginner Challenges
            [
                'name' => 'Premier Pas',
                'description' => 'Effectuez votre première visite',
                'challenge_type' => 'visit_count',
                'criteria' => [
                    'target' => 1,
                ],
                'reward_points' => 100,
                'badge_icon' => '👣',
                'starts_at' => now(),
                'ends_at' => now()->addYear(),
                'is_active' => true,
                'is_recurring' => false,
                'recurrence_type' => null,
            ],
            [
                'name' => 'Social Butterfly',
                'description' => 'Partagez FreeOui avec 3 amis',
                'challenge_type' => 'visit_count',
                'criteria' => [
                    'target' => 3,
                    'type' => 'referrals',
                ],
                'reward_points' => 800,
                'badge_icon' => '🦋',
                'starts_at' => now(),
                'ends_at' => now()->addYear(),
                'is_active' => true,
                'is_recurring' => false,
                'recurrence_type' => null,
            ],
        ];

        foreach ($challenges as $challenge) {
            Challenge::create($challenge);
        }

        $this->command->info('✅ Challenges seeded successfully!');
    }
}
