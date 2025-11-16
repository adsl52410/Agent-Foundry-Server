<?php

namespace App\Services;

use App\Models\Plugin;
use App\Models\PluginVersion;
use App\Models\PluginFile;
use App\Models\PluginDependency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Exception;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use App\Helpers\StorageHelper;

class PluginService
{
    protected $s3StorageService;

    public function __construct()
    {
        // 嘗試初始化 S3StorageService（如果使用 S3）
        try {
            $disk = Storage::disk('plugins');
            if (StorageHelper::isS3($disk, 'plugins')) {
                $this->s3StorageService = new S3StorageService('plugins');
            }
        } catch (\Exception $e) {
            // 如果初始化失敗，繼續使用傳統方式
            $this->s3StorageService = null;
        }
    }

    /**
     * Publish a new plugin or new version of existing plugin.
     *
     * @param Request $request
     * @return array
     * @throws Exception
     */
    public function publishPlugin(Request $request): array
    {
        return DB::transaction(function () use ($request) {
            // Parse manifest if provided
            $manifest = null;
            if ($request->hasFile('manifest_file')) {
                $manifestContent = file_get_contents($request->file('manifest_file')->getRealPath());
                $manifest = json_decode($manifestContent, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception('manifest.json 檔案格式錯誤：' . json_last_error_msg() . '。系統需要有效的 JSON 格式才能解析插件資訊。');
                }
            }

            // Determine plugin name and version
            $pluginName = $request->input('name') ?? $manifest['name'] ?? null;
            $version = $request->input('version') ?? $manifest['version'] ?? '0.1.0';

            // 調試日誌：記錄插件名稱來源
            \Illuminate\Support\Facades\Log::info('PluginService::publishPlugin - Plugin name determined', [
                'request_name' => $request->input('name'),
                'manifest_name' => $manifest['name'] ?? null,
                'final_pluginName' => $pluginName,
                'version' => $version,
            ]);

            if (!$pluginName) {
                throw new Exception('插件名稱是必需的。系統需要插件名稱來識別和管理插件，請在請求中提供 "name" 參數或在 manifest.json 中包含 "name" 欄位。');
            }

            // Validate version format (SemVer)
            if (!preg_match('/^\d+\.\d+\.\d+$/', $version)) {
                throw new Exception('版本號必須符合語義化版本格式（例如：1.0.0）。這是插件系統版本管理的基本要求，用於追蹤和管理不同版本的插件。');
            }

            // Get or create plugin
            $plugin = Plugin::firstOrCreate(
                ['name' => $pluginName],
                [
                    'description' => $request->input('description') ?? $manifest['description'] ?? '',
                    'author' => $request->input('author') ?? $manifest['author'] ?? 'unknown',
                ]
            );

            // Update plugin metadata if provided
            if ($request->has('description')) {
                $plugin->description = $request->input('description');
            }
            if ($request->has('author')) {
                $plugin->author = $request->input('author');
            }
            $plugin->save();

            // Check if version already exists
            if ($plugin->versions()->where('version', $version)->exists()) {
                throw new Exception("插件「{$pluginName}」的版本「{$version}」已存在。系統不允許重複的版本號，請使用不同的版本號或更新現有版本。");
            }

            // 使用 S3StorageService 或傳統方式上傳文件
            $pluginFile = $request->file('plugin_file');
            $pluginFilePath = null;
            $checksum = null;
            
            // 使用原始檔案名稱，保持上傳時的原始結構
            $pluginFileName = $pluginFile->getClientOriginalName();

            // 在方法中再次檢查是否使用 S3（不依賴構造函數的結果）
            $disk = Storage::disk('plugins');
            $isS3 = StorageHelper::isS3($disk, 'plugins');
            
            if ($isS3) {
                // 使用 S3StorageService（模仿 Python 版本的架構）
                // 如果構造函數中沒有初始化，這裡重新初始化
                if (!$this->s3StorageService) {
                    $this->s3StorageService = new S3StorageService('plugins');
                }
                
                $metadata = [
                    'plugin_name' => $pluginName,
                    'version' => $version,
                    'author' => $plugin->author,
                    'uploaded_at' => now()->toIso8601String(),
                ];

                // 使用結構化路徑：plugins/{pluginName}/{version}/{filename}
                $pluginFilePath = $this->s3StorageService->uploadPluginFile(
                    $pluginFile,
                    $pluginName,
                    $version,
                    $pluginFileName,
                    $metadata
                );

                // 計算 checksum（從上傳的文件）
                $checksum = hash_file('sha256', $pluginFile->getRealPath());
            } else {
                // 傳統方式（本地存儲）
                // 使用結構化路徑：plugins/{pluginName}/{version}/{filename}
                $pluginFilePath = $pluginFile->storeAs("plugins/{$pluginName}/{$version}", $pluginFileName, 'plugins');
                $checksum = hash_file('sha256', $disk->path($pluginFilePath));
            }

            // Store manifest.json
            $manifestFilePath = null;
            if ($request->hasFile('manifest_file')) {
                $manifestFile = $request->file('manifest_file');
                $manifestFileName = $manifestFile->getClientOriginalName();
                
                if ($isS3) {
                    // 確保 S3StorageService 已初始化
                    if (!$this->s3StorageService) {
                        $this->s3StorageService = new S3StorageService('plugins');
                    }
                    // 使用結構化路徑：plugins/{pluginName}/{version}/{filename}
                    $manifestFilePath = $this->s3StorageService->uploadPluginFile(
                        $manifestFile,
                        $pluginName,
                        $version,
                        $manifestFileName,
                        ['plugin_name' => $pluginName, 'version' => $version]
                    );
                } else {
                    // 使用結構化路徑：plugins/{pluginName}/{version}/{filename}
                    $manifestFilePath = $manifestFile->storeAs("plugins/{$pluginName}/{$version}", $manifestFileName, 'plugins');
                }
            } else {
                // Create manifest from request data
                $manifest = [
                    'name' => $pluginName,
                    'version' => $version,
                    'description' => $plugin->description,
                    'author' => $plugin->author,
                    'dependencies' => [],
                ];
                $manifestContent = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                $manifestFileName = 'manifest.json';
                
                if ($isS3) {
                    // 確保 S3StorageService 已初始化
                    if (!$this->s3StorageService) {
                        $this->s3StorageService = new S3StorageService('plugins');
                    }
                    // 使用結構化路徑：plugins/{pluginName}/{version}/{filename}
                    $manifestFilePath = $this->s3StorageService->uploadPluginFile(
                        $manifestContent,
                        $pluginName,
                        $version,
                        $manifestFileName,
                        ['plugin_name' => $pluginName, 'version' => $version]
                    );
                } else {
                    // 使用結構化路徑：plugins/{pluginName}/{version}/{filename}
                    Storage::disk('plugins')->put("plugins/{$pluginName}/{$version}/{$manifestFileName}", $manifestContent);
                    $manifestFilePath = "plugins/{$pluginName}/{$version}/{$manifestFileName}";
                }
            }

            // Store additional files
            $additionalFiles = [];
            if ($request->hasFile('additional_files')) {
                foreach ($request->file('additional_files') as $file) {
                    $fileName = $file->getClientOriginalName();
                    
                    if ($isS3) {
                        // 確保 S3StorageService 已初始化
                        if (!$this->s3StorageService) {
                            $this->s3StorageService = new S3StorageService('plugins');
                        }
                        // 使用結構化路徑：plugins/{pluginName}/{version}/{filename}
                        $filePath = $this->s3StorageService->uploadPluginFile(
                            $file,
                            $pluginName,
                            $version,
                            $fileName,
                            ['plugin_name' => $pluginName, 'version' => $version]
                        );
                    } else {
                        // 使用結構化路徑：plugins/{pluginName}/{version}/{filename}
                        $filePath = $file->storeAs("plugins/{$pluginName}/{$version}", $fileName, 'plugins');
                    }
                    
                    $additionalFiles[] = [
                        'file_name' => $fileName,
                        'file_path' => $filePath,
                        'file_size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                    ];
                }
            }

            // Create plugin version
            $pluginVersion = PluginVersion::create([
                'plugin_id' => $plugin->id,
                'version' => $version,
                'manifest' => $manifest ?? [],
                'plugin_file_path' => $pluginFilePath,
                'manifest_file_path' => $manifestFilePath,
                'file_size' => $pluginFile->getSize(),
                'checksum' => $checksum,
                'is_latest' => false, // Will be updated below
            ]);

            // Store additional files
            foreach ($additionalFiles as $fileData) {
                PluginFile::create(array_merge($fileData, [
                    'plugin_version_id' => $pluginVersion->id,
                ]));
            }

            // Update latest version flag
            $this->updateLatestVersion($plugin);

            // Process dependencies
            if (isset($manifest['dependencies']) && is_array($manifest['dependencies'])) {
                $this->processDependencies($pluginVersion, $manifest['dependencies']);
            }

            return [
                'id' => $pluginVersion->id,
                'plugin_name' => $pluginName,
                'version' => $version,
                'message' => 'Plugin published successfully',
                'download_url' => route('api.plugins.version.download', [
                    'name' => $pluginName,
                    'version' => $version
                ]),
            ];
        });
    }

    /**
     * Update the latest version flag for a plugin.
     *
     * @param Plugin $plugin
     * @return void
     */
    protected function updateLatestVersion(Plugin $plugin): void
    {
        // Reset all latest flags
        $plugin->versions()->update(['is_latest' => false]);

        // Find and set latest version
        // Get all versions and sort in PHP (compatible with all databases)
        $versions = $plugin->versions()->get();
        
        if ($versions->isEmpty()) {
            return;
        }

        // Sort versions using SemVer comparison
        $sortedVersions = $versions->sort(function ($a, $b) {
            return version_compare($b->version, $a->version);
        });

        // Get the latest version (first in sorted list)
        $latestVersion = $sortedVersions->first();

        if ($latestVersion) {
            $latestVersion->update(['is_latest' => true]);
        }
    }

    /**
     * Process and store plugin dependencies.
     *
     * @param PluginVersion $pluginVersion
     * @param array $dependencies
     * @return void
     */
    protected function processDependencies(PluginVersion $pluginVersion, array $dependencies): void
    {
        foreach ($dependencies as $depName => $constraint) {
            PluginDependency::create([
                'plugin_version_id' => $pluginVersion->id,
                'dependency_name' => $depName,
                'version_constraint' => is_string($constraint) ? $constraint : json_encode($constraint),
                'created_at' => now(),
            ]);
        }
    }
}

