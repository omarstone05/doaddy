<?php

namespace App\Modules\Consulting\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class TaskStep extends Model
{
    use HasUuids;

    protected $table = 'consulting_task_steps';

    protected $fillable = [
        'task_id',
        'title',
        'description',
        'order',
        'is_completed',
        'completed_at',
        'completed_by',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function completedBy()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}

