<?php

namespace App\Services\Onboarding;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * AI-powered business classification service
 * Uses pattern matching and AI to classify businesses
 */
class BusinessClassifierService
{
    protected array $categories = [
        'retail' => [
            'keywords' => ['shop', 'store', 'sell', 'retail', 'boutique', 'goods', 'products', 'merchandise'],
            'patterns' => ['we sell', 'selling', 'retail', 'shop'],
        ],
        'consulting' => [
            'keywords' => ['consulting', 'consultant', 'advisory', 'services', 'professional services', 'help businesses'],
            'patterns' => ['we help', 'we advise', 'consulting', 'professional services'],
        ],
        'agriculture' => [
            'keywords' => ['farm', 'agriculture', 'crops', 'livestock', 'produce', 'farming', 'harvest', 'vegetables', 'fruits'],
            'patterns' => ['we grow', 'we farm', 'produce'],
        ],
        'hospitality' => [
            'keywords' => ['restaurant', 'hotel', 'food', 'catering', 'lodge', 'cafe', 'bar', 'hospitality'],
            'patterns' => ['we serve food', 'restaurant', 'hotel'],
        ],
        'construction' => [
            'keywords' => ['construction', 'building', 'contractor', 'engineering', 'builder', 'renovations'],
            'patterns' => ['we build', 'construction', 'contractor'],
        ],
        'education' => [
            'keywords' => ['school', 'education', 'training', 'tutoring', 'teaching', 'academy', 'learning'],
            'patterns' => ['we teach', 'we train', 'education'],
        ],
        'health' => [
            'keywords' => ['clinic', 'medical', 'health', 'hospital', 'pharmacy', 'healthcare', 'doctor', 'nurse'],
            'patterns' => ['healthcare', 'medical', 'clinic'],
        ],
        'transport' => [
            'keywords' => ['transport', 'logistics', 'delivery', 'shipping', 'freight', 'courier'],
            'patterns' => ['we transport', 'we deliver', 'logistics'],
        ],
        'technology' => [
            'keywords' => ['software', 'technology', 'digital', 'app', 'website', 'tech', 'IT', 'development'],
            'patterns' => ['we develop', 'software', 'technology'],
        ],
        'beauty' => [
            'keywords' => ['salon', 'beauty', 'spa', 'hair', 'makeup', 'cosmetics', 'barbershop'],
            'patterns' => ['beauty', 'salon', 'spa'],
        ],
        'manufacturing' => [
            'keywords' => ['manufacturing', 'factory', 'production', 'assembly', 'fabrication', 'maker'],
            'patterns' => ['we manufacture', 'we produce', 'factory'],
        ],
        'finance' => [
            'keywords' => ['finance', 'accounting', 'insurance', 'banking', 'investment', 'loans'],
            'patterns' => ['financial', 'accounting', 'insurance'],
        ],
        'creative' => [
            'keywords' => ['design', 'creative', 'media', 'marketing', 'advertising', 'branding', 'graphics'],
            'patterns' => ['we design', 'creative', 'marketing'],
        ],
        'ngo' => [
            'keywords' => ['NGO', 'charity', 'nonprofit', 'community', 'foundation', 'aid', 'help people'],
            'patterns' => ['non-profit', 'community', 'charity'],
        ],
    ];

    /**
     * Classify business based on description
     */
    public function classify(string $description): string
    {
        $description = strtolower(trim($description));

        // First try: Pattern matching
        $patternMatch = $this->matchByPattern($description);
        if ($patternMatch) {
            return $this->humanizeCategory($patternMatch);
        }

        // Second try: Keyword scoring
        $keywordMatch = $this->matchByKeywords($description);
        if ($keywordMatch) {
            return $this->humanizeCategory($keywordMatch);
        }

        // Third try: Use AI (optional)
        if (config('services.openai.enabled', false)) {
            $aiMatch = $this->classifyWithAI($description);
            if ($aiMatch) {
                return $this->humanizeCategory($aiMatch);
            }
        }

        // Fallback
        return 'General Business';
    }

    /**
     * Match by patterns
     */
    protected function matchByPattern(string $description): ?string
    {
        foreach ($this->categories as $category => $config) {
            foreach ($config['patterns'] as $pattern) {
                if (str_contains($description, strtolower($pattern))) {
                    return $category;
                }
            }
        }

        return null;
    }

    /**
     * Match by keyword scoring
     */
    protected function matchByKeywords(string $description): ?string
    {
        $scores = [];

        foreach ($this->categories as $category => $config) {
            $score = 0;

            foreach ($config['keywords'] as $keyword) {
                if (str_contains($description, strtolower($keyword))) {
                    $score += str_word_count($keyword); // Longer keywords = more weight
                }
            }

            if ($score > 0) {
                $scores[$category] = $score;
            }
        }

        if (empty($scores)) {
            return null;
        }

        // Return category with highest score
        arsort($scores);
        return array_key_first($scores);
    }

    /**
     * Classify using OpenAI
     */
    protected function classifyWithAI(string $description): ?string
    {
        try {
            $categories = implode(', ', array_keys($this->categories));

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.openai.key'),
                'Content-Type' => 'application/json',
            ])->timeout(10)->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => "You are a business classification assistant. Classify businesses into one of these categories: {$categories}. Respond with ONLY the category name, nothing else.",
                    ],
                    [
                        'role' => 'user',
                        'content' => "Classify this business: {$description}",
                    ],
                ],
                'temperature' => 0.3,
                'max_tokens' => 20,
            ]);

            if ($response->successful()) {
                $category = strtolower(trim($response->json()['choices'][0]['message']['content']));
                
                // Validate it's a known category
                if (array_key_exists($category, $this->categories)) {
                    return $category;
                }
            }
        } catch (\Exception $e) {
            Log::warning('AI classification failed', [
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Convert category slug to human-readable format
     */
    protected function humanizeCategory(string $category): string
    {
        $humanized = [
            'retail' => 'Retail / Shop / Store',
            'consulting' => 'Services & Consulting',
            'agriculture' => 'Agriculture & Farming',
            'hospitality' => 'Hospitality & Food',
            'construction' => 'Construction & Engineering',
            'education' => 'Education / Training',
            'health' => 'Health & Medical',
            'transport' => 'Transport & Logistics',
            'technology' => 'Technology & Software',
            'beauty' => 'Beauty & Personal Care',
            'manufacturing' => 'Manufacturing & Production',
            'finance' => 'Finance / Insurance',
            'creative' => 'Creative / Media',
            'ngo' => 'NGO / Community Work',
        ];

        return $humanized[$category] ?? ucfirst($category);
    }

    /**
     * Get all available categories for selection
     */
    public function getAllCategories(): array
    {
        return array_map(
            fn($cat) => $this->humanizeCategory($cat),
            array_keys($this->categories)
        );
    }
}

