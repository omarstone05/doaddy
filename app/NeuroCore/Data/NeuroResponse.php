<?php

namespace App\NeuroCore\Data;

/**
 * Represents Neuro's response to a user
 * Designed for empowerment, not imposition
 */
class NeuroResponse
{
    // The conversational response message
    public string $message;

    // Type of assistance provided
    public string $assistanceType; // 'guidance', 'clarification', 'acknowledgment', 'direction', 'reflection', 'celebration'

    // Suggested next steps (for USER to take, not Neuro)
    public array $suggestedSteps;

    // Questions to help user think through the problem
    public array $guidingQuestions;

    // Resources that might help (links, references, tools)
    public array $resources;

    // Was context updated from this interaction?
    public ?array $contextUpdate;

    // Is there a goal we can suggest tracking?
    public ?array $goalSuggestion;

    // Quick action buttons (for UI)
    public array $quickActions;

    // Confidence in this response being helpful (0-1)
    public float $confidence;

    // Internal notes about why this response was chosen
    public ?string $responseRationale;

    // Follow-up questions Neuro might ask later
    public array $potentialFollowUps;

    // Sentiment of response (to match or uplift user)
    public string $responseTone;

    public function __construct(array $data = [])
    {
        $this->message = $data['message'] ?? '';
        $this->assistanceType = $data['assistance_type'] ?? 'acknowledgment';
        $this->suggestedSteps = $data['suggested_steps'] ?? [];
        $this->guidingQuestions = $data['guiding_questions'] ?? [];
        $this->resources = $data['resources'] ?? [];
        $this->contextUpdate = $data['context_update'] ?? null;
        $this->goalSuggestion = $data['goal_suggestion'] ?? null;
        $this->quickActions = $data['quick_actions'] ?? [];
        $this->confidence = $data['confidence'] ?? 0.7;
        $this->responseRationale = $data['response_rationale'] ?? null;
        $this->potentialFollowUps = $data['potential_follow_ups'] ?? [];
        $this->responseTone = $data['response_tone'] ?? 'supportive';
    }

    /**
     * Add a suggested step for the user
     */
    public function addStep(string $step, ?string $why = null): self
    {
        $this->suggestedSteps[] = [
            'step' => $step,
            'rationale' => $why,
        ];
        return $this;
    }

    /**
     * Add a guiding question
     */
    public function addQuestion(string $question, ?string $purpose = null): self
    {
        $this->guidingQuestions[] = [
            'question' => $question,
            'purpose' => $purpose,
        ];
        return $this;
    }

    /**
     * Add a resource
     */
    public function addResource(string $title, string $url, ?string $type = null): self
    {
        $this->resources[] = [
            'title' => $title,
            'url' => $url,
            'type' => $type ?? 'link',
        ];
        return $this;
    }

    /**
     * Add a quick action
     */
    public function addQuickAction(string $label, ?string $command = null, ?string $url = null): self
    {
        $action = ['label' => $label];
        if ($command) $action['command'] = $command;
        if ($url) $action['url'] = $url;
        $this->quickActions[] = $action;
        return $this;
    }

    /**
     * Suggest tracking a goal
     */
    public function suggestGoal(string $goalDescription, array $context = []): self
    {
        $this->goalSuggestion = [
            'description' => $goalDescription,
            'context' => $context,
            'suggested_at' => now()->toIso8601String(),
        ];
        return $this;
    }

    /**
     * Record what context was updated
     */
    public function recordContextUpdate(string $type, array $details): self
    {
        $this->contextUpdate = [
            'type' => $type, // 'goal_added', 'need_recorded', 'want_learned', 'pattern_detected'
            'details' => $details,
            'timestamp' => now()->toIso8601String(),
        ];
        return $this;
    }

    /**
     * Check if response has guidance
     */
    public function hasGuidance(): bool
    {
        return !empty($this->suggestedSteps) || !empty($this->guidingQuestions);
    }

    /**
     * Check if response is primarily an acknowledgment
     */
    public function isAcknowledgment(): bool
    {
        return $this->assistanceType === 'acknowledgment' && !$this->hasGuidance();
    }

    /**
     * Get the full response formatted for chat
     */
    public function getFormattedResponse(): string
    {
        $parts = [$this->message];

        if (!empty($this->guidingQuestions)) {
            $parts[] = "\n**Questions to consider:**";
            foreach ($this->guidingQuestions as $q) {
                $question = is_array($q) ? $q['question'] : $q;
                $parts[] = "- {$question}";
            }
        }

        if (!empty($this->suggestedSteps)) {
            $parts[] = "\n**Possible next steps:**";
            foreach ($this->suggestedSteps as $i => $step) {
                $stepText = is_array($step) ? $step['step'] : $step;
                $num = $i + 1;
                $parts[] = "{$num}. {$stepText}";
            }
        }

        return implode("\n", $parts);
    }

    /**
     * Convert to array for API response
     */
    public function toArray(): array
    {
        return [
            'message' => $this->message,
            'content' => $this->message, // Alias for compatibility
            'assistance_type' => $this->assistanceType,
            'suggested_steps' => $this->suggestedSteps,
            'guiding_questions' => $this->guidingQuestions,
            'resources' => $this->resources,
            'context_update' => $this->contextUpdate,
            'goal_suggestion' => $this->goalSuggestion,
            'quick_actions' => $this->quickActions,
            'confidence' => $this->confidence,
            'response_tone' => $this->responseTone,
        ];
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    /**
     * Create a simple acknowledgment response
     */
    public static function acknowledge(string $message): self
    {
        return new self([
            'message' => $message,
            'assistance_type' => 'acknowledgment',
            'response_tone' => 'supportive',
        ]);
    }

    /**
     * Create a guidance response
     */
    public static function guide(string $message, array $questions = [], array $steps = []): self
    {
        return new self([
            'message' => $message,
            'assistance_type' => 'guidance',
            'guiding_questions' => array_map(fn($q) => ['question' => $q], $questions),
            'suggested_steps' => array_map(fn($s) => ['step' => $s], $steps),
            'response_tone' => 'supportive',
        ]);
    }

    /**
     * Create a celebration response
     */
    public static function celebrate(string $message): self
    {
        return new self([
            'message' => $message,
            'assistance_type' => 'celebration',
            'response_tone' => 'enthusiastic',
        ]);
    }
}


