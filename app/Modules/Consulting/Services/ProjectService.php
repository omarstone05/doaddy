<?php

namespace App\Modules\Consulting\Services;

use App\Modules\Consulting\Models\Project;
use App\Models\User;

class ProjectService
{
    public function createProject(array $data, User $user): Project
    {
        $project = Project::create([
            ...$data,
            'organization_id' => $user->organization_id,
        ]);

        // Log activity
        $this->logActivity($project, 'created', $user);

        return $project;
    }

    public function updateProject(Project $project, array $data, User $user): Project
    {
        $oldValues = $project->toArray();
        
        $project->update($data);
        
        // Log activity
        $this->logActivity($project, 'updated', $user, $oldValues, $project->toArray());

        return $project;
    }

    public function deleteProject(Project $project, User $user): bool
    {
        // Log activity before deletion
        $this->logActivity($project, 'deleted', $user);

        return $project->delete();
    }

    public function updateProjectHealth(Project $project): void
    {
        $project->updateHealthStatus();
    }

    public function updateProjectProgress(Project $project): void
    {
        $project->updateProgress();
    }

    protected function logActivity(Project $project, string $action, User $user, ?array $oldValues = null, ?array $newValues = null): void
    {
        $project->activities()->create([
            'action' => $action,
            'entity_type' => 'project',
            'entity_id' => $project->id,
            'user_id' => $user->id,
            'user_name' => $user->name,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'description' => "Project {$action}",
            'created_at' => now(),
        ]);
    }
}

