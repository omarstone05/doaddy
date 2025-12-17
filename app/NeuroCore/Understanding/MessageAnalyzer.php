<?php

namespace App\NeuroCore\Understanding;

use App\NeuroCore\Contracts\AIProviderInterface;
use App\NeuroCore\Context\UserProfile;
use App\NeuroCore\Data\NeuroUnderstanding;

/**
 * MessageAnalyzer - Analyzes user messages to extract understanding
 * This is the brain of the understanding layer
 */
class MessageAnalyzer
{
    private AIProviderInterface $ai;

    public function __construct(AIProviderInterface $ai)
    {
        $this->ai = $ai;
    }

    /**
     * Analyze a user message
     */
    public function analyze(
        string $message,
        UserProfile $profile,
        array $conversationHistory = [],
        array $context = []
    ): NeuroUnderstanding {
        // First, do quick pattern-based analysis
        $quickAnalysis = $this->quickAnalyze($message);

        // If message is simple (greeting, brief), skip AI analysis
        if ($this->isSimpleMessage($message, $quickAnalysis)) {
            return $this->buildSimpleUnderstanding($message, $quickAnalysis);
        }

        // Use AI for deeper analysis
        try {
            $aiAnalysis = $this->aiAnalyze($message, $profile, $conversationHistory, $context);
            return $this->mergeAnalysis($message, $quickAnalysis, $aiAnalysis);
        } catch (\Exception $e) {
            // Fallback to quick analysis if AI fails
            \Log::warning('Neuro AI analysis failed, using fallback', ['error' => $e->getMessage()]);
            return $this->buildSimpleUnderstanding($message, $quickAnalysis);
        }
    }

    /**
     * Quick pattern-based analysis (no AI)
     */
    private function quickAnalyze(string $message): array
    {
        $message = trim($message);
        $lowerMessage = strtolower($message);

        $analysis = [
            'is_greeting' => $this->isGreeting($lowerMessage),
            'is_question' => str_contains($message, '?') || $this->startsWithQuestionWord($lowerMessage),
            'is_urgent' => $this->detectUrgency($lowerMessage),
            'sentiment' => $this->detectBasicSentiment($lowerMessage),
            'has_numbers' => preg_match('/\d+/', $message),
            'has_dates' => $this->detectDates($lowerMessage),
            'word_count' => str_word_count($message),
            'detected_topics' => $this->detectTopics($lowerMessage),
            'entities' => $this->extractBasicEntities($message),
        ];

        // Detect potential goals in the message
        $analysis['potential_goals'] = $this->detectPotentialGoals($lowerMessage);

        // Detect potential needs
        $analysis['potential_needs'] = $this->detectPotentialNeeds($lowerMessage);

        return $analysis;
    }

