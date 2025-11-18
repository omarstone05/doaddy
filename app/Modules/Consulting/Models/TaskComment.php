<?php

namespace App\Modules\Consulting\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class TaskComment extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'consulting_task_comments';

    protected $fillable = [
        'task_id',
        'user_id',
        'parent_comment_id',
        'comment',
        'is_internal',
    ];

    protected $casts = [
        'is_internal' => 'boolean',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parentComment()
    {
        return $this->belongsTo(TaskComment::class, 'parent_comment_id');
    }

    public function replies()
    {
        return $this->hasMany(TaskComment::class, 'parent_comment_id')
            ->with('user')
            ->orderBy('created_at', 'asc');
    }

    public function attachments()
    {
        return $this->hasMany(TaskCommentAttachment::class, 'comment_id');
    }
}

