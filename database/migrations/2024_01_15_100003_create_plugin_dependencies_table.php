<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('plugin_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plugin_version_id')->constrained('plugin_versions')->onDelete('cascade');
            $table->string('dependency_name', 255);
            $table->string('version_constraint', 50);
            $table->timestamp('created_at')->nullable();
            
            $table->index('plugin_version_id');
            $table->index('dependency_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plugin_dependencies');
    }
};