    /**
     * Check if greeting
     */
    private function isGreeting(string $message): bool
    {
        $greetings = ['hi', 'hello', 'hey', 'good morning', 'good afternoon', 'good evening', 'howdy', 'greetings'];
        foreach ($greetings as $greeting) {
            if (str_starts_with($message, $greeting)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if starts with question word
     */
    private function startsWithQuestionWord(string $message): bool
    {
        $questionWords = ['what', 'how', 'why', 'when', 'where', 'who', 'which', 'can', 'could', 'should', 'would', 'is', 'are', 'do', 'does'];
        foreach ($questionWords as $word) {
            if (str_starts_with($message, $word . ' ')) {
                return true;
            }
        }
        return false;
    }

    /**
     * Detect urgency
     */
    private function detectUrgency(string $message): float
    {
        $urgentWords = ['urgent', 'asap', 'immediately', 'now', 'critical', 'emergency', 'deadline', 'today', 'right away'];
        $urgency = 0.3; // Base

        foreach ($urgentWords as $word) {
            if (str_contains($message, $word)) {
                $urgency += 0.2;
            }
        }

        // Exclamation marks add urgency
        $urgency += min(0.2, substr_count($message, '!') * 0.05);

        return min(1.0, $urgency);
    }

    /**
     * Basic sentiment detection
     */
    private function detectBasicSentiment(string $message): string
    {
        $positive = ['great', 'good', 'excellent', 'happy', 'excited', 'thanks', 'thank you', 'love', 'awesome', 'perfect'];
        $negative = ['bad', 'terrible', 'frustrated', 'angry', 'upset', 'hate', 'annoyed', 'problem', 'issue', 'wrong', 'failed'];
        $confused = ['confused', 'unsure', 'don\'t understand', 'not sure', 'help me understand'];

        $positiveCount = 0;
        $negativeCount = 0;

        foreach ($positive as $word) {
            if (str_contains($message, $word)) $positiveCount++;
        }
        foreach ($negative as $word) {
            if (str_contains($message, $word)) $negativeCount++;
        }
        foreach ($confused as $word) {
            if (str_contains($message, $word)) return 'confused';
        }

        if ($positiveCount > $negativeCount) return 'positive';
        if ($negativeCount > $positiveCount) return 'negative';
        return 'neutral';
    }

    /**
     * Detect dates in message
     */
    private function detectDates(string $message): bool
    {
        $datePatterns = [
            '/\d{1,2}\/\d{1,2}\/\d{2,4}/',
            '/\d{4}-\d{2}-\d{2}/',
            '/\b(january|february|march|april|may|june|july|august|september|october|november|december)\b/i',
            '/\b(monday|tuesday|wednesday|thursday|friday|saturday|sunday)\b/i',
            '/\b(today|tomorrow|yesterday|next week|last week|this month)\b/i',
        ];

        foreach ($datePatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Detect topics
     */
    private function detectTopics(string $message): array
    {
        $topicPatterns = [
            'finance' => ['money', 'cash', 'budget', 'expense', 'revenue', 'profit', 'payment', 'invoice', 'cost'],
            'sales' => ['sales', 'customer', 'client', 'deal', 'revenue', 'quote', 'proposal'],
            'team' => ['team', 'employee', 'staff', 'hire', 'payroll', 'leave', 'vacation'],
            'inventory' => ['inventory', 'stock', 'product', 'order', 'supplier'],
            'planning' => ['plan', 'goal', 'target', 'objective', 'strategy', 'milestone'],
            'marketing' => ['marketing', 'campaign', 'advertising', 'promotion', 'brand'],
            'operations' => ['process', 'workflow', 'efficiency', 'productivity'],
        ];

        $detected = [];
        foreach ($topicPatterns as $topic => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($message, $keyword)) {
                    $detected[] = $topic;
                    break;
                }
            }
        }

        return array_unique($detected);
    }

    /**
     * Extract basic entities
     */
    private function extractBasicEntities(string $message): array
    {
        $entities = [];

        // Extract numbers/amounts
        if (preg_match_all('/\$?\d+(?:,\d{3})*(?:\.\d{2})?/', $message, $matches)) {
            foreach ($matches[0] as $match) {
                $entities[] = ['type' => 'amount', 'value' => $match];
            }
        }

        // Extract percentages
        if (preg_match_all('/\d+(?:\.\d+)?%/', $message, $matches)) {
            foreach ($matches[0] as $match) {
                $entities[] = ['type' => 'percentage', 'value' => $match];
            }
        }

        // Extract emails
        if (preg_match_all('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $message, $matches)) {
            foreach ($matches[0] as $match) {
                $entities[] = ['type' => 'email', 'value' => $match];
            }
        }

        return $entities;
    }

    /**
     * Detect potential goals
     */
    private function detectPotentialGoals(string $message): array
    {
        $goals = [];
        
        $goalPatterns = [
            '/i want to ([^.!?]+)/i',
            '/i\'m trying to ([^.!?]+)/i',
            '/my goal is to ([^.!?]+)/i',
            '/i need to ([^.!?]+)/i',
            '/i\'d like to ([^.!?]+)/i',
            '/planning to ([^.!?]+)/i',
            '/hoping to ([^.!?]+)/i',
            '/working on ([^.!?]+)/i',
            '/aiming for ([^.!?]+)/i',
        ];

        foreach ($goalPatterns as $pattern) {
            if (preg_match($pattern, $message, $matches)) {
                $goals[] = [
                    'description' => trim($matches[1]),
                    'confidence' => 0.7,
                ];
            }
        }

        return $goals;
    }

    /**
     * Detect potential needs
     */
    private function detectPotentialNeeds(string $message): array
    {
        $needs = [];

        $needPatterns = [
            '/i need help with ([^.!?]+)/i',
            '/i\'m struggling with ([^.!?]+)/i',
            '/how do i ([^.!?]+)/i',
            '/can you help me ([^.!?]+)/i',
            '/i don\'t know how to ([^.!?]+)/i',
            '/i need ([^.!?]+)/i',
        ];

        foreach ($needPatterns as $pattern) {
            if (preg_match($pattern, $message, $matches)) {
                $needs[] = [
                    'description' => trim($matches[1]),
                    'category' => 'support',
                    'confidence' => 0.6,
                ];
            }
        }

        return $needs;
    }

    /**
     * Check if simple message (no AI needed)
     */
    private function isSimpleMessage(string $message, array $quickAnalysis): bool
    {
        // Greetings are simple
        if ($quickAnalysis['is_greeting'] && $quickAnalysis['word_count'] < 10) {
            return true;
        }

        // Very short messages
        if ($quickAnalysis['word_count'] < 3) {
            return true;
        }

        return false;
    }

    /**
     * Build understanding from quick analysis only
     */
    private function buildSimpleUnderstanding(string $message, array $quickAnalysis): NeuroUnderstanding
    {
        $intent = 'other';
        if ($quickAnalysis['is_greeting']) {
            $intent = 'greeting';
        } elseif ($quickAnalysis['is_question']) {
            $intent = 'ask_for_help';
        }

        return new NeuroUnderstanding([
            'original_message' => $message,
            'intent' => $intent,
            'intent_confidence' => 0.7,
            'detected_goals' => $quickAnalysis['potential_goals'] ?? [],
            'detected_needs' => $quickAnalysis['potential_needs'] ?? [],
            'detected_wants' => [],
            'entities' => $quickAnalysis['entities'] ?? [],
            'sentiment' => $quickAnalysis['sentiment'],
            'urgency' => $quickAnalysis['is_urgent'] ? 0.8 : 0.3,
            'topics' => $quickAnalysis['detected_topics'] ?? [],
            'questions' => $quickAnalysis['is_question'] ? [$message] : [],
            'seeking_guidance' => $quickAnalysis['is_question'],
        ]);
    }

    /**
     * Use AI for deeper analysis
     */
    private function aiAnalyze(
        string $message,
        UserProfile $profile,
        array $conversationHistory,
        array $context
    ): array {
        $systemPrompt = $this->buildAnalysisPrompt($profile);

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        // Add recent conversation for context
        foreach (array_slice($conversationHistory, -4) as $msg) {
            $messages[] = $msg;
        }

        $messages[] = [
            'role' => 'user',
            'content' => "Analyze this message from the user:\n\n\"{$message}\"\n\nProvide analysis as JSON.",
        ];

        $response = $this->ai->chat($messages, 800, 0.3);
        $content = $response['content'];

        // Parse JSON from response
        if (preg_match('/```json\s*([\s\S]*?)\s*```/', $content, $matches)) {
            $content = $matches[1];
        } elseif (preg_match('/```\s*([\s\S]*?)\s*```/', $content, $matches)) {
            $content = $matches[1];
        }

        $analysis = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Failed to parse AI analysis: ' . json_last_error_msg());
        }

        return $analysis;
    }

    /**
     * Build the analysis prompt
     */
    private function buildAnalysisPrompt(UserProfile $profile): string
    {
        return <<<PROMPT
You are an analytical component of a Neuro Helper system. Your job is to deeply understand user messages.

Analyze the user message and return a JSON object with these fields:

{
    "intent": "share_information|ask_for_help|request_action|clarify|reflect|greeting|other",
    "sub_intent": "more specific intent if applicable",
    "intent_confidence": 0.0-1.0,
    "detected_goals": [
        {"description": "goal text", "category": "business|financial|personal|learning", "timeframe": "if mentioned"}
    ],
    "detected_needs": [
        {"description": "need text", "category": "efficiency|capability|knowledge|resource|support", "priority": 1-5}
    ],
    "detected_wants": [
        {"description": "preference text", "category": "communication|workflow|ui|timing|style"}
    ],
    "sentiment": "positive|negative|neutral|frustrated|excited|confused|urgent",
    "urgency": 0.0-1.0,
    "topics": ["topic1", "topic2"],
    "questions": ["explicit or implicit questions the user has"],
    "seeking_guidance": true|false,
    "action_request": "specific action requested or null",
    "context_hints": ["helpful context for response"]
}

USER'S KNOWN CONTEXT:
- Active Goals: {$profile->getGoalsSummary()}
- Known Needs: {$profile->getNeedsSummary()}
- Preferences: {$profile->getWantsSummary()}

Be thorough but concise. Focus on understanding what the user really wants/needs, not just what they said.
Output ONLY valid JSON, no additional text.
PROMPT;
    }

    /**
     * Merge quick and AI analysis
     */
    private function mergeAnalysis(string $message, array $quickAnalysis, array $aiAnalysis): NeuroUnderstanding
    {
        // Combine entities
        $entities = array_merge(
            $quickAnalysis['entities'] ?? [],
            $aiAnalysis['entities'] ?? []
        );

        // Combine topics
        $topics = array_unique(array_merge(
            $quickAnalysis['detected_topics'] ?? [],
            $aiAnalysis['topics'] ?? []
        ));

        return new NeuroUnderstanding([
            'original_message' => $message,
            'intent' => $aiAnalysis['intent'] ?? 'other',
            'sub_intent' => $aiAnalysis['sub_intent'] ?? null,
            'intent_confidence' => $aiAnalysis['intent_confidence'] ?? 0.7,
            'detected_goals' => $aiAnalysis['detected_goals'] ?? $quickAnalysis['potential_goals'] ?? [],
            'detected_needs' => $aiAnalysis['detected_needs'] ?? $quickAnalysis['potential_needs'] ?? [],
            'detected_wants' => $aiAnalysis['detected_wants'] ?? [],
            'entities' => $entities,
            'sentiment' => $aiAnalysis['sentiment'] ?? $quickAnalysis['sentiment'],
            'urgency' => $aiAnalysis['urgency'] ?? ($quickAnalysis['is_urgent'] ? 0.8 : 0.3),
            'topics' => $topics,
            'questions' => $aiAnalysis['questions'] ?? [],
            'seeking_guidance' => $aiAnalysis['seeking_guidance'] ?? $quickAnalysis['is_question'],
            'action_request' => $aiAnalysis['action_request'] ?? null,
            'context_hints' => $aiAnalysis['context_hints'] ?? [],
            'raw_analysis' => $aiAnalysis,
        ]);
    }
}


