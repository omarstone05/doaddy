<?php

namespace App\NeuroCore\Context;

use App\NeuroCore\Contracts\StorageInterface;
use App\NeuroCore\Contracts\DataProviderInterface;
use App\NeuroCore\Data\Goal;
use App\NeuroCore\Data\Need;
use App\NeuroCore\Data\Want;
use App\NeuroCore\Data\NeuroUnderstanding;
use Carbon\Carbon;

/**
 * UserProfile - The accumulated understanding of a user across systems
 * This is the core of Neuro's memory about each user
 */
class UserProfile
{
    private string $userId;
    private StorageInterface $storage;

    // Core profile data
    public array $goals = [];
    public array $needs = [];
    public array $wants = [];
    public array $patterns = [];

    // Metadata
    public ?string $displayName = null;
    public array $systemContexts = []; // Data from different systems
    public Carbon $createdAt;
    public Carbon $updatedAt;
    public int $interactionCount = 0;
    public ?Carbon $lastInteractionAt = null;

    // Learning metadata
    public array $topicFrequency = []; // What topics come up most
    public array $timePatterns = []; // When they're most active
    public array $communicationStyle = []; // How they communicate

    private function __construct(string $userId, StorageInterface $storage)
    {
        $this->userId = $userId;
        $this->storage = $storage;
        $this->createdAt = now();
        $this->updatedAt = now();
    }

    /**
     * Load or create a user profile
     */
    public static function loadOrCreate(string $userId, StorageInterface $storage): self
    {
        $profile = new self($userId, $storage);
        
        $stored = $storage->get("neuro:profile:{$userId}");
        if ($stored) {
            $profile->hydrate($stored);
        }

        return $profile;
    }

    /**
     * Hydrate profile from stored data
     */
    private function hydrate(array $data): void
    {
        // Hydrate goals
        foreach ($data['goals'] ?? [] as $goalData) {
            $this->goals[$goalData['id']] = Goal::fromArray($goalData);
        }

        // Hydrate needs
        foreach ($data['needs'] ?? [] as $needData) {
            $this->needs[$needData['id']] = Need::fromArray($needData);
        }

        // Hydrate wants
        foreach ($data['wants'] ?? [] as $wantData) {
            $this->wants[$wantData['id']] = Want::fromArray($wantData);
        }

        // Hydrate other fields
        $this->displayName = $data['display_name'] ?? null;
        $this->patterns = $data['patterns'] ?? [];
        $this->systemContexts = $data['system_contexts'] ?? [];
        $this->createdAt = isset($data['created_at']) ? Carbon::parse($data['created_at']) : now();
        $this->updatedAt = isset($data['updated_at']) ? Carbon::parse($data['updated_at']) : now();
        $this->interactionCount = $data['interaction_count'] ?? 0;
        $this->lastInteractionAt = isset($data['last_interaction_at']) 
            ? Carbon::parse($data['last_interaction_at']) 
            : null;
        $this->topicFrequency = $data['topic_frequency'] ?? [];
        $this->timePatterns = $data['time_patterns'] ?? [];
        $this->communicationStyle = $data['communication_style'] ?? [];
    }

    /**
     * Save profile to storage
     */
    public function save(): void
    {
        $this->updatedAt = now();
        $this->storage->set("neuro:profile:{$this->userId}", $this->toArray());
    }

    /**
     * Enrich profile with data from a system
     */
    public function enrichFromSystem(DataProviderInterface $provider): void
    {
        $systemId = $provider->getSystemId();
        $summary = $provider->getUserDataSummary($this->userId);
        
        $this->systemContexts[$systemId] = [
            'last_sync' => now()->toIso8601String(),
            'summary' => $summary,
        ];

        $this->save();
    }

    /**
     * Add or update a goal
     */
    public function addOrUpdateGoal(array|Goal $goalData): Goal
    {
        if ($goalData instanceof Goal) {
            $goal = $goalData;
        } else {
            // Check if we have an existing similar goal
            $existing = $this->findSimilarGoal($goalData['description'] ?? '');
            if ($existing) {
                // Update existing goal
                $existing->addContext('updated_via', 'conversation');
                $existing->updatedAt = now();
                $goal = $existing;
            } else {
                // Create new goal
                $goal = new Goal($goalData);
            }
        }

        $this->goals[$goal->id] = $goal;
        return $goal;
    }

