<?php

namespace App\NeuroCore\Response;

use App\NeuroCore\Contracts\AIProviderInterface;
use App\NeuroCore\Context\UserProfile;
use App\NeuroCore\Data\NeuroUnderstanding;
use App\NeuroCore\Data\NeuroResponse;

/**
 * EmpowermentEngine - Generates responses that guide rather than do
 * This is the heart of the "silent helper" philosophy
 */
class EmpowermentEngine
{
    private AIProviderInterface $ai;

    public function __construct(AIProviderInterface $ai)
    {
        $this->ai = $ai;
    }

    /**
     * Generate an empowering response
     */
    public function generateResponse(
        NeuroUnderstanding $understanding,
        UserProfile $profile,
        array $conversationHistory = [],
        array $systemContext = []
    ): NeuroResponse {
        // Handle simple cases without AI
        if ($understanding->isGreeting()) {
            return $this->handleGreeting($profile);
        }

        // Build the empowerment prompt
        $systemPrompt = $this->buildEmpowermentPrompt($profile, $understanding);

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        // Add conversation history
        foreach (array_slice($conversationHistory, -6) as $msg) {
            $messages[] = $msg;
        }

        // Add current message with understanding context
        $messages[] = [
            'role' => 'user',
            'content' => $this->buildUserContextMessage($understanding),
        ];

        try {
            $aiResponse = $this->ai->chat($messages, 1200, 0.7);
            return $this->parseResponse($aiResponse['content'], $understanding);
        } catch (\Exception $e) {
            \Log::error('Neuro response generation failed', ['error' => $e->getMessage()]);
            return $this->fallbackResponse($understanding);
        }
    }

    /**
     * Build the empowerment system prompt
     */
    private function buildEmpowermentPrompt(UserProfile $profile, NeuroUnderstanding $understanding): string
    {
        $goalsContext = $profile->getGoalsSummary();
        $needsContext = $profile->getNeedsSummary();
        $wantsContext = $profile->getWantsSummary();
        $patternsContext = $profile->getPatternsSummary();

        return <<<PROMPT
You are Neuro, a thoughtful guide who helps users achieve their goals WITHOUT doing things for them.

## YOUR CORE PHILOSOPHY

1. **GUIDE, DON'T DO**
   - Help users THINK through problems
   - Ask questions that lead to insight
   - Suggest steps THEY can take (not actions YOU will take)
   - Never say "I'll do X for you" - say "You could do X" or "Consider X"

2. **EMPOWER, DON'T IMPOSE**
   - Present options, don't prescribe solutions
   - Respect their autonomy to make decisions
   - Your job is to expand their thinking, not narrow it

3. **UNDERSTAND DEEPLY**
   - Listen for what they REALLY need (not just what they say)
   - Connect new information to their existing context/goals
   - Remember what matters to them

4. **STAY HUMBLE**
   - You're a thinking partner, not an expert
   - Ask clarifying questions when unsure
   - Acknowledge when something is outside your knowledge

## USER'S CONTEXT

**Active Goals:**
{$goalsContext}

**Known Needs:**
{$needsContext}

**Preferences:**
{$wantsContext}

**Patterns:**
{$patternsContext}

## CURRENT MESSAGE ANALYSIS

Intent: {$understanding->intent}
Sentiment: {$understanding->sentiment}
Urgency: {$understanding->urgency}
Seeking Guidance: {$understanding->seekingGuidance}
Topics: [topics_placeholder]

## RESPONSE GUIDELINES

**When user SHARES INFORMATION:**
- Acknowledge what they shared genuinely
- Note if it relates to their goals
- Ask a clarifying or deepening question if appropriate
- DON'T immediately jump to advice

**When user ASKS FOR HELP:**
- Ask questions to help THEM think it through
- Offer frameworks or approaches THEY can use
- Suggest resources if relevant
- Let THEM arrive at conclusions

**When user EXPRESSES FRUSTRATION:**
- Acknowledge the feeling first
- Ask what specifically is frustrating
- Help them break down the problem
- DON'T dismiss or immediately problem-solve

**When user SHARES A GOAL:**
- Acknowledge and validate the goal
- Ask about their motivation (why this matters)
- Help them think about first steps
- Offer to remember and track progress

## OUTPUT FORMAT

Respond naturally in conversation. After your response, on a NEW LINE, add:
---METADATA---
{
    "assistance_type": "guidance|clarification|acknowledgment|direction|reflection|celebration",
    "suggested_steps": ["step 1", "step 2"],
    "guiding_questions": ["question 1", "question 2"],
    "goal_suggestion": {"description": "if a new goal was detected", "context": {}},
    "context_learned": {"type": "goal|need|want|pattern", "description": "what we learned"},
    "response_tone": "supportive|curious|celebratory|empathetic|encouraging"
}
PROMPT;
    }

