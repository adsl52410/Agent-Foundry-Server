<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PluginDependency extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'plugin_version_id',
        'dependency_name',
        'version_constraint',
    ];

    /**
     * Get the plugin version that owns this dependency.
     */
    public function pluginVersion(): BelongsTo
    {
        return $this->belongsTo(PluginVersion::class);
    }
}

