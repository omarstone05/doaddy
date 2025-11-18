<?php

namespace App\Modules\Consulting\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class TaskCommentAttachment extends Model
{
    use HasUuids;

    protected $table = 'consulting_task_comment_attachments';

    protected $fillable = [
        'comment_id',
        'file_name',
        'original_name',
        'file_path',
        'file_type',
        'file_size',
        'uploaded_by',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function comment()
    {
        return $this->belongsTo(TaskComment::class, 'comment_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}

