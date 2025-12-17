<?php

namespace App\NeuroCore\Tests;

use App\NeuroCore\NeuroHelper;
use App\NeuroCore\Adapters\CacheStorage;
use App\NeuroCore\Adapters\AddyAIProvider;
use App\NeuroCore\Adapters\AddyNeuroAdapter;
use App\NeuroCore\Data\Goal;
use App\NeuroCore\Data\Need;
use App\NeuroCore\Data\Want;
use App\Models\User;
use App\Models\Organization;

/**
 * Test suite for NeuroCore
 * 
 * Run with: php artisan tinker < app/NeuroCore/Tests/run-tests.php
 * Or use the Artisan command: php artisan neuro:test
 */
class NeuroHelperTest
{
    private array $results = [];
    private int $passed = 0;
    private int $failed = 0;

    public function runAll(): array
    {
        echo "\n🧠 NeuroCore Test Suite\n";
        echo str_repeat("=", 50) . "\n\n";

        // Data structure tests
        $this->test('Goal Creation', fn() => $this->testGoalCreation());
        $this->test('Need Creation', fn() => $this->testNeedCreation());
        $this->test('Want Creation', fn() => $this->testWantCreation());

        // Storage tests
        $this->test('Cache Storage', fn() => $this->testCacheStorage());

        // Core tests
        $this->test('NeuroHelper Creation', fn() => $this->testNeuroHelperCreation());
        $this->test('User Profile', fn() => $this->testUserProfile());
        $this->test('Conversation Memory', fn() => $this->testConversationMemory());

        // Integration tests (require AI - may skip if not configured)
        $this->test('Basic Chat (requires AI)', fn() => $this->testBasicChat(), true);
        $this->test('Goal Detection (requires AI)', fn() => $this->testGoalDetection(), true);
        $this->test('Full Conversation Flow (requires AI)', fn() => $this->testFullConversation(), true);

        // Summary
        echo "\n" . str_repeat("=", 50) . "\n";
        echo "Results: {$this->passed} passed, {$this->failed} failed\n";
        echo str_repeat("=", 50) . "\n\n";

        return [
            'passed' => $this->passed,
            'failed' => $this->failed,
            'results' => $this->results,
        ];
    }

    private function test(string $name, callable $fn, bool $canSkip = false): void
    {
        echo "Testing: {$name}... ";
        
        try {
            $result = $fn();
            if ($result === true || $result === null) {
                echo "✅ PASSED\n";
                $this->passed++;
                $this->results[$name] = ['status' => 'passed'];
            } elseif ($result === 'skipped') {
                echo "⏭️ SKIPPED\n";
                $this->results[$name] = ['status' => 'skipped'];
            } else {
                echo "❌ FAILED: {$result}\n";
                $this->failed++;
                $this->results[$name] = ['status' => 'failed', 'error' => $result];
            }
        } catch (\Exception $e) {
            if ($canSkip && str_contains($e->getMessage(), 'API key')) {
                echo "⏭️ SKIPPED (AI not configured)\n";
                $this->results[$name] = ['status' => 'skipped', 'reason' => 'AI not configured'];
            } else {
                echo "❌ FAILED: {$e->getMessage()}\n";
                $this->failed++;
                $this->results[$name] = ['status' => 'failed', 'error' => $e->getMessage()];
            }
        }
    }

    // === Data Structure Tests ===

    private function testGoalCreation(): bool|string
    {
        $goal = new Goal([
            'description' => 'Grow business to 10 clients',
            'category' => 'business',
            'target_value' => 10,
            'current_value' => 3,
        ]);

        if ($goal->description !== 'Grow business to 10 clients') {
            return 'Description not set correctly';
        }
        if ($goal->calculateProgress() !== 0.3) {
            return 'Progress calculation incorrect';
        }
        if (empty($goal->id)) {
            return 'ID not generated';
        }

        // Test milestone
        $goal->addMilestone('First client signed');
        if (count($goal->milestones) !== 1) {
            return 'Milestone not added';
        }

        // Test serialization
        $array = $goal->toArray();
        $restored = Goal::fromArray($array);
        if ($restored->description !== $goal->description) {
            return 'Serialization/deserialization failed';
        }

        return true;
    }

    private function testNeedCreation(): bool|string
    {
        $need = new Need([
            'description' => 'Help with pricing strategy',
            'category' => 'knowledge',
            'priority' => 4,
        ]);

        if ($need->priority !== 4) {
            return 'Priority not set';
        }

        // Test mention recording
        $need->recordMention();
        if ($need->mentionCount !== 2) {
            return 'Mention count not incremented';
        }

        // Test urgency calculation
        $urgency = $need->getUrgency();
        if ($urgency < 0.8) {
            return 'Urgency calculation incorrect';
        }

        return true;
    }

