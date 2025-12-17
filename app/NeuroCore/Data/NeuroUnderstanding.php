<?php

namespace App\NeuroCore\Data;

/**
 * Represents Neuro's understanding of a user message
 * This is the output of the analysis phase
 */
class NeuroUnderstanding
{
    // The original message
    public string $originalMessage;

    // What user is explicitly asking/saying
    public string $intent; // 'share_information', 'ask_for_help', 'request_action', 'clarify', 'reflect', 'greeting', 'other'

    // Sub-intent for more specificity
    public ?string $subIntent;

    // Confidence in intent classification (0-1)
    public float $intentConfidence;

    // Goals detected in this message
    public array $detectedGoals; // Array of Goal objects or goal descriptions

    // Needs expressed or implied
    public array $detectedNeeds; // Array of Need objects or need descriptions

    // Wants/preferences expressed
    public array $detectedWants; // Array of Want objects or want descriptions

    // Entities mentioned (people, money, dates, places, etc.)
    public array $entities;

    // Emotional tone/sentiment
    public string $sentiment; // 'positive', 'negative', 'neutral', 'frustrated', 'excited', 'confused', 'urgent'

    // Urgency level (0-1)
    public float $urgency;

    // Related to which of their existing goals?
    public array $relatedGoalIds;

    // Related to which of their existing needs?
    public array $relatedNeedIds;

    // Topics/themes in the message
    public array $topics;

    // Questions user is asking (explicit or implicit)
    public array $questions;

    // Is user looking for guidance or just sharing/venting?
    public bool $seekingGuidance;

    // Any specific request for action
    public ?string $actionRequest;

    // Context hints that might be useful for response
    public array $contextHints;

    // Raw AI analysis output (for debugging)
    public ?array $rawAnalysis;

    public function __construct(array $data = [])
    {
        $this->originalMessage = $data['original_message'] ?? '';
        $this->intent = $data['intent'] ?? 'other';
        $this->subIntent = $data['sub_intent'] ?? null;
        $this->intentConfidence = $data['intent_confidence'] ?? 0.5;
        $this->detectedGoals = $data['detected_goals'] ?? [];
        $this->detectedNeeds = $data['detected_needs'] ?? [];
        $this->detectedWants = $data['detected_wants'] ?? [];
        $this->entities = $data['entities'] ?? [];
        $this->sentiment = $data['sentiment'] ?? 'neutral';
        $this->urgency = $data['urgency'] ?? 0.3;
        $this->relatedGoalIds = $data['related_goal_ids'] ?? [];
        $this->relatedNeedIds = $data['related_need_ids'] ?? [];
        $this->topics = $data['topics'] ?? [];
        $this->questions = $data['questions'] ?? [];
        $this->seekingGuidance = $data['seeking_guidance'] ?? false;
        $this->actionRequest = $data['action_request'] ?? null;
        $this->contextHints = $data['context_hints'] ?? [];
        $this->rawAnalysis = $data['raw_analysis'] ?? null;
    }

    /**
     * Check if user is sharing information (vs asking)
     */
    public function isSharing(): bool
    {
        return $this->intent === 'share_information' || $this->intent === 'reflect';
    }

    /**
     * Check if user is asking for help
     */
    public function isAskingForHelp(): bool
    {
        return $this->intent === 'ask_for_help' || $this->seekingGuidance;
    }

    /**
     * Check if this is a greeting/casual message
     */
    public function isGreeting(): bool
    {
        return $this->intent === 'greeting';
    }

    /**
     * Check if urgent
     */
    public function isUrgent(): bool
    {
        return $this->urgency >= 0.7 || $this->sentiment === 'urgent';
    }

    /**
     * Check if user seems frustrated
     */
    public function isFrustrated(): bool
    {
        return $this->sentiment === 'frustrated' || $this->sentiment === 'negative';
    }

    /**
     * Get the most relevant goal mentioned
     */
    public function getPrimaryGoal(): ?array
    {
        return $this->detectedGoals[0] ?? null;
    }

    /**
     * Get all extracted entities of a specific type
     */
    public function getEntitiesOfType(string $type): array
    {
        return array_filter($this->entities, fn($e) => ($e['type'] ?? null) === $type);
    }

    /**
     * Check if a specific topic was mentioned
     */
    public function hasTopic(string $topic): bool
    {
        return in_array(strtolower($topic), array_map('strtolower', $this->topics));
    }

    /**
     * Get summary of what was understood
     */
    public function getSummary(): string
    {
        $parts = [];
        
        $parts[] = "Intent: {$this->intent}";
        
        if (!empty($this->detectedGoals)) {
            $parts[] = "Goals: " . count($this->detectedGoals) . " detected";
        }
        
        if (!empty($this->detectedNeeds)) {
            $parts[] = "Needs: " . count($this->detectedNeeds) . " detected";
        }
        
        if (!empty($this->questions)) {
            $parts[] = "Questions: " . count($this->questions);
        }
        
        $parts[] = "Sentiment: {$this->sentiment}";
        
        if ($this->urgency > 0.5) {
            $parts[] = "Urgency: " . round($this->urgency * 100) . "%";
        }

        return implode(' | ', $parts);
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'original_message' => $this->originalMessage,
            'intent' => $this->intent,
            'sub_intent' => $this->subIntent,
            'intent_confidence' => $this->intentConfidence,
            'detected_goals' => $this->detectedGoals,
            'detected_needs' => $this->detectedNeeds,
            'detected_wants' => $this->detectedWants,
            'entities' => $this->entities,
            'sentiment' => $this->sentiment,
            'urgency' => $this->urgency,
            'related_goal_ids' => $this->relatedGoalIds,
            'related_need_ids' => $this->relatedNeedIds,
            'topics' => $this->topics,
            'questions' => $this->questions,
            'seeking_guidance' => $this->seekingGuidance,
            'action_request' => $this->actionRequest,
            'context_hints' => $this->contextHints,
        ];
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }
}