    /**
     * Find a similar existing goal
     */
    private function findSimilarGoal(string $description): ?Goal
    {
        $description = strtolower($description);
        foreach ($this->goals as $goal) {
            // Simple similarity check - could be enhanced with AI
            if (similar_text(strtolower($goal->description), $description) > strlen($description) * 0.7) {
                return $goal;
            }
        }
        return null;
    }

    /**
     * Record a need
     */
    public function recordNeed(array|Need $needData): Need
    {
        if ($needData instanceof Need) {
            $need = $needData;
        } else {
            // Check if similar need exists
            $existing = $this->findSimilarNeed($needData['description'] ?? '');
            if ($existing) {
                $existing->recordMention();
                $need = $existing;
            } else {
                $need = new Need($needData);
            }
        }

        $this->needs[$need->id] = $need;
        return $need;
    }

    /**
     * Find a similar existing need
     */
    private function findSimilarNeed(string $description): ?Need
    {
        $description = strtolower($description);
        foreach ($this->needs as $need) {
            if (similar_text(strtolower($need->description), $description) > strlen($description) * 0.6) {
                return $need;
            }
        }
        return null;
    }

    /**
     * Record a want/preference
     */
    public function recordWant(array|Want $wantData): Want
    {
        if ($wantData instanceof Want) {
            $want = $wantData;
        } else {
            // Check if similar want exists
            $existing = $this->findSimilarWant($wantData['description'] ?? '');
            if ($existing) {
                $existing->recordObservation();
                $want = $existing;
            } else {
                $want = new Want($wantData);
            }
        }

        $this->wants[$want->id] = $want;
        return $want;
    }

    /**
     * Find a similar existing want
     */
    private function findSimilarWant(string $description): ?Want
    {
        $description = strtolower($description);
        foreach ($this->wants as $want) {
            if (similar_text(strtolower($want->description), $description) > strlen($description) * 0.6) {
                return $want;
            }
        }
        return null;
    }

    /**
     * Update patterns from an interaction
     */
    public function updatePatterns(NeuroUnderstanding $understanding): void
    {
        // Update topic frequency
        foreach ($understanding->topics as $topic) {
            $topic = strtolower($topic);
            $this->topicFrequency[$topic] = ($this->topicFrequency[$topic] ?? 0) + 1;
        }

        // Update time patterns
        $hour = now()->hour;
        $day = strtolower(now()->format('l'));
        $this->timePatterns['hours'][$hour] = ($this->timePatterns['hours'][$hour] ?? 0) + 1;
        $this->timePatterns['days'][$day] = ($this->timePatterns['days'][$day] ?? 0) + 1;

        // Update communication style observations
        if ($understanding->intent === 'ask_for_help' && !empty($understanding->questions)) {
            $this->communicationStyle['asks_questions'] = ($this->communicationStyle['asks_questions'] ?? 0) + 1;
        }
        if (strlen($understanding->originalMessage) > 200) {
            $this->communicationStyle['detailed_messages'] = ($this->communicationStyle['detailed_messages'] ?? 0) + 1;
        }
        if (strlen($understanding->originalMessage) < 50) {
            $this->communicationStyle['brief_messages'] = ($this->communicationStyle['brief_messages'] ?? 0) + 1;
        }

        // Update interaction count
        $this->interactionCount++;
        $this->lastInteractionAt = now();
    }

    /**
     * Get active goals
     */
    public function getActiveGoals(): array
    {
        return array_filter($this->goals, fn($g) => $g->status === 'active');
    }

    /**
     * Get active needs sorted by priority
     */
    public function getActiveNeeds(): array
    {
        $active = array_filter($this->needs, fn($n) => $n->status === 'active');
        usort($active, fn($a, $b) => $b->priority - $a->priority);
        return $active;
    }

