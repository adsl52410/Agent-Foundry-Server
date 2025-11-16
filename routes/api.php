<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PluginController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('v1')->group(function () {
    // Public endpoints
    Route::get('/plugins', [PluginController::class, 'index'])->name('plugins.index');
    Route::get('/plugins/index', [PluginController::class, 'indexJson'])->name('plugins.indexJson');
    Route::get('/plugins/search', [PluginController::class, 'search'])->name('plugins.search');
    Route::get('/plugins/{name}', [PluginController::class, 'show'])->name('plugins.show');
    Route::get('/plugins/{name}/download', [PluginController::class, 'download'])->name('api.plugins.download');
    Route::get('/plugins/{name}/versions/{version}', [PluginController::class, 'showVersion'])->name('plugins.showVersion');
    Route::get('/plugins/{name}/versions/{version}/download', [PluginController::class, 'download'])->name('api.plugins.version.download');
    Route::get('/plugins/{name}/versions/{version}/files/{filename}', [PluginController::class, 'downloadFile'])->name('plugins.downloadFile');
    Route::post('/plugins', [PluginController::class, 'store'])->name('plugins.store');

    // Protected endpoints (require authentication)
    Route::middleware('auth:api')->group(function () {
        // Route::delete('/plugins/{name}', [PluginController::class, 'destroy']); // Optional: for future use
    });
});
