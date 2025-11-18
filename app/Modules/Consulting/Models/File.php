<?php

namespace App\Modules\Consulting\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class File extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'consulting_files';

    protected $fillable = [
        'project_id',
        'folder_id',
        'name',
        'original_name',
        'file_path',
        'file_type',
        'file_size',
        'category',
        'uploaded_by',
        'uploaded_at',
        'version',
        'parent_file_id',
        'visible_to_client',
        'access_rules',
        'tags',
        'description',
        'related_type',
        'related_id',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'version' => 'integer',
        'visible_to_client' => 'boolean',
        'access_rules' => 'array',
        'tags' => 'array',
        'uploaded_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function parentFile()
    {
        return $this->belongsTo(File::class, 'parent_file_id');
    }

    public function versions()
    {
        return $this->hasMany(File::class, 'parent_file_id');
    }
}

