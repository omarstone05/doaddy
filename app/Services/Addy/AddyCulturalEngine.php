<?php

namespace App\Services\Addy;

use App\Models\Organization;
use App\Models\User;
use App\Models\AddyUserPattern;
use App\Models\AddyCulturalSetting;
use Carbon\Carbon;

class AddyCulturalEngine
{
    protected Organization $organization;
    protected User $user;
    protected AddyUserPattern $pattern;
    protected AddyCulturalSetting $settings;

    public function __construct(Organization $organization, User $user)
    {
        $this->organization = $organization;
        $this->user = $user;
        $this->pattern = AddyUserPattern::getOrCreate($organization->id, $user->id);
        $this->settings = AddyCulturalSetting::getOrCreate($organization->id);
    }

    /**
     * Get contextual greeting based on time and day
     */
    public function getContextualGreeting(): string
    {
        $hour = now($this->settings->timezone)->hour;
        $dayTheme = $this->pattern->getTodayTheme();

        $timeGreeting = match(true) {
            $hour < 12 => 'Good morning',
            $hour < 17 => 'Good afternoon',
            default => 'Good evening',
        };

        $culturalContext = $this->getCulturalContext();

        return "{$timeGreeting}! {$culturalContext}";
    }

    /**
     * Get cultural context message
     */
    protected function getCulturalContext(): string
    {
        $dayTheme = $this->pattern->getTodayTheme();
        $day = now()->format('l');

        $messages = [
            'monday' => "It's {$dayTheme['theme']} Monday - perfect for {$dayTheme['focus']}.",
            'tuesday' => "It's {$dayTheme['theme']} Tuesday - time to {$dayTheme['focus']}.",
            'wednesday' => "Midweek! It's {$dayTheme['theme']} Wednesday.",
            'thursday' => "Almost there! It's {$dayTheme['theme']} Thursday.",
            'friday' => "It's {$dayTheme['theme']} Friday - let's wrap up strong!",
            'saturday' => "It's the weekend! Time to {$dayTheme['focus']}.",
            'sunday' => "Sunday vibes - great for {$dayTheme['focus']}.",
        ];

        return $messages[strtolower($day)] ?? '';
    }

    /**
     * Get cultural settings
     */
    public function getSettings(): AddyCulturalSetting
    {
        return $this->settings;
    }
    
    /**
     * Resolve the active tone, falling back to organization preference
     */
    public function getTone(): string
    {
        return $this->settings->tone ?? $this->organization->tone_preference ?? 'professional';
    }

    /**
     * Get system instructions for the active tone
     */
    public function getToneInstructions(): string
    {
        $tone = $this->getTone();
        
        return match($tone) {
            'casual' => "You are Addy, a casual and friendly business assistant. 
                - Use colloquial language, emojis, and a relaxed vibe.
                - Address the user as 'friend' or by first name.
                - Keep responses short and punchy.
                - Avoid overly formal jargon unless necessary for precision.
                - Example: 'Hey! Looks like cash flow is tight. Let's fix that.'",
                
            'motivational' => "You are Addy, a high-energy business coach and cheerleader.
                - Use exclamation points, encouraging words, and positive reinforcement!
                - Focus on growth, potential, and winning.
                - Frame challenges as opportunities.
                - Use sports or fitness metaphors where appropriate.
                - Example: 'You're crushing it! Revenue is up, let's keep this momentum going!'",
                
            'sassy' => "You are Addy, a sharp-witted, no-nonsense business partner who spills the tea.
                - Be direct, slightly sarcastic, and fun.
                - Don't sugarcoat bad news - give it to them straight.
                - Use slang like 'real talk', 'receipts', 'tea', 'slay'.
                - Call out bad business decisions with humor.
                - Example: 'Real talk: that expense report is a mess. Let's clean it up before the auditors come knocking.'",
                
            'technical' => "You are Addy, a precise and analytical data scientist.
                - Focus purely on data, metrics, and logical conclusions.
                - Use precise terminology and avoid emotional language.
                - Structure responses with bullet points and data tables.
                - Cite specific numbers and percentages.
                - Example: 'Analysis indicates a 15% variance in projected cash flow. Recommended action: audit recurring expenses.'",
                
            default => "You are Addy, a professional, warm, and capable executive assistant.
                - Be polite, efficient, and supportive.
                - Use clear, professional business English.
                - Balance warmth with competence.
                - Focus on solutions and clarity.
                - Example: 'Good morning. I've noticed a discrepancy in the budget that requires your attention.'",
        };
    }

