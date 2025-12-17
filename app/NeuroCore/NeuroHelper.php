<?php

namespace App\NeuroCore;

use App\NeuroCore\Contracts\AIProviderInterface;
use App\NeuroCore\Contracts\StorageInterface;
use App\NeuroCore\Contracts\DataProviderInterface;
use App\NeuroCore\Context\UserProfile;
use App\NeuroCore\Context\ConversationMemory;
use App\NeuroCore\Understanding\MessageAnalyzer;
use App\NeuroCore\Response\EmpowermentEngine;
use App\NeuroCore\Data\NeuroResponse;
use App\NeuroCore\Data\NeuroUnderstanding;
use App\NeuroCore\Data\Goal;

/**
 * NeuroHelper - The main entry point for the Neuro system
 * 
 * A chat-triggered, user-centric helper that:
 * - Understands user goals, needs, and wants
 * - Builds context from conversations
 * - Guides users to achieve their goals (doesn't do it for them)
 * - Can be dropped into any system (Addy, Projjo, etc.)
 * 
 * Usage:
 *   $neuro = NeuroHelper::create($config);
 *   $response = $neuro->chat("I want to grow my business");
 */
class NeuroHelper
{
    private string $userId;
    private string $systemContext;
    private UserProfile $profile;
    private ConversationMemory $memory;
    private MessageAnalyzer $analyzer;
    private EmpowermentEngine $empowerment;
    private ?DataProviderInterface $dataProvider;
    private StorageInterface $storage;
    private AIProviderInterface $ai;

    /**
     * Private constructor - use create() or forUser()
     */
    private function __construct(
        string $userId,
        string $systemContext,
        StorageInterface $storage,
        AIProviderInterface $ai,
        ?DataProviderInterface $dataProvider = null
    ) {
        $this->userId = $userId;
        $this->systemContext = $systemContext;
        $this->storage = $storage;
        $this->ai = $ai;
        $this->dataProvider = $dataProvider;

        // Initialize components
        $this->profile = UserProfile::loadOrCreate($userId, $storage);
        $this->memory = new ConversationMemory($userId, $systemContext, $storage);
        $this->analyzer = new MessageAnalyzer($ai);
        $this->empowerment = new EmpowermentEngine($ai);

        // Enrich profile from data provider if available
        if ($dataProvider) {
            $this->profile->enrichFromSystem($dataProvider);
        }
    }

    /**
     * Create a new NeuroHelper instance
     * 
     * @param array $config Configuration array:
     *   - user_id: string (required)
     *   - system_context: string (e.g., 'addy', 'projjo')
     *   - storage: StorageInterface
     *   - ai: AIProviderInterface
     *   - data_provider: DataProviderInterface (optional)
     */
    public static function create(array $config): self
    {
        if (!isset($config['user_id'])) {
            throw new \InvalidArgumentException('user_id is required');
        }
        if (!isset($config['storage'])) {
            throw new \InvalidArgumentException('storage is required');
        }
        if (!isset($config['ai'])) {
            throw new \InvalidArgumentException('ai is required');
        }

        return new self(
            $config['user_id'],
            $config['system_context'] ?? 'default',
            $config['storage'],
            $config['ai'],
            $config['data_provider'] ?? null
        );
    }

    /**
     * Static factory for fluent API
     */
    public static function forUser(string $userId): NeuroHelperBuilder
    {
        return new NeuroHelperBuilder($userId);
    }

    /**
     * Main chat interface - process a user message
     * 
     * @param string $message The user's message
     * @param array $context Additional context (attachments, metadata, etc.)
     * @return NeuroResponse
     */
    public function chat(string $message, array $context = []): NeuroResponse
    {
        // 1. UNDERSTAND - Analyze the message
        $understanding = $this->understand($message, $context);

        // 2. LEARN - Update user profile with new knowledge
        $this->learn($understanding);

        // 3. RESPOND - Generate empowering response
        $response = $this->respond($understanding, $context);

        // 4. REMEMBER - Store exchange in memory
        $messageId = $this->memory->store($message, $response, $understanding);

        // 5. Update response with any goal suggestions
        if (!empty($understanding->detectedGoals) && !$response->goalSuggestion) {
            $goal = $understanding->detectedGoals[0];
            $response->suggestGoal(
                $goal['description'] ?? $goal,
                ['source' => 'detected_in_conversation']
            );
        }

        return $response;
    }

    /**
     * Understand a message
     */
    private function understand(string $message, array $context = []): NeuroUnderstanding
    {
        $conversationHistory = $this->memory->getRecentHistory(6);

        return $this->analyzer->analyze(
            $message,
            $this->profile,
            $conversationHistory,
            $context
        );
    }

    /**
     * Learn from the understanding
     */
    private function learn(NeuroUnderstanding $understanding): void
    {
        // Track detected goals
        foreach ($understanding->detectedGoals as $goalData) {
            $this->profile->addOrUpdateGoal($goalData);
        }

        // Record detected needs
        foreach ($understanding->detectedNeeds as $needData) {
            $this->profile->recordNeed($needData);
        }

        // Note detected preferences
        foreach ($understanding->detectedWants as $wantData) {
            $this->profile->recordWant($wantData);
        }

        // Update patterns
        $this->profile->updatePatterns($understanding);

        // Save profile
        $this->profile->save();
    }

    /**
     * Generate response
     */
    private function respond(NeuroUnderstanding $understanding, array $context = []): NeuroResponse
    {
        $conversationHistory = $this->memory->getRecentHistory(6);
        
        // Get system context if data provider available
        $systemContext = [];
        if ($this->dataProvider && !empty($understanding->topics)) {
            $systemContext = $this->dataProvider->getRelevantContext(
                $this->userId,
                $understanding->originalMessage,
                $understanding->entities
            );
        }

        return $this->empowerment->generateResponse(
            $understanding,
            $this->profile,
            $conversationHistory,
            $systemContext
        );
    }

