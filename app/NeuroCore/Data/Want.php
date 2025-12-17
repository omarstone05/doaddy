<?php

namespace App\NeuroCore\Data;

use Carbon\Carbon;

/**
 * Represents a user preference/want that Neuro tracks
 * Wants are preferences about how they like to work/communicate
 */
class Want
{
    public string $id;
    public string $description;
    public string $category; // 'communication', 'workflow', 'ui', 'timing', 'style'
    public float $strength; // 0.0 to 1.0 - how strongly they prefer this
    public array $manifestations; // How this preference shows up in behavior
    public array $context;
    public ?string $systemOrigin;
    public int $observationCount; // How many times we've observed this preference
    public Carbon $firstObserved;
    public Carbon $lastObserved;
    public bool $explicit; // Did user explicitly state this, or did we infer it?

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? $this->generateId();
        $this->description = $data['description'] ?? '';
        $this->category = $data['category'] ?? 'general';
        $this->strength = $data['strength'] ?? 0.5;
        $this->manifestations = $data['manifestations'] ?? [];
        $this->context = $data['context'] ?? [];
        $this->systemOrigin = $data['system_origin'] ?? null;
        $this->observationCount = $data['observation_count'] ?? 1;
        $this->firstObserved = isset($data['first_observed']) 
            ? Carbon::parse($data['first_observed']) 
            : now();
        $this->lastObserved = isset($data['last_observed']) 
            ? Carbon::parse($data['last_observed']) 
            : now();
        $this->explicit = $data['explicit'] ?? false;
    }

    private function generateId(): string
    {
        return 'want_' . bin2hex(random_bytes(8));
    }

    /**
     * Record an observation of this preference
     */
    public function recordObservation(string $manifestation = null): void
    {
        $this->observationCount++;
        $this->lastObserved = now();
        
        // Strengthen preference with observations
        if ($this->strength < 0.95) {
            $this->strength = min(0.95, $this->strength + 0.05);
        }

        if ($manifestation && !in_array($manifestation, $this->manifestations)) {
            $this->manifestations[] = $manifestation;
        }
    }

    /**
     * Mark as explicitly stated by user
     */
    public function markExplicit(): void
    {
        $this->explicit = true;
        $this->strength = max($this->strength, 0.8); // Explicit preferences are strong
    }

    /**
     * Add context
     */
    public function addContext(string $key, mixed $value): void
    {
        $this->context[$key] = $value;
    }

    /**
     * Check if this is a strong preference
     */
    public function isStrong(): bool
    {
        return $this->strength >= 0.7 || $this->explicit;
    }

    /**
     * Check if this is a well-established preference (observed multiple times)
     */
    public function isEstablished(): bool
    {
        return $this->observationCount >= 3 || $this->explicit;
    }

    /**
     * Convert to array for storage
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'category' => $this->category,
            'strength' => $this->strength,
            'manifestations' => $this->manifestations,
            'context' => $this->context,
            'system_origin' => $this->systemOrigin,
            'observation_count' => $this->observationCount,
            'first_observed' => $this->firstObserved->toIso8601String(),
            'last_observed' => $this->lastObserved->toIso8601String(),
            'explicit' => $this->explicit,
        ];
    }

    /**
     * Create from stored array
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    /**
     * Get category label
     */
    public static function getCategoryLabel(string $category): string
    {
        return match($category) {
            'communication' => 'Communication Style',
            'workflow' => 'Workflow Preferences',
            'ui' => 'Interface Preferences',
            'timing' => 'Timing & Scheduling',
            'style' => 'Personal Style',
            default => 'General',
        };
    }

    /**
     * Common preference templates
     */
    public static function commonPreferences(): array
    {
        return [
            'concise_responses' => [
                'description' => 'Prefers concise, to-the-point responses',
                'category' => 'communication',
            ],
            'detailed_explanations' => [
                'description' => 'Likes detailed explanations and context',
                'category' => 'communication',
            ],
            'questions_over_answers' => [
                'description' => 'Prefers being asked questions to think through vs given answers',
                'category' => 'workflow',
            ],
            'morning_person' => [
                'description' => 'Most active and productive in mornings',
                'category' => 'timing',
            ],
            'visual_thinker' => [
                'description' => 'Prefers visual representations and examples',
                'category' => 'style',
            ],
            'step_by_step' => [
                'description' => 'Likes breaking things into small steps',
                'category' => 'workflow',
            ],
        ];
    }

    /**
     * Get a human-readable summary
     */
    public function getSummary(): string
    {
        $strengthLabel = match(true) {
            $this->strength >= 0.8 => 'Strong',
            $this->strength >= 0.5 => 'Moderate',
            default => 'Mild',
        };

        $prefix = $this->explicit ? 'Stated' : 'Observed';
        
        return "[{$prefix} - {$strengthLabel}] {$this->description}";
    }
}