    /**
     * Get established preferences
     */
    public function getEstablishedWants(): array
    {
        return array_filter($this->wants, fn($w) => $w->isEstablished());
    }

    /**
     * Get a summary of goals for AI context
     */
    public function getGoalsSummary(): string
    {
        $active = $this->getActiveGoals();
        if (empty($active)) {
            return "No active goals tracked yet.";
        }

        $summaries = array_map(fn($g) => "- " . $g->getSummary(), $active);
        return implode("\n", array_slice($summaries, 0, 5));
    }

    /**
     * Get a summary of needs for AI context
     */
    public function getNeedsSummary(): string
    {
        $active = $this->getActiveNeeds();
        if (empty($active)) {
            return "No specific needs recorded yet.";
        }

        $summaries = array_map(fn($n) => "- " . $n->getSummary(), $active);
        return implode("\n", array_slice($summaries, 0, 5));
    }

    /**
     * Get a summary of preferences for AI context
     */
    public function getWantsSummary(): string
    {
        $established = $this->getEstablishedWants();
        if (empty($established)) {
            return "No preferences observed yet.";
        }

        $summaries = array_map(fn($w) => "- " . $w->getSummary(), $established);
        return implode("\n", array_slice($summaries, 0, 5));
    }

    /**
     * Get a summary of patterns for AI context
     */
    public function getPatternsSummary(): string
    {
        $parts = [];

        // Top topics
        if (!empty($this->topicFrequency)) {
            arsort($this->topicFrequency);
            $topTopics = array_slice(array_keys($this->topicFrequency), 0, 3);
            $parts[] = "Common topics: " . implode(', ', $topTopics);
        }

        // Communication style
        if (!empty($this->communicationStyle)) {
            $brief = $this->communicationStyle['brief_messages'] ?? 0;
            $detailed = $this->communicationStyle['detailed_messages'] ?? 0;
            if ($brief > $detailed * 2) {
                $parts[] = "Tends to send brief messages";
            } elseif ($detailed > $brief * 2) {
                $parts[] = "Tends to send detailed messages";
            }
        }

        // Active times
        if (!empty($this->timePatterns['hours'] ?? [])) {
            arsort($this->timePatterns['hours']);
            $peakHours = array_slice(array_keys($this->timePatterns['hours']), 0, 3);
            if (!empty($peakHours)) {
                $parts[] = "Most active around: " . implode(', ', array_map(fn($h) => "{$h}:00", $peakHours));
            }
        }

        return empty($parts) ? "Still learning user patterns." : implode(". ", $parts);
    }

    /**
     * Get related goal IDs for a topic
     */
    public function findGoalsRelatedTo(string $topic): array
    {
        $topic = strtolower($topic);
        $related = [];

        foreach ($this->goals as $goal) {
            if (stripos($goal->description, $topic) !== false) {
                $related[] = $goal->id;
            }
            foreach ($goal->context as $key => $value) {
                if (is_string($value) && stripos($value, $topic) !== false) {
                    $related[] = $goal->id;
                    break;
                }
            }
        }

        return array_unique($related);
    }

    /**
     * Get user ID
     */
    public function getUserId(): string
    {
        return $this->userId;
    }

    /**
     * Convert to array for storage
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'display_name' => $this->displayName,
            'goals' => array_map(fn($g) => $g->toArray(), $this->goals),
            'needs' => array_map(fn($n) => $n->toArray(), $this->needs),
            'wants' => array_map(fn($w) => $w->toArray(), $this->wants),
            'patterns' => $this->patterns,
            'system_contexts' => $this->systemContexts,
            'created_at' => $this->createdAt->toIso8601String(),
            'updated_at' => $this->updatedAt->toIso8601String(),
            'interaction_count' => $this->interactionCount,
            'last_interaction_at' => $this->lastInteractionAt?->toIso8601String(),
            'topic_frequency' => $this->topicFrequency,
            'time_patterns' => $this->timePatterns,
            'communication_style' => $this->communicationStyle,
        ];
    }
}