    /**
     * Build user context message for AI
     */
    private function buildUserContextMessage(NeuroUnderstanding $understanding): string
    {
        $msg = $understanding->originalMessage;
        
        $context = [];
        if (!empty($understanding->detectedGoals)) {
            $context[] = "Detected potential goal: " . ($understanding->detectedGoals[0]['description'] ?? 'unknown');
        }
        if (!empty($understanding->questions)) {
            $context[] = "User seems to be asking: " . implode(', ', $understanding->questions);
        }
        if ($understanding->isFrustrated()) {
            $context[] = "User seems frustrated";
        }
        if ($understanding->isUrgent()) {
            $context[] = "This seems urgent";
        }

        if (!empty($context)) {
            return "{$msg}\n\n[Context: " . implode(". ", $context) . "]";
        }

        return $msg;
    }

    /**
     * Parse AI response into NeuroResponse
     */
    private function parseResponse(string $content, NeuroUnderstanding $understanding): NeuroResponse
    {
        $message = $content;
        $metadata = [];

        // Extract metadata if present
        if (str_contains($content, '---METADATA---')) {
            $parts = explode('---METADATA---', $content, 2);
            $message = trim($parts[0]);
            
            $metadataJson = trim($parts[1] ?? '');
            if (preg_match('/\{[\s\S]*\}/', $metadataJson, $matches)) {
                $parsed = json_decode($matches[0], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $metadata = $parsed;
                }
            }
        }

        $response = new NeuroResponse([
            'message' => $message,
            'assistance_type' => $metadata['assistance_type'] ?? $this->inferAssistanceType($understanding),
            'suggested_steps' => array_map(
                fn($s) => ['step' => $s],
                $metadata['suggested_steps'] ?? []
            ),
            'guiding_questions' => array_map(
                fn($q) => ['question' => $q],
                $metadata['guiding_questions'] ?? []
            ),
            'response_tone' => $metadata['response_tone'] ?? 'supportive',
            'confidence' => 0.8,
        ]);

        // Handle goal suggestion
        if (!empty($metadata['goal_suggestion']['description'])) {
            $response->suggestGoal(
                $metadata['goal_suggestion']['description'],
                $metadata['goal_suggestion']['context'] ?? []
            );
        }

        // Handle context learned
        if (!empty($metadata['context_learned'])) {
            $response->recordContextUpdate(
                $metadata['context_learned']['type'] ?? 'observation',
                ['description' => $metadata['context_learned']['description'] ?? '']
            );
        }

        return $response;
    }

    /**
     * Infer assistance type from understanding
     */
    private function inferAssistanceType(NeuroUnderstanding $understanding): string
    {
        if ($understanding->isAskingForHelp()) {
            return 'guidance';
        }
        if ($understanding->isSharing()) {
            return 'acknowledgment';
        }
        if (!empty($understanding->questions)) {
            return 'clarification';
        }
        return 'acknowledgment';
    }

    /**
     * Handle greeting messages
     */
    private function handleGreeting(UserProfile $profile): NeuroResponse
    {
        $hour = now()->hour;
        $timeGreeting = match(true) {
            $hour < 12 => 'Good morning',
            $hour < 17 => 'Good afternoon',
            default => 'Good evening',
        };

        $message = "{$timeGreeting}! How can I help you think through something today?";

        // Add context if we know things about them
        $activeGoals = $profile->getActiveGoals();
        if (!empty($activeGoals)) {
            $topGoal = reset($activeGoals);
            $message .= "\n\nI remember you're working on: \"{$topGoal->description}\". Would you like to discuss progress on that, or something else?";
        }

        return new NeuroResponse([
            'message' => $message,
            'assistance_type' => 'acknowledgment',
            'response_tone' => 'supportive',
            'quick_actions' => [
                ['label' => 'Discuss a goal', 'command' => 'I want to talk about my goals'],
                ['label' => 'Think through a problem', 'command' => 'Help me think through something'],
                ['label' => 'Just chat', 'command' => 'Let\'s just chat'],
            ],
        ]);
    }

    /**
     * Fallback response when AI fails
     */
    private function fallbackResponse(NeuroUnderstanding $understanding): NeuroResponse
    {
        $message = "I heard you. ";

        if ($understanding->isAskingForHelp()) {
            $message .= "Let me make sure I understand what you need help with. Could you tell me more about what you're trying to accomplish?";
        } elseif ($understanding->isSharing()) {
            $message .= "Thanks for sharing that. What would be most helpful for you right now?";
        } else {
            $message .= "How can I help you think through this?";
        }

        return new NeuroResponse([
            'message' => $message,
            'assistance_type' => 'clarification',
            'guiding_questions' => [
                ['question' => 'What are you hoping to accomplish?'],
                ['question' => 'What have you tried so far?'],
            ],
            'response_tone' => 'supportive',
            'confidence' => 0.5,
        ]);
    }

    /**
     * Generate a celebration response for achievements
     */
    public function celebrate(string $achievement, UserProfile $profile): NeuroResponse
    {
        $message = "That's fantastic! {$achievement}\n\n";
        $message .= "Take a moment to appreciate this progress. What do you think made the difference?";

        return NeuroResponse::celebrate($message)->addQuestion(
            'What would you like to focus on next?',
            'To help maintain momentum'
        );
    }

    /**
     * Generate a reflection prompt
     */
    public function promptReflection(string $topic, UserProfile $profile): NeuroResponse
    {
        return NeuroResponse::guide(
            "Let's reflect on {$topic}.",
            [
                "What's working well?",
                "What could be better?",
                "What's one thing you'd change?",
            ],
            []
        );
    }
}


