<?php

namespace App\NeuroCore\Adapters;

use App\NeuroCore\NeuroHelper;
use App\NeuroCore\Data\NeuroResponse;
use App\Models\Organization;
use App\Models\User;

/**
 * AddyNeuroAdapter - High-level adapter for using Neuro in Addy
 * 
 * This is the main entry point for Addy to interact with Neuro.
 * It handles all the wiring and provides a simple interface.
 * 
 * Usage in Addy:
 *   $neuro = AddyNeuroAdapter::forUser($user, $organization);
 *   $response = $neuro->chat("I want to grow my business");
 */
class AddyNeuroAdapter
{
    private NeuroHelper $neuro;
    private Organization $organization;
    private User $user;

    private function __construct(User $user, Organization $organization)
    {
        $this->user = $user;
        $this->organization = $organization;

        // Set up storage (use cache for now, can switch to database later)
        $storage = new CacheStorage('neuro');

        // Set up AI provider
        $ai = new AddyAIProvider();

        // Set up data provider
        $dataProvider = new AddyDataProvider($organization, $user);

        // Create Neuro instance
        $this->neuro = NeuroHelper::create([
            'user_id' => $user->id,
            'system_context' => 'addy',
            'storage' => $storage,
            'ai' => $ai,
            'data_provider' => $dataProvider,
        ]);
    }

    /**
     * Create adapter for a user
     */
    public static function forUser(User $user, Organization $organization): self
    {
        return new self($user, $organization);
    }

    /**
     * Process a chat message
     */
    public function chat(string $message, array $context = []): array
    {
        // Add Addy-specific context
        $context['organization_id'] = $this->organization->id;
        $context['organization_name'] = $this->organization->name;
        $context['currency'] = $this->organization->currency ?? 'ZMW';

        // Get response from Neuro
        $response = $this->neuro->chat($message, $context);

        // Format for Addy's chat interface
        return $this->formatForAddy($response);
    }

    /**
     * Format Neuro response for Addy's chat UI
     */
    private function formatForAddy(NeuroResponse $response): array
    {
        $formatted = [
            'content' => $response->message,
            'assistance_type' => $response->assistanceType,
        ];

        // Convert guiding questions to a displayable format
        if (!empty($response->guidingQuestions)) {
            $questionsText = "\n\n**Questions to consider:**\n";
            foreach ($response->guidingQuestions as $q) {
                $question = is_array($q) ? $q['question'] : $q;
                $questionsText .= "- {$question}\n";
            }
            // Don't append if already in message
            if (!str_contains($response->message, 'Questions to consider')) {
                $formatted['content'] .= $questionsText;
            }
        }

        // Convert suggested steps to a displayable format
        if (!empty($response->suggestedSteps)) {
            $stepsText = "\n\n**Possible next steps:**\n";
            foreach ($response->suggestedSteps as $i => $step) {
                $stepText = is_array($step) ? $step['step'] : $step;
                $num = $i + 1;
                $stepsText .= "{$num}. {$stepText}\n";
            }
            // Don't append if already in message
            if (!str_contains($response->message, 'next steps')) {
                $formatted['content'] .= $stepsText;
            }
        }

        // Format quick actions
        $formatted['quick_actions'] = [];
        foreach ($response->quickActions as $action) {
            $formatted['quick_actions'][] = $action;
        }

        // Add default actions if none provided
        if (empty($formatted['quick_actions'])) {
            $formatted['quick_actions'] = [
                ['label' => 'Tell me more', 'command' => 'Can you elaborate?'],
                ['label' => 'Help me think through this', 'command' => 'Help me think through this step by step'],
            ];
        }

        // Goal suggestion
        if ($response->goalSuggestion) {
            $formatted['goal_suggestion'] = $response->goalSuggestion;
            $formatted['quick_actions'][] = [
                'label' => '🎯 Track this goal',
                'command' => 'Track goal: ' . ($response->goalSuggestion['description'] ?? ''),
            ];
        }

        // Context update notification
        if ($response->contextUpdate) {
            $formatted['context_update'] = $response->contextUpdate;
        }

        return $formatted;
    }

    /**
     * Get user's profile
     */
    public function getProfile(): array
    {
        return $this->neuro->getProfile()->toArray();
    }

    /**
     * Get active goals
     */
    public function getActiveGoals(): array
    {
        return array_map(
            fn($g) => $g->toArray(),
            $this->neuro->getActiveGoals()
        );
    }

    /**
     * Track a goal
     */
    public function trackGoal(string $description, array $context = []): array
    {
        $goal = $this->neuro->trackGoal($description, $context);
        return $goal->toArray();
    }

    /**
     * Update goal progress
     */
    public function updateGoalProgress(string $goalId, float $progress): ?array
    {
        $goal = $this->neuro->updateGoalProgress($goalId, $progress);
        return $goal?->toArray();
    }

    /**
     * Get conversation history
     */
    public function getHistory(int $limit = 50): array
    {
        return $this->neuro->getHistory($limit);
    }

    /**
     * Get proactive insight (if any)
     */
    public function getProactiveInsight(): ?array
    {
        if ($this->neuro->hasRelevantInsight()) {
            return $this->neuro->getProactiveInsight();
        }
        return null;
    }

    /**
     * Start a new conversation
     */
    public function startNewConversation(): string
    {
        return $this->neuro->startNewConversation();
    }

    /**
     * Export user data
     */
    public function exportUserData(): array
    {
        return $this->neuro->exportUserData();
    }

    /**
     * Get underlying Neuro instance (for advanced usage)
     */
    public function getNeuro(): NeuroHelper
    {
        return $this->neuro;
    }
}