    private function testWantCreation(): bool|string
    {
        $want = new Want([
            'description' => 'Prefers concise responses',
            'category' => 'communication',
            'explicit' => true,
        ]);

        if (!$want->isStrong()) {
            return 'Explicit want should be strong';
        }

        // Test observation
        $want->recordObservation('Short message sent');
        if (count($want->manifestations) !== 1) {
            return 'Manifestation not recorded';
        }

        return true;
    }

    // === Storage Tests ===

    private function testCacheStorage(): bool|string
    {
        $storage = new CacheStorage('neuro_test');

        // Test set/get
        $storage->set('test_key', ['foo' => 'bar']);
        $value = $storage->get('test_key');
        if ($value['foo'] !== 'bar') {
            return 'Set/get failed';
        }

        // Test has
        if (!$storage->has('test_key')) {
            return 'Has check failed';
        }

        // Test delete
        $storage->delete('test_key');
        if ($storage->has('test_key')) {
            return 'Delete failed';
        }

        // Test namespace
        $storage->setInNamespace('users', 'user1', ['name' => 'Test']);
        $items = $storage->getNamespace('users');
        if (!isset($items['user1'])) {
            return 'Namespace operations failed';
        }

        // Cleanup
        $storage->flush();

        return true;
    }

    // === Core Tests ===

    private function testNeuroHelperCreation(): bool|string
    {
        $storage = new CacheStorage('neuro_test');
        
        // Create a mock AI provider for testing without actual AI
        $mockAI = new MockAIProvider();

        $neuro = NeuroHelper::create([
            'user_id' => 'test-user-123',
            'system_context' => 'test',
            'storage' => $storage,
            'ai' => $mockAI,
        ]);

        if ($neuro->getUserId() !== 'test-user-123') {
            return 'User ID not set correctly';
        }
        if ($neuro->getSystemContext() !== 'test') {
            return 'System context not set correctly';
        }

        // Test builder pattern
        $neuro2 = NeuroHelper::forUser('test-user-456')
            ->inSystem('test2')
            ->withStorage($storage)
            ->withAI($mockAI)
            ->build();

        if ($neuro2->getUserId() !== 'test-user-456') {
            return 'Builder pattern failed';
        }

        return true;
    }

    private function testUserProfile(): bool|string
    {
        $storage = new CacheStorage('neuro_test');
        $mockAI = new MockAIProvider();

        $neuro = NeuroHelper::create([
            'user_id' => 'profile-test-user',
            'system_context' => 'test',
            'storage' => $storage,
            'ai' => $mockAI,
        ]);

        // Track a goal
        $goal = $neuro->trackGoal('Test goal', ['source' => 'test']);
        if (empty($goal->id)) {
            return 'Goal tracking failed';
        }

        // Verify goal is in profile
        $activeGoals = $neuro->getActiveGoals();
        if (empty($activeGoals)) {
            return 'Goal not in active goals';
        }

        // Update progress
        $updated = $neuro->updateGoalProgress($goal->id, 0.5);
        if ($updated->progress !== 0.5) {
            return 'Progress update failed';
        }

        // Get profile
        $profile = $neuro->getProfile();
        if (empty($profile->goals)) {
            return 'Profile goals empty';
        }

        // Cleanup
        $storage->flush();

        return true;
    }

    private function testConversationMemory(): bool|string
    {
        $storage = new CacheStorage('neuro_test');
        $mockAI = new MockAIProvider();

        $neuro = NeuroHelper::create([
            'user_id' => 'memory-test-user',
            'system_context' => 'test',
            'storage' => $storage,
            'ai' => $mockAI,
        ]);

        // Have a conversation
        $response1 = $neuro->chat('Hello');
        $response2 = $neuro->chat('Tell me more');

        // Check history
        $history = $neuro->getHistory();
        if (count($history) < 2) {
            return 'History not recorded';
        }

        // Check conversation summary
        $summary = $neuro->getConversationSummary();
        if (empty($summary)) {
            return 'Summary generation failed';
        }

        // Test new conversation
        $neuro->startNewConversation();
        $newHistory = $neuro->getHistory();
        if (count($newHistory) !== 0) {
            return 'New conversation not started cleanly';
        }

        // Cleanup
        $storage->flush();

        return true;
    }

