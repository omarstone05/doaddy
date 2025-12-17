# NeuroCore - Empowering AI Helper System

## Overview

NeuroCore is a modular, portable AI helper system designed to understand users, build context around their goals, needs, and wants, then help them achieve those goals **without imposing or doing it for them**. It acts as a "silent helper" - guiding through questions and suggestions rather than prescribing solutions.

## Key Principles

1. **User-Triggered**: All interactions start via user chat
2. **Empowering, Not Prescriptive**: Asks questions, suggests approaches, doesn't give direct answers
3. **Silent Helper**: Guides without imposing
4. **Cross-System**: Works across different applications (Addy, Projjo, etc.)
5. **Cross-Subscription**: Single profile works across multiple subscriptions
6. **User-Centric**: Context built around user goals, not just organization data

## Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                         NeuroHelper                              │
│                    (Main Entry Point)                            │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌──────────────────────┐    ┌──────────────────────┐          │
│  │   Understanding      │    │      Context         │          │
│  │   ───────────────    │    │   ──────────────     │          │
│  │  • MessageAnalyzer   │    │  • UserProfile       │          │
│  │  • IntentClassifier  │◄───│  • ConversationMem   │          │
│  │  • PatternDetector   │    │  • PatternRecognizer │          │
│  └──────────────────────┘    └──────────────────────┘          │
│                │                      │                         │
│                └──────────┬───────────┘                         │
│                           ▼                                     │
│               ┌───────────────────────┐                         │
│               │   Response Layer      │                         │
│               │   ───────────────     │                         │
│               │  • EmpowermentEngine  │                         │
│               │  • ToneAdapter        │                         │
│               │  • QuickActions       │                         │
│               └───────────────────────┘                         │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                        Adapters                                  │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────────┐ │
│  │ AIProvider  │  │   Storage   │  │  System Adapter (Addy)  │ │
│  │ Interface   │  │  Interface  │  │  ────────────────────── │ │
│  │ ─────────── │  │ ─────────── │  │  Data, Actions, Context │ │
│  │ OpenAI/Anth │  │ Cache/DB    │  │                         │ │
│  └─────────────┘  └─────────────┘  └─────────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
```

## Quick Start

### Basic Usage (Addy Integration)

```php
use App\NeuroCore\Adapters\AddyNeuroAdapter;

// Create for a user/organization
$neuro = AddyNeuroAdapter::forUser($user, $organization);

// Chat (user-triggered)
$response = $neuro->chat('I want to grow my business to 20 clients');

// Response includes:
// - content: The empowering response
// - assistance_type: clarification|guidance|reflection|celebration|acknowledgment
// - quick_actions: Suggested next steps (user chooses)
// - goal_suggestion: If a goal was detected
```

### Standalone Usage

```php
use App\NeuroCore\NeuroHelper;
use App\NeuroCore\Adapters\CacheStorage;
use App\NeuroCore\Adapters\AddyAIProvider;

$neuro = NeuroHelper::create([
    'user_id' => 'user-123',
    'system_context' => 'my-app',
    'storage' => new CacheStorage('my_app_neuro'),
    'ai' => new AddyAIProvider(),
]);

$response = $neuro->chat('Help me think through my pricing strategy');
```

### Builder Pattern

```php
$neuro = NeuroHelper::forUser('user-456')
    ->inSystem('projjo')
    ->withStorage($myStorage)
    ->withAI($myAIProvider)
    ->build();
```

## Core Concepts

### Goals, Needs, and Wants

- **Goals**: Explicit objectives the user wants to achieve (tracked, progress monitored)
- **Needs**: Support requirements - knowledge gaps, resources needed
- **Wants**: Preferences for interaction - communication style, detail level

```php
// Track a goal
$goal = $neuro->trackGoal('Grow business to 20 clients by Q2');

// Update progress
$neuro->updateGoalProgress($goal->id, 0.5); // 50%

// Get active goals
$goals = $neuro->getActiveGoals();
```

### Response Types

Neuro classifies responses by assistance type:

| Type | Purpose | Example |
|------|---------|---------|
| `clarification` | Understand better | "What does success look like for you?" |
| `guidance` | Guide thinking | "Here are some angles to consider..." |
| `reflection` | Prompt self-discovery | "You mentioned X earlier. How does that connect?" |
| `celebration` | Acknowledge progress | "That's a meaningful milestone!" |
| `acknowledgment` | Validate feelings | "I hear that this is challenging." |

### Quick Actions

Every response can include optional quick actions - suggestions the user can choose to take:

```php
[
    ['label' => 'Track this goal', 'command' => '/track-goal'],
    ['label' => 'Think through more', 'command' => 'Let\'s explore this further'],
    ['label' => 'View related data', 'url' => '/dashboard/sales'],
]
```

## API Endpoints

```
POST   /api/neuro/chat              - Send message
GET    /api/neuro/profile           - Get user profile
GET    /api/neuro/goals             - List active goals
POST   /api/neuro/goals             - Track new goal
PUT    /api/neuro/goals/{id}/progress - Update goal progress
GET    /api/neuro/history           - Get conversation history
POST   /api/neuro/new-conversation  - Start fresh conversation
GET    /api/neuro/insight           - Get proactive insight (if any)
GET    /api/neuro/export            - Export user data
```

## Testing

### Run Tests

```bash
# Via script
php scripts/test-neuro.php

