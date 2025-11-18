<?php

namespace App\Modules\Consulting\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Communication extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'consulting_communications';

    protected $fillable = [
        'project_id',
        'message',
        'type',
        'channel',
        'user_id',
        'user_name',
        'related_type',
        'related_id',
        'parent_id',
        'attachments',
        'voice_file',
        'voice_duration',
        'visible_to_client',
        'is_pinned',
        'is_edited',
        'edited_at',
    ];

    protected $casts = [
        'attachments' => 'array',
        'voice_duration' => 'integer',
        'visible_to_client' => 'boolean',
        'is_pinned' => 'boolean',
        'is_edited' => 'boolean',
        'edited_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parent()
    {
        return $this->belongsTo(Communication::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(Communication::class, 'parent_id');
    }
}

