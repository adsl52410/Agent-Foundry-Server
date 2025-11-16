<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plugin extends Model
{
    protected $fillable = [
        'name',
        'description',
        'author',
    ];

    /**
     * Get all versions for this plugin.
     */
    public function versions(): HasMany
    {
        return $this->hasMany(PluginVersion::class);
    }

    /**
     * Get the latest version of this plugin.
     */
    public function latestVersion()
    {
        return $this->hasOne(PluginVersion::class)->where('is_latest', true);
    }
}

