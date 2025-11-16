<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PluginVersion extends Model
{
    protected $fillable = [
        'plugin_id',
        'version',
        'manifest',
        'plugin_file_path',
        'manifest_file_path',
        'file_size',
        'checksum',
        'is_latest',
        'download_count',
    ];

    protected $casts = [
        'manifest' => 'array',
        'is_latest' => 'boolean',
        'download_count' => 'integer',
        'file_size' => 'integer',
    ];

    /**
     * Get the plugin that owns this version.
     */
    public function plugin(): BelongsTo
    {
        return $this->belongsTo(Plugin::class);
    }

    /**
     * Get all additional files for this version.
     */
    public function files(): HasMany
    {
        return $this->hasMany(PluginFile::class);
    }

    /**
     * Get all dependencies for this version.
     */
    public function dependencies(): HasMany
    {
        return $this->hasMany(PluginDependency::class);
    }
}

