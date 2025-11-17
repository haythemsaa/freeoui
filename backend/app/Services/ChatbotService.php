<?php

namespace App\Services;

use App\Models\User;
use App\Models\Advantage;
use App\Models\Merchant;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ChatbotService
{
    public function __construct(
        private RecommendationService $recommendationService
    ) {}

    /**
     * Process user message and generate response
     */
    public function processMessage(User $user, string $message): array
    {
        $message = strtolower(trim($message));
        $intent = $this->detectIntent($message);

        return match($intent) {
            'greeting' => $this->handleGreeting($user),
            'help' => $this->handleHelp(),
            'search_advantage' => $this->handleSearchAdvantage($message),
            'search_merchant' => $this->handleSearchMerchant($message),
            'my_points' => $this->handleMyPoints($user),
            'my_favorites' => $this->handleMyFavorites($user),
            'recommendations' => $this->handleRecommendations($user),
            'nearby' => $this->handleNearby($user),
            'how_to_use' => $this->handleHowToUse(),
            'contact_support' => $this->handleContactSupport(),
            'subscription' => $this->handleSubscription($user),
            'challenges' => $this->handleChallenges($user),
            default => $this->handleFallback($message),
        };
    }

    /**
     * Detect user intent from message
     */
    private function detectIntent(string $message): string
    {
        $patterns = [
            'greeting' => ['bonjour', 'salut', 'hello', 'hi', 'hey'],
            'help' => ['aide', 'help', 'aider', 'besoin'],
            'search_advantage' => ['cherche', 'trouve', 'offre', 'promo', 'réduction', 'avantage'],
            'search_merchant' => ['restaurant', 'magasin', 'commerce', 'boutique', 'où'],
            'my_points' => ['points', 'solde', 'balance', 'niveau', 'level'],
            'my_favorites' => ['favoris', 'préféré', 'saved', 'mes offres'],
            'recommendations' => ['recommande', 'suggère', 'propose', 'pour moi'],
            'nearby' => ['proche', 'près', 'proximité', 'autour', 'nearby'],
            'how_to_use' => ['comment', 'utiliser', 'fonctionne', 'marche'],
            'contact_support' => ['contact', 'support', 'problème', 'bug', 'erreur'],
            'subscription' => ['abonnement', 'premium', 'freeoui plus', 'subscription'],
            'challenges' => ['challenge', 'défi', 'mission', 'quête'],
        ];

        foreach ($patterns as $intent => $keywords) {
            foreach ($keywords as $keyword) {
                if (Str::contains($message, $keyword)) {
                    return $intent;
                }
            }
        }

        return 'unknown';
    }

    /**
     * Handle greeting
     */
    private function handleGreeting(User $user): array
    {
        $greetings = [
            "Bonjour {$user->first_name} ! 👋 Comment puis-je vous aider aujourd'hui ?",
            "Salut {$user->first_name} ! Que recherchez-vous ?",
            "Hello {$user->first_name} ! Je suis là pour vous aider à trouver les meilleures offres.",
        ];

        return [
            'message' => $greetings[array_rand($greetings)],
            'type' => 'text',
            'quick_replies' => [
                'Voir mes points',
                'Recommandations',
                'Offres à proximité',
                'Aide',
            ],
        ];
    }

    /**
     * Handle help request
     */
    private function handleHelp(): array
    {
        return [
            'message' => "Je peux vous aider avec :\n\n" .
                "🔍 Rechercher des offres\n" .
                "📍 Trouver des commerces à proximité\n" .
                "⭐ Voir vos favoris\n" .
                "🎯 Obtenir des recommandations\n" .
                "💰 Consulter vos points\n" .
                "🏆 Suivre vos challenges\n" .
                "💎 Gérer votre abonnement\n\n" .
                "Que voulez-vous faire ?",
            'type' => 'text',
            'quick_replies' => [
                'Mes points',
                'Recommandations',
                'Challenges',
                'Abonnement',
            ],
        ];
    }

    /**
     * Handle search advantage
     */
    private function handleSearchAdvantage(string $message): array
    {
        // Extract keywords from message
        $keywords = $this->extractKeywords($message);

        $advantages = Advantage::where('is_active', true)
            ->where('ends_at', '>', now())
            ->where(function ($query) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $query->orWhere('title', 'like', "%{$keyword}%")
                        ->orWhere('description', 'like', "%{$keyword}%");
                }
            })
            ->with(['merchant', 'category'])
            ->limit(5)
            ->get();

        if ($advantages->isEmpty()) {
            return [
                'message' => "Je n'ai pas trouvé d'offres correspondant à votre recherche. Voulez-vous voir les recommandations ?",
                'type' => 'text',
                'quick_replies' => ['Oui, recommandations', 'Offres populaires'],
            ];
        }

        return [
            'message' => "J'ai trouvé {$advantages->count()} offre(s) pour vous :",
            'type' => 'list',
            'items' => $advantages->map(function ($adv) {
                return [
                    'id' => $adv->id,
                    'title' => $adv->title,
                    'merchant' => $adv->merchant->name,
                    'discount' => $adv->discount_percentage . '%',
                    'ends_at' => $adv->ends_at->diffForHumans(),
                ];
            })->toArray(),
        ];
    }

    /**
     * Handle search merchant
     */
    private function handleSearchMerchant(string $message): array
    {
        $keywords = $this->extractKeywords($message);

        $merchants = Merchant::where('is_active', true)
            ->where(function ($query) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $query->orWhere('name', 'like', "%{$keyword}%")
                        ->orWhere('description', 'like', "%{$keyword}%");
                }
            })
            ->withCount('advantages')
            ->having('advantages_count', '>', 0)
            ->limit(5)
            ->get();

        if ($merchants->isEmpty()) {
            return [
                'message' => "Aucun commerce trouvé. Voulez-vous voir les commerces populaires ?",
                'type' => 'text',
                'quick_replies' => ['Oui', 'Rechercher autre chose'],
            ];
        }

        return [
            'message' => "Voici {$merchants->count()} commerce(s) :",
            'type' => 'list',
            'items' => $merchants->map(function ($merchant) {
                return [
                    'id' => $merchant->id,
                    'name' => $merchant->name,
                    'advantages_count' => $merchant->advantages_count,
                    'category' => $merchant->category->name ?? 'N/A',
                ];
            })->toArray(),
        ];
    }

    /**
     * Handle my points request
     */
    private function handleMyPoints(User $user): array
    {
        $pointsToNextLevel = $this->getPointsToNextLevel($user);

        $message = "💰 Votre solde :\n\n" .
            "Points : {$user->points_balance}\n" .
            "Niveau : {$user->level}\n" .
            "Coins : {$user->coins_balance}\n";

        if ($pointsToNextLevel) {
            $message .= "\n🎯 Plus que {$pointsToNextLevel} points pour le niveau " . ($user->level + 1) . " !";
        }

        return [
            'message' => $message,
            'type' => 'text',
            'quick_replies' => ['Comment gagner des points ?', 'Mes challenges'],
        ];
    }

    /**
     * Handle my favorites
     */
    private function handleMyFavorites(User $user): array
    {
        $favorites = $user->favorites()
            ->with('advantage.merchant')
            ->whereHas('advantage', function ($query) {
                $query->where('is_active', true)
                    ->where('ends_at', '>', now());
            })
            ->limit(5)
            ->get();

        if ($favorites->isEmpty()) {
            return [
                'message' => "Vous n'avez pas encore de favoris. Voulez-vous voir des recommandations ?",
                'type' => 'text',
                'quick_replies' => ['Oui, montrez-moi', 'Rechercher des offres'],
            ];
        }

        return [
            'message' => "⭐ Vos {$favorites->count()} favoris :",
            'type' => 'list',
            'items' => $favorites->map(function ($fav) {
                $adv = $fav->advantage;
                return [
                    'id' => $adv->id,
                    'title' => $adv->title,
                    'merchant' => $adv->merchant->name,
                    'discount' => $adv->discount_percentage . '%',
                    'ends_soon' => $adv->ends_at->diffInDays(now()) <= 2,
                ];
            })->toArray(),
        ];
    }

    /**
     * Handle recommendations request
     */
    private function handleRecommendations(User $user): array
    {
        $recommendations = $this->recommendationService->getPersonalizedRecommendations($user, 5);

        if ($recommendations->isEmpty()) {
            return [
                'message' => "Pas encore assez de données pour des recommandations personnalisées. Voici les offres populaires !",
                'type' => 'text',
            ];
        }

        return [
            'message' => "✨ Recommandations pour vous :",
            'type' => 'list',
            'items' => $recommendations->map(function ($adv) {
                return [
                    'id' => $adv->id,
                    'title' => $adv->title,
                    'merchant' => $adv->merchant->name,
                    'discount' => $adv->discount_percentage . '%',
                    'reason' => $adv->recommendation_reason ?? 'Pour vous',
                ];
            })->toArray(),
        ];
    }

    /**
     * Handle nearby request
     */
    private function handleNearby(User $user): array
    {
        $latestLocation = $user->latestLocation;

        if (!$latestLocation) {
            return [
                'message' => "Je n'ai pas votre position actuelle. Activez la géolocalisation pour voir les offres à proximité.",
                'type' => 'text',
            ];
        }

        return [
            'message' => "📍 Je cherche des offres à proximité...",
            'type' => 'text',
            'action' => 'fetch_nearby',
        ];
    }

    /**
     * Handle how to use
     */
    private function handleHowToUse(): array
    {
        return [
            'message' => "🎓 Comment utiliser FreeOui :\n\n" .
                "1️⃣ Parcourez les offres et ajoutez vos favoris\n" .
                "2️⃣ Générez un QR code pour valider l'offre en magasin\n" .
                "3️⃣ Gagnez des points à chaque achat\n" .
                "4️⃣ Montez de niveau et débloquez des avantages\n" .
                "5️⃣ Partagez avec vos amis pour plus de points !\n\n" .
                "Besoin d'aide sur un point spécifique ?",
            'type' => 'text',
            'quick_replies' => ['QR codes', 'Points', 'Challenges', 'Abonnement'],
        ];
    }

    /**
     * Handle contact support
     */
    private function handleContactSupport(): array
    {
        return [
            'message' => "📞 Support FreeOui :\n\n" .
                "Email : support@freeoui.tn\n" .
                "Téléphone : +216 XX XXX XXX\n" .
                "Disponible : Lun-Ven 9h-18h\n\n" .
                "Ou décrivez votre problème et nous vous contacterons.",
            'type' => 'text',
            'action' => 'open_contact_form',
        ];
    }

    /**
     * Handle subscription info
     */
    private function handleSubscription(User $user): array
    {
        if ($user->is_premium) {
            return [
                'message' => "💎 Vous êtes membre FreeOui Plus !\n\n" .
                    "Avantages actifs :\n" .
                    "✅ -15% sur tous vos achats\n" .
                    "✅ Accès prioritaire aux offres\n" .
                    "✅ Support client dédié\n\n" .
                    "Voulez-vous gérer votre abonnement ?",
                'type' => 'text',
                'quick_replies' => ['Voir mon abonnement', 'Avantages Premium'],
            ];
        }

        return [
            'message' => "💎 FreeOui Plus - 9.90 TND/mois\n\n" .
                "Avantages :\n" .
                "✅ -15% sur tous vos achats\n" .
                "✅ Coins x2 sur check-ins\n" .
                "✅ Accès aux offres exclusives\n" .
                "✅ Support prioritaire\n\n" .
                "Voulez-vous essayer ?",
            'type' => 'text',
            'quick_replies' => ['S\'abonner', 'En savoir plus'],
            'action' => 'show_subscription_plans',
        ];
    }

    /**
     * Handle challenges info
     */
    private function handleChallenges(User $user): array
    {
        $activeChallenges = $user->challengeParticipations()
            ->where('status', 'active')
            ->with('challenge')
            ->get();

        $message = "🏆 Vos Challenges :\n\n";

        if ($activeChallenges->isEmpty()) {
            $message .= "Vous n'avez pas de challenges actifs. Voulez-vous voir les challenges disponibles ?";
        } else {
            foreach ($activeChallenges as $participation) {
                $challenge = $participation->challenge;
                $message .= "• {$challenge->name} ({$participation->progress_percentage}%)\n";
            }
        }

        return [
            'message' => $message,
            'type' => 'text',
            'quick_replies' => ['Voir tous les challenges', 'Mes récompenses'],
        ];
    }

    /**
     * Handle unknown/fallback
     */
    private function handleFallback(string $message): array
    {
        return [
            'message' => "Je n'ai pas bien compris. Pouvez-vous reformuler ?\n\n" .
                "Je peux vous aider avec :\n" .
                "• Rechercher des offres\n" .
                "• Voir vos points et favoris\n" .
                "• Recommandations personnalisées\n" .
                "• Informations sur l'abonnement",
            'type' => 'text',
            'quick_replies' => ['Aide', 'Recommandations', 'Mes points'],
        ];
    }

    /**
     * Extract keywords from message
     */
    private function extractKeywords(string $message): array
    {
        $stopWords = ['le', 'la', 'les', 'un', 'une', 'des', 'de', 'du', 'pour', 'avec', 'dans', 'sur'];
        $words = explode(' ', $message);

        return array_filter($words, function ($word) use ($stopWords) {
            return strlen($word) > 2 && !in_array($word, $stopWords);
        });
    }

    /**
     * Get points to next level
     */
    private function getPointsToNextLevel(User $user): ?int
    {
        $levels = [
            1 => 0,
            2 => 100,
            3 => 500,
            4 => 1000,
            5 => 2500,
            6 => 5000,
            7 => 10000,
        ];

        $nextLevel = $user->level + 1;

        if (!isset($levels[$nextLevel])) {
            return null;
        }

        return $levels[$nextLevel] - $user->points_balance;
    }

    /**
     * Get conversation context
     */
    public function getContext(User $user): array
    {
        return [
            'user' => [
                'name' => $user->first_name,
                'points' => $user->points_balance,
                'level' => $user->level,
                'is_premium' => $user->is_premium,
            ],
            'quick_actions' => [
                'Mes points',
                'Recommandations',
                'Offres à proximité',
                'Mes challenges',
            ],
        ];
    }
}
