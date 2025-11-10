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
     * Adapt message tone based on settings
     */
    public function adaptTone(string $message): string
    {
        $tone = $this->settings->tone;

        return match($tone) {
            'casual' => $this->makeCasual($message),
            'motivational' => $this->makeMotivational($message),
            default => $message, // professional - keep as is
        };
    }

    protected function makeCasual(string $message): string
    {
        $replacements = [
            'Hello' => 'Hey',
            'Good morning' => 'Morning',
            'You have' => "You've got",
            'I recommend' => "I'd suggest",
            'Please' => '',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $message);
    }

    protected function makeMotivational(string $message): string
    {
        $motivational = [
            "Let's crush it! ",
            "You've got this! ",
            "Keep up the great work! ",
            "Excellent progress! ",
        ];

        return $motivational[array_rand($motivational)] . $message;
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
}