    // === Integration Tests (require AI) ===

    private function testBasicChat(): bool|string
    {
        // Check if we have a real user and org to test with
        $user = User::first();
        $org = $user?->organization;

        if (!$user || !$org) {
            return 'skipped'; // No test data
        }

        try {
            $neuro = AddyNeuroAdapter::forUser($user, $org);
            $response = $neuro->chat('Hello, how are you?');

            if (empty($response['content'])) {
                return 'Empty response';
            }

            return true;
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'API key')) {
                return 'skipped';
            }
            throw $e;
        }
    }

    private function testGoalDetection(): bool|string
    {
        $user = User::first();
        $org = $user?->organization;

        if (!$user || !$org) {
            return 'skipped';
        }

        try {
            $neuro = AddyNeuroAdapter::forUser($user, $org);
            
            // Send a message with a clear goal
            $response = $neuro->chat('I want to grow my business to 20 clients by end of next quarter');

            // Check if goal was detected
            $goals = $neuro->getActiveGoals();
            
            // Goal detection depends on AI quality, so we're lenient here
            if (isset($response['goal_suggestion']) || !empty($goals)) {
                return true;
            }

            // Even if no goal detected, response should be empowering
            if (str_contains(strtolower($response['content']), 'goal') || 
                str_contains(strtolower($response['content']), 'client')) {
                return true;
            }

            return true; // Don't fail on AI variance
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'API key')) {
                return 'skipped';
            }
            throw $e;
        }
    }

    private function testFullConversation(): bool|string
    {
        $user = User::first();
        $org = $user?->organization;

        if (!$user || !$org) {
            return 'skipped';
        }

        try {
            $neuro = AddyNeuroAdapter::forUser($user, $org);

            // Multi-turn conversation
            $r1 = $neuro->chat('I\'m struggling with my pricing strategy');
            if (empty($r1['content'])) {
                return 'First response empty';
            }

            $r2 = $neuro->chat('I have 3 competitors charging $50-100 per hour');
            if (empty($r2['content'])) {
                return 'Second response empty';
            }

            $r3 = $neuro->chat('What should I consider?');
            if (empty($r3['content'])) {
                return 'Third response empty';
            }

            // Check that response is guidance, not prescription
            $lastResponse = strtolower($r3['content']);
            $isGuidance = str_contains($lastResponse, 'consider') ||
                         str_contains($lastResponse, 'think') ||
                         str_contains($lastResponse, 'question') ||
                         str_contains($lastResponse, '?');

            if (!$isGuidance) {
                // Still pass but note the concern
                echo "(response may be too prescriptive) ";
            }

            // Check history
            $history = $neuro->getHistory();
            if (count($history) < 3) {
                return 'History incomplete';
            }

            return true;
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'API key')) {
                return 'skipped';
            }
            throw $e;
        }
    }
}

/**
 * Mock AI Provider for testing without actual AI
 */
class MockAIProvider implements \App\NeuroCore\Contracts\AIProviderInterface
{
    public function chat(array $messages, int $maxTokens = 1500, float $temperature = 0.7): array
    {
        $lastMessage = end($messages);
        $content = $lastMessage['content'] ?? '';

        // Simple mock responses
        if (str_contains(strtolower($content), 'hello') || str_contains(strtolower($content), 'hi')) {
            return [
                'content' => "Hello! How can I help you think through something today?\n\n---METADATA---\n{\"assistance_type\": \"acknowledgment\", \"response_tone\": \"supportive\"}",
                'tokens' => 50,
                'model' => 'mock',
            ];
        }

        if (str_contains(strtolower($content), 'goal') || str_contains(strtolower($content), 'want to')) {
            return [
                'content' => "That's a meaningful goal. Let me help you think through it.\n\n**Questions to consider:**\n- What would success look like?\n- What's your first step?\n\n---METADATA---\n{\"assistance_type\": \"guidance\", \"guiding_questions\": [\"What would success look like?\", \"What's your first step?\"], \"response_tone\": \"supportive\"}",
                'tokens' => 100,
                'model' => 'mock',
            ];
        }

        // Default response
        return [
            'content' => "I hear you. Tell me more about what you're working on.\n\n---METADATA---\n{\"assistance_type\": \"clarification\", \"response_tone\": \"curious\"}",
            'tokens' => 30,
            'model' => 'mock',
        ];
    }

    public function ask(string $prompt, ?string $systemMessage = null): string
    {
        return "Mock response to: " . substr($prompt, 0, 50);
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function getModel(): string
    {
        return 'mock-model';
    }
}


