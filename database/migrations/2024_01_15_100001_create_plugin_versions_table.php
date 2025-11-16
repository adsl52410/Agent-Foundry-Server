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
        Schema::create('plugin_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plugin_id')->constrained('plugins')->onDelete('cascade');
            $table->string('version', 50);
            $table->json('manifest');
            $table->string('plugin_file_path', 500);
            $table->string('manifest_file_path', 500)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('checksum', 64)->nullable();
            $table->boolean('is_latest')->default(false);
            $table->unsignedInteger('download_count')->default(0);
            $table->timestamps();
            
            $table->unique(['plugin_id', 'version'], 'unique_plugin_version');
            $table->index('plugin_id');
            $table->index('version');
            $table->index('is_latest');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plugin_versions');
    }
};