    /**
     * Get the user's profile
     */
    public function getProfile(): UserProfile
    {
        return $this->profile;
    }

    /**
     * Get conversation history
     */
    public function getHistory(int $limit = 50): array
    {
        return $this->memory->getHistory($limit);
    }

    /**
     * Get recent history formatted for display
     */
    public function getRecentHistory(int $limit = 10): array
    {
        return $this->memory->getRecentHistory($limit);
    }

    /**
     * Get conversation summary
     */
    public function getConversationSummary(): string
    {
        return $this->memory->getConversationSummary();
    }

    /**
     * Start a new conversation session
     */
    public function startNewConversation(): string
    {
        return $this->memory->startNewConversation();
    }

    /**
     * Track a goal explicitly
     */
    public function trackGoal(string $description, array $context = []): Goal
    {
        $goal = new Goal([
            'description' => $description,
            'context' => $context,
            'system_origin' => $this->systemContext,
        ]);

        $this->profile->addOrUpdateGoal($goal);
        $this->profile->save();

        return $goal;
    }

    /**
     * Update goal progress
     */
    public function updateGoalProgress(string $goalId, float $progress): ?Goal
    {
        if (!isset($this->profile->goals[$goalId])) {
            return null;
        }

        $goal = $this->profile->goals[$goalId];
        $goal->progress = min(1.0, max(0.0, $progress));
        $goal->updatedAt = now();

        if ($goal->progress >= 1.0) {
            $goal->status = 'achieved';
        }

        $this->profile->save();

        return $goal;
    }

    /**
     * Get active goals
     */
    public function getActiveGoals(): array
    {
        return $this->profile->getActiveGoals();
    }

    /**
     * Get the path to help achieve a goal
     */
    public function getPathToGoal(string $goalId): ?array
    {
        $goal = $this->profile->goals[$goalId] ?? null;
        if (!$goal) {
            return null;
        }

        // Search history for context about this goal
        $relatedExchanges = $this->memory->getGoalContext($goalId);
        $historicalContext = $this->memory->searchHistory($goal->description, 5);

        return [
            'goal' => $goal->toArray(),
            'related_conversations' => count($relatedExchanges),
            'historical_context' => array_map(
                fn($e) => $e['user']['message'] ?? '',
                $historicalContext
            ),
            'suggested_next_steps' => $goal->milestones,
        ];
    }

    /**
     * Check if helper has something relevant to offer
     * (Call this to decide whether to show a suggestion proactively)
     */
    public function hasRelevantInsight(): bool
    {
        // Check for overdue goals
        foreach ($this->profile->getActiveGoals() as $goal) {
            if ($goal->isOverdue()) {
                return true;
            }
        }

        // Check for high-priority unaddressed needs
        $needs = $this->profile->getActiveNeeds();
        foreach ($needs as $need) {
            if ($need->priority >= 4 && $need->isRecurring()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get a proactive insight (if any)
     */
    public function getProactiveInsight(): ?array
    {
        // Check for overdue goals first
        foreach ($this->profile->getActiveGoals() as $goal) {
            if ($goal->isOverdue()) {
                return [
                    'type' => 'overdue_goal',
                    'message' => "Your goal \"{$goal->description}\" was due. Would you like to discuss it?",
                    'goal_id' => $goal->id,
                ];
            }
        }

        // Check for patterns that might be interesting
        if ($this->profile->interactionCount > 10) {
            $topTopics = array_slice(array_keys($this->profile->topicFrequency), 0, 1);
            if (!empty($topTopics)) {
                return [
                    'type' => 'pattern_observation',
                    'message' => "I've noticed you often discuss {$topTopics[0]}. Would you like to set a related goal?",
                    'topic' => $topTopics[0],
                ];
            }
        }

        return null;
    }

    /**
     * Get user ID
     */
    public function getUserId(): string
    {
        return $this->userId;
    }

    /**
     * Get system context
     */
    public function getSystemContext(): string
    {
        return $this->systemContext;
    }

    /**
     * Export user data (for portability/privacy)
     */
    public function exportUserData(): array
    {
        return [
            'user_id' => $this->userId,
            'profile' => $this->profile->toArray(),
            'conversation_history' => $this->memory->getHistory(100),
            'exported_at' => now()->toIso8601String(),
        ];
    }
}

/**
 * Builder class for fluent API
 */
class NeuroHelperBuilder
{
    private string $userId;
    private string $systemContext = 'default';
    private ?StorageInterface $storage = null;
    private ?AIProviderInterface $ai = null;
    private ?DataProviderInterface $dataProvider = null;

    public function __construct(string $userId)
    {
        $this->userId = $userId;
    }

    public function inSystem(string $systemContext): self
    {
        $this->systemContext = $systemContext;
        return $this;
    }

    public function withStorage(StorageInterface $storage): self
    {
        $this->storage = $storage;
        return $this;
    }

    public function withAI(AIProviderInterface $ai): self
    {
        $this->ai = $ai;
        return $this;
    }

    public function withDataProvider(DataProviderInterface $dataProvider): self
    {
        $this->dataProvider = $dataProvider;
        return $this;
    }

    public function build(): NeuroHelper
    {
        if (!$this->storage) {
            throw new \InvalidArgumentException('Storage is required. Call withStorage()');
        }
        if (!$this->ai) {
            throw new \InvalidArgumentException('AI provider is required. Call withAI()');
        }

        return NeuroHelper::create([
            'user_id' => $this->userId,
            'system_context' => $this->systemContext,
            'storage' => $this->storage,
            'ai' => $this->ai,
            'data_provider' => $this->dataProvider,
        ]);
    }
}


