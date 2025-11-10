<?php

namespace App\Services\Addy;

use App\Models\Organization;
use App\Models\User;
use App\Models\AddyAction;
use App\Models\AddyActionPattern;
use App\Services\Addy\Actions\ActionRegistry;
use Illuminate\Support\Facades\Log;

class ActionExecutionService
{
    protected Organization $organization;
    protected User $user;

    public function __construct(Organization $organization, User $user)
    {
        $this->organization = $organization;
        $this->user = $user;
    }

    /**
     * Prepare an action for confirmation
     */
    public function prepareAction(string $actionType, array $parameters = [], $chatMessageId = null): AddyAction
    {
        $actionDef = ActionRegistry::get($actionType);
        
        if (!$actionDef) {
            throw new \Exception("Unknown action type: {$actionType}");
        }

        // Instantiate action handler
        $handler = new $actionDef['class']($this->organization, $this->user, $parameters);

        // Validate
        if (!$handler->validate()) {
            throw new \Exception("Invalid parameters for action: {$actionType}");
        }

        // Check permissions
        if (!$handler->hasPermissions()) {
            throw new \Exception("Insufficient permissions for action: {$actionType}");
        }

        // Generate preview
        $preview = $handler->preview();

        // Create action record
        $action = AddyAction::create([
            'organization_id' => $this->organization->id,
            'user_id' => $this->user->id,
            'chat_message_id' => $chatMessageId,
            'action_type' => $actionType,
            'category' => $actionDef['category'],
            'status' => 'pending',
            'parameters' => $parameters,
            'preview_data' => $preview,
        ]);

        // Record suggestion
        $pattern = AddyActionPattern::getOrCreate(
            $this->organization->id,
            $this->user->id,
            $actionType
        );
        $pattern->recordSuggestion();

        return $action;
    }

    /**
     * Execute a confirmed action
     */
    public function executeAction(AddyAction $action): array
    {
        if ($action->status !== 'confirmed') {
            throw new \Exception('Action must be confirmed before execution');
        }

        $actionDef = ActionRegistry::get($action->action_type);
        $handler = new $actionDef['class'](
            $this->organization, 
            $this->user, 
            $action->parameters
        );

        try {
            Log::info("Executing action: {$action->action_type}", [
                'action_id' => $action->id,
                'user_id' => $this->user->id,
            ]);

            // Execute
            $result = $handler->execute();

            // Mark as executed
            $action->markExecuted($result, true);

            // Record success
            $pattern = AddyActionPattern::getOrCreate(
                $this->organization->id,
                $this->user->id,
                $action->action_type
            );
            $pattern->recordSuccess();

            Log::info("Action executed successfully: {$action->action_type}", [
                'action_id' => $action->id,
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error("Action execution failed: {$action->action_type}", [
                'action_id' => $action->id,
                'error' => $e->getMessage(),
            ]);

            $action->fail($e->getMessage());

            throw $e;
        }
    }

    /**
     * Confirm an action
     */
    public function confirmAction(AddyAction $action): void
    {
        $action->confirm();

        $pattern = AddyActionPattern::getOrCreate(
            $this->organization->id,
            $this->user->id,
            $action->action_type
        );
        $pattern->recordConfirmation([
            'day' => now()->format('l'),
            'hour' => now()->hour,
        ]);
    }

    /**
     * Reject/cancel an action
     */
    public function rejectAction(AddyAction $action, string $reason = null): void
    {
        $action->cancel();

        $pattern = AddyActionPattern::getOrCreate(
            $this->organization->id,
            $this->user->id,
            $action->action_type
        );
        $pattern->recordRejection([
            'reason' => $reason,
            'day' => now()->format('l'),
            'hour' => now()->hour,
        ]);
    }

    /**
     * Get suggested actions based on learning
     */
    public function getSuggestedActions(): array
    {
        $patterns = AddyActionPattern::where('organization_id', $this->organization->id)
            ->where('user_id', $this->user->id)
            ->get();

        $suggestions = [];

        foreach ($patterns as $pattern) {
            if ($pattern->shouldSuggest()) {
                $actionDef = ActionRegistry::get($pattern->action_type);
                
                if ($actionDef) {
                    $suggestions[] = [
                        'action_type' => $pattern->action_type,
                        'category' => $actionDef['category'] ?? 'general',
                        'title' => $actionDef['label'] ?? $pattern->action_type,
                        'description' => $actionDef['description'] ?? '',
                        'confidence' => $pattern->getConfidence(),
                    ];
                }
            }
        }

        // Sort by confidence
        usort($suggestions, fn($a, $b) => $b['confidence'] <=> $a['confidence']);

        return $suggestions;
    }
}