# Via Artisan
php artisan neuro:test

# Via Tinker
php artisan tinker < app/NeuroCore/Tests/run-tests.php
```

### Run Demo

```bash
php scripts/demo-neuro.php
```

## Creating Adapters for Other Systems

### 1. Create a System Adapter

```php
namespace MyApp\NeuroCore;

use App\NeuroCore\NeuroHelper;
use App\NeuroCore\Contracts\AIProviderInterface;
use App\NeuroCore\Contracts\StorageInterface;

class MySystemNeuroAdapter
{
    private NeuroHelper $neuro;
    
    public static function forUser($user): self
    {
        $instance = new self();
        
        $storage = new MyStorage(); // Implement StorageInterface
        $ai = new MyAIProvider();    // Implement AIProviderInterface
        
        $instance->neuro = NeuroHelper::create([
            'user_id' => 'global:' . $user->id,
            'system_context' => 'my-system',
            'storage' => $storage,
            'ai' => $ai,
            'data_provider' => new MyDataProvider($user),
        ]);
        
        return $instance;
    }
    
    public function chat(string $message, array $context = []): array
    {
        // Add system-specific context
        $context['current_page'] = request()->path();
        
        $response = $this->neuro->chat($message, $context);
        
        return $this->formatForMySystem($response);
    }
}
```

### 2. Implement Required Interfaces

```php
// Storage
class MyStorage implements StorageInterface
{
    public function set(string $key, mixed $value, ?int $ttl = null): void { }
    public function get(string $key, mixed $default = null): mixed { }
    public function has(string $key): bool { }
    public function delete(string $key): bool { }
    public function keys(string $pattern): array { }
    public function setInNamespace(string $namespace, string $key, mixed $value): void { }
    public function getNamespace(string $namespace): array { }
    public function flush(): void { }
}

// AI Provider
class MyAIProvider implements AIProviderInterface
{
    public function chat(array $messages, int $maxTokens, float $temperature): array { }
    public function ask(string $prompt, ?string $systemMessage = null): string { }
    public function isAvailable(): bool { }
    public function getModel(): string { }
}
```

## File Structure

```
app/NeuroCore/
├── NeuroHelper.php              # Main entry point
├── NeuroHelperBuilder.php       # Builder pattern
├── README.md                    # This file
│
├── Understanding/
│   ├── MessageAnalyzer.php      # Analyzes user messages
│   └── PatternDetector.php      # Detects behavioral patterns
│
├── Context/
│   ├── UserProfile.php          # User goals, needs, wants
│   └── ConversationMemory.php   # Conversation history
│
├── Response/
│   ├── EmpowermentEngine.php    # Generates empowering responses
│   └── Response.php             # Response DTO
│
├── Data/
│   ├── Goal.php                 # Goal data structure
│   ├── Need.php                 # Need data structure
│   └── Want.php                 # Want data structure
│
├── Contracts/
│   ├── AIProviderInterface.php  # AI abstraction
│   ├── StorageInterface.php     # Storage abstraction
│   └── DataProviderInterface.php # System data access
│
├── Adapters/
│   ├── AddyNeuroAdapter.php     # Addy integration
│   ├── AddyAIProvider.php       # Uses Addy's AIService
│   ├── CacheStorage.php         # Laravel cache storage
│   └── AddyDataProvider.php     # Accesses Addy data
│
└── Tests/
    ├── NeuroHelperTest.php      # Test suite
    └── run-tests.php            # Test runner
```

## Configuration

NeuroCore uses minimal configuration - most settings come from the system adapter:

```php
// Addy adapter pulls from organization settings
$neuro = AddyNeuroAdapter::forUser($user, $organization);
// Uses: tone_preference, enabled_modules, etc. from Organization

// Standalone with explicit config
$neuro = NeuroHelper::create([
    'user_id' => 'user-123',
    'system_context' => 'standalone',
    'config' => [
        'tone' => 'supportive',
        'max_history' => 50,
        'proactive_insights' => true,
    ],
]);
```

## Roadmap

### Phase 1 (Current) ✅
- Core architecture
- Basic chat flow
- Goal tracking
- Addy integration

### Phase 2 (Next)
- Pattern recognition refinement
- Cross-system profile sync
- Enhanced proactive insights
- Multi-subscription support

### Phase 3 (Future)
- Learning from user feedback
- Predictive goal suggestions
- Integration templates for new systems

## Contributing

When adding new functionality:

1. Keep responses empowering, not prescriptive
2. User always initiates interaction
3. Quick actions are suggestions, not requirements
4. Test with both mock and real AI providers
5. Ensure cross-system compatibility
