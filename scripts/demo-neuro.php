<?php

/**
 * NeuroCore Interactive Demo
 * 
 * Run from project root:
 *   php scripts/demo-neuro.php
 * 
 * This script provides an interactive demo of NeuroCore
 */

// Bootstrap Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\NeuroCore\NeuroHelper;
use App\NeuroCore\Adapters\CacheStorage;
use App\NeuroCore\Adapters\AddyAIProvider;
use App\NeuroCore\Adapters\AddyNeuroAdapter;
use App\Models\User;
use App\Models\Organization;

echo "\n";
echo "╔══════════════════════════════════════════════════╗\n";
echo "║        🧠 NeuroCore Interactive Demo             ║\n";
echo "╚══════════════════════════════════════════════════╝\n";
echo "\n";

// Check for user/org or use mock
$user = User::first();
$org = $user?->organization;

if ($user && $org) {
    echo "Using real user: {$user->name} @ {$org->name}\n";
    echo "Note: This will use the configured AI provider\n\n";
    
    try {
        $neuro = AddyNeuroAdapter::forUser($user, $org);
        echo "✅ NeuroCore initialized with Addy adapter\n\n";
    } catch (\Exception $e) {
        echo "⚠️ Could not initialize with real AI: {$e->getMessage()}\n";
        echo "Falling back to mock mode...\n\n";
        $neuro = createMockNeuro();
    }
} else {
    echo "No user found. Using mock mode.\n\n";
    $neuro = createMockNeuro();
}

// Demo conversation
echo str_repeat("-", 50) . "\n";
echo "Demo Conversation:\n";
echo str_repeat("-", 50) . "\n\n";

$demoMessages = [
    "Hello! I'm new here.",
    "I want to grow my consulting business to 10 clients by end of year",
    "I currently have 3 clients. What should I think about?",
    "How do I track my progress?",
];

foreach ($demoMessages as $i => $message) {
    echo "👤 User: {$message}\n";
    echo str_repeat("-", 40) . "\n";
    
    try {
        if ($neuro instanceof AddyNeuroAdapter) {
            $response = $neuro->chat($message);
            $content = $response['content'] ?? 'No response';
        } else {
            $response = $neuro->chat($message);
            $content = $response->message ?? 'No response';
        }
        
        echo "🧠 Neuro: {$content}\n";
        
        // Show quick actions if available
        $quickActions = $response['quick_actions'] ?? $response->quickActions ?? [];
        if (!empty($quickActions)) {
            echo "\n   Quick actions: ";
            echo implode(' | ', array_map(fn($a) => $a['label'] ?? $a, $quickActions));
            echo "\n";
        }
        
        // Show goal suggestion if available
        $goalSuggestion = $response['goal_suggestion'] ?? $response->goalSuggestion ?? null;
        if ($goalSuggestion) {
            echo "\n   🎯 Goal detected: " . ($goalSuggestion['description'] ?? json_encode($goalSuggestion)) . "\n";
        }
        
    } catch (\Exception $e) {
        echo "❌ Error: {$e->getMessage()}\n";
    }
    
    echo "\n";
    
    // Small delay for readability
    if ($i < count($demoMessages) - 1) {
        sleep(1);
    }
}

// Show profile summary
echo str_repeat("-", 50) . "\n";
echo "User Profile Summary:\n";
echo str_repeat("-", 50) . "\n\n";

try {
    if ($neuro instanceof AddyNeuroAdapter) {
        $profile = $neuro->getProfile();
        $goals = $neuro->getActiveGoals();
    } else {
        $profile = $neuro->getProfile()->toArray();
        $goals = array_map(fn($g) => $g->toArray(), $neuro->getActiveGoals());
    }
    
    echo "Goals tracked: " . count($goals) . "\n";
    foreach ($goals as $goal) {
        $desc = is_array($goal) ? $goal['description'] : $goal->description;
        echo "  - {$desc}\n";
    }
    
    echo "\nInteraction count: " . ($profile['interaction_count'] ?? 'N/A') . "\n";
    
    $topics = $profile['topic_frequency'] ?? [];
    if (!empty($topics)) {
        arsort($topics);
        echo "Top topics: " . implode(', ', array_slice(array_keys($topics), 0, 3)) . "\n";
    }
    
} catch (\Exception $e) {
    echo "Could not get profile: {$e->getMessage()}\n";
}

echo "\n✅ Demo complete!\n\n";

// Helper function for mock mode
function createMockNeuro() {
    require_once __DIR__ . '/../app/NeuroCore/Tests/NeuroHelperTest.php';
    
    $storage = new CacheStorage('neuro_demo');
    $mockAI = new \App\NeuroCore\Tests\MockAIProvider();
    
    return NeuroHelper::create([
        'user_id' => 'demo-user',
        'system_context' => 'demo',
        'storage' => $storage,
        'ai' => $mockAI,
    ]);
}