    /**
     * Adapt message tone based on settings (Legacy fallback)
     */
    public function adaptTone(string $message): string
    {
        // This is now primarily handled by the system prompt, 
        // but we keep this for non-LLM generated messages
        $tone = $this->getTone();

        return match($tone) {
            'casual', 'friendly' => $this->makeCasual($message),
            'motivational' => $this->makeMotivational($message),
            'sassy' => $this->makeSassy($message),
            'technical' => $this->makeTechnical($message),
            default => $message,
        };
    }

    /**
     * Chunk tasks for ADHD mode
     */
    public function chunkTasks(array $tasks): array
    {
        if (!$this->pattern->adhd_mode) {
            return [$tasks]; // Return all tasks in one chunk
        }

        $chunkSize = $this->pattern->preferred_task_chunk_size;
        return array_chunk($tasks, $chunkSize);
    }

    /**
     * Get proactive suggestion based on time and patterns
     */
    public function getProactiveSuggestion(): ?array
    {
        if (!$this->settings->enable_proactive_suggestions) {
            return null;
        }

        if ($this->settings->isInQuietHours()) {
            return null;
        }

        $hour = now()->hour;
        $dayTheme = $this->pattern->getTodayTheme();

        // Morning suggestions
        if ($hour >= 8 && $hour <= 10) {
            return [
                'message' => "Morning! Ready to plan your {$dayTheme['theme']} day?",
                'actions' => [
                    ['label' => '🎯 Show Focus', 'command' => 'What should I focus on today?'],
                    ['label' => '💰 Cash Check', 'command' => 'What is my cash position?'],
                ],
            ];
        }

        // Midday review
        if ($hour == 14 && strtolower(now()->format('l')) === 'thursday') {
            return [
                'message' => "Thursday afternoon - perfect time to review your week's progress!",
                'actions' => [
                    ['label' => '📊 View Insights', 'command' => 'Show me all insights'],
                ],
            ];
        }

        // Friday wrap-up
        if ($hour >= 15 && strtolower(now()->format('l')) === 'friday') {
            return [
                'message' => "Time to wrap up the week! Want to review your accomplishments?",
                'actions' => [
                    ['label' => '📈 Weekly Summary', 'command' => 'Show me this week\'s summary'],
                ],
            ];
        }

        return null;
    }

    /**
     * Check if should show prediction
     */
    public function shouldShowPredictions(): bool
    {
        return $this->settings->enable_predictions && $this->pattern->isInPeakHours();
    }

    /**
     * Get recommended focus for current day/time
     */
    public function getRecommendedFocus(): string
    {
        $dayTheme = $this->pattern->getTodayTheme();
        $hour = now()->hour;

        // Morning: Strategic work
        if ($hour >= 8 && $hour < 12) {
            return "Focus on {$dayTheme['focus']} - your peak productivity hours!";
        }

        // Afternoon: Execution
        if ($hour >= 13 && $hour < 17) {
            return "Good time for meetings and collaboration.";
        }

        // Evening: Wrap up
        if ($hour >= 17) {
            return "Wrap up tasks and plan for tomorrow.";
        }

        return "Take your time and work at your own pace.";
    }

    /**
     * Make message casual
     */
    protected function makeCasual(string $message): string
    {
        // Simple transformation - in practice, tone is handled by system prompt
        return $message;
    }

    /**
     * Make message motivational
     */
    protected function makeMotivational(string $message): string
    {
        // Simple transformation - in practice, tone is handled by system prompt
        return $message;
    }

    /**
     * Make message sassy
     */
    protected function makeSassy(string $message): string
    {
        // Simple transformation - in practice, tone is handled by system prompt
        return $message;
    }

    /**
     * Make message technical
     */
    protected function makeTechnical(string $message): string
    {
        // Simple transformation - in practice, tone is handled by system prompt
        return $message;
    }
}
