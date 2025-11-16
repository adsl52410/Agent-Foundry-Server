<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plugin;
use App\Models\PluginVersion;
use App\Services\PluginService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use ZipArchive;
use OpenApi\Attributes as OA;
use App\Helpers\StorageHelper;

#[OA\Info(
    version: '1.0.0',
    title: 'Plugin Registry API',
    description: 'Agent-Foundry Plugin Registry Server API 文檔'
)]
#[OA\Server(
    url: 'http://localhost:8089/api/v1',
    description: '本地開發伺服器'
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    description: 'Enter JWT token in format (Bearer <token>)'
)]
class PluginController extends Controller
{
    protected $pluginService;

    public function __construct(PluginService $pluginService)
    {
        $this->pluginService = $pluginService;
    }

    #[OA\Get(
        path: '/plugins',
        tags: ['Plugins'],
        summary: 'List all plugins',
        description: 'Get a paginated list of all available plugins with optional filtering',
        parameters: [
            new OA\Parameter(
                name: 'search',
                in: 'query',
                description: 'Search by plugin name or description',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'author',
                in: 'query',
                description: 'Filter by author',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                description: 'Page number for pagination',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1)
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                description: 'Items per page (max: 100)',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 20, maximum: 100)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
                        new OA\Property(property: 'meta', type: 'object'),
                    ]
                )
            ),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = Plugin::with('latestVersion');

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('author')) {
            $query->where('author', $request->get('author'));
        }

        $perPage = min($request->get('per_page', 20), 100);
        $plugins = $query->paginate($perPage);

        $data = $plugins->map(function ($plugin) {
            return [
                'id' => $plugin->id,
                'name' => $plugin->name,
                'description' => $plugin->description,
                'author' => $plugin->author,
                'latest_version' => $plugin->latestVersion->version ?? null,
                'versions_count' => $plugin->versions()->count(),
                'created_at' => $plugin->created_at?->toIso8601String(),
                'updated_at' => $plugin->updated_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $plugins->currentPage(),
                'per_page' => $plugins->perPage(),
                'total' => $plugins->total(),
                'last_page' => $plugins->lastPage(),
            ]
        ]);
    }

    #[OA\Get(
        path: '/plugins/{name}',
        tags: ['Plugins'],
        summary: 'Get plugin details',
        description: 'Get detailed information about a specific plugin including all versions',
        parameters: [
            new OA\Parameter(
                name: 'name',
                in: 'path',
                required: true,
                description: 'Plugin name',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Success'),
            new OA\Response(response: 404, description: 'Plugin not found'),
        ]
    )]
    public function show(string $name): JsonResponse
    {
        $plugin = Plugin::where('name', $name)
            ->with('versions')
            ->firstOrFail();

        $versions = $plugin->versions->map(function ($version) {
            return [
                'version' => $version->version,
                'is_latest' => $version->is_latest,
                'created_at' => $version->created_at?->toIso8601String(),
                'download_count' => $version->download_count,
            ];
        });

        return response()->json([
            'data' => [
                'id' => $plugin->id,
                'name' => $plugin->name,
                'description' => $plugin->description,
                'author' => $plugin->author,
                'versions' => $versions,
                'latest_version' => $plugin->latestVersion->version ?? null,
                'created_at' => $plugin->created_at?->toIso8601String(),
                'updated_at' => $plugin->updated_at?->toIso8601String(),
            ]
        ]);
    }

    #[OA\Get(
        path: '/plugins/{name}/versions/{version}',
        tags: ['Plugins'],
        summary: 'Get plugin version details',
        description: 'Get detailed information about a specific plugin version',
        parameters: [
            new OA\Parameter(
                name: 'name',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'version',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Success'),
            new OA\Response(response: 404, description: 'Plugin version not found'),
        ]
    )]
    public function showVersion(string $name, string $version): JsonResponse
    {
        $plugin = Plugin::where('name', $name)->firstOrFail();
        $pluginVersion = $plugin->versions()
            ->where('version', $version)
            ->with('dependencies')
            ->firstOrFail();

        $dependencies = $pluginVersion->dependencies->map(function ($dep) {
            return [
                'name' => $dep->dependency_name,
                'version_constraint' => $dep->version_constraint,
            ];
        });

        return response()->json([
            'data' => [
                'id' => $pluginVersion->id,
                'plugin_name' => $plugin->name,
                'version' => $pluginVersion->version,
                'manifest' => $pluginVersion->manifest,
                'dependencies' => $dependencies,
                'file_size' => $pluginVersion->file_size,
                'checksum' => $pluginVersion->checksum,
                'is_latest' => $pluginVersion->is_latest,
                'download_count' => $pluginVersion->download_count,
                'download_url' => route('api.plugins.version.download', [
                    'name' => $plugin->name,
                    'version' => $pluginVersion->version
                ]),
                'created_at' => $pluginVersion->created_at?->toIso8601String(),
            ]
        ]);
    }

    #[OA\Post(
        path: '/plugins',
        tags: ['Plugins'],
        summary: 'Upload/Publish plugin',
        description: 'Upload a new plugin or new version of existing plugin',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: 'plugin_file', type: 'string', format: 'binary', description: 'The plugin.py file'),
                        new OA\Property(property: 'manifest_file', type: 'string', format: 'binary', description: 'The manifest.json file (optional)'),
                        new OA\Property(property: 'additional_files', type: 'array', items: new OA\Items(type: 'string', format: 'binary'), description: 'Additional files'),
                        new OA\Property(property: 'name', type: 'string', description: 'Plugin name (required if manifest not provided)'),
                        new OA\Property(property: 'version', type: 'string', description: 'Version override (defaults to manifest version)'),
                        new OA\Property(property: 'description', type: 'string', description: 'Description override'),
                        new OA\Property(property: 'author', type: 'string', description: 'Author override'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Plugin published successfully'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        // 只檢查插件運作所需的基本規則
        $validator = Validator::make($request->all(), [
            'plugin_file' => [
                'required',
                'file',
                'max:10240', // 檔案大小限制：防止過大檔案影響系統運作
                function ($attribute, $value, $fail) {
                    if ($value) {
                        $extension = strtolower($value->getClientOriginalExtension());
                        // 插件必須是 Python 檔案才能正常運作
                        if ($extension !== 'py') {
                            $fail('插件檔案必須是 .py 格式的 Python 檔案，系統才能正常執行。');
                        }
                        
                        // 檢查檔案是否為有效的 Python 檔案（基本檢查）
                        try {
                            $content = file_get_contents($value->getRealPath());
                            // 檢查是否包含基本的 Python 語法特徵
                            if (empty(trim($content))) {
                                $fail('插件檔案不能為空，必須包含有效的 Python 程式碼。');
                            }
                        } catch (\Exception $e) {
                            $fail('無法讀取插件檔案內容，請確認檔案完整且可讀取。');
                        }
                    }
                },
            ],
            'manifest_file' => [
                'nullable',
                'file',
                'max:1024', // manifest 檔案大小限制
                function ($attribute, $value, $fail) {
                    if ($value) {
                        // 檢查是否為有效的 JSON 檔案
                        try {
                            $content = file_get_contents($value->getRealPath());
                            $manifest = json_decode($content, true);
                            
                            if (json_last_error() !== JSON_ERROR_NONE) {
                                $fail('manifest.json 檔案格式錯誤：' . json_last_error_msg() . '。請提供有效的 JSON 格式檔案。');
                            }
                            
                            // 檢查必要的欄位（如果提供了 manifest，必須包含基本資訊）
                            if (isset($manifest) && is_array($manifest)) {
                                if (empty($manifest['name'])) {
                                    $fail('manifest.json 必須包含 "name" 欄位，用於識別插件。');
                                }
                                if (isset($manifest['version']) && !preg_match('/^\d+\.\d+\.\d+$/', $manifest['version'])) {
                                    $fail('manifest.json 中的 "version" 必須符合語義化版本格式（例如：1.0.0），用於版本管理。');
                                }
                            }
                        } catch (\Exception $e) {
                            $fail('無法讀取 manifest.json 檔案：' . $e->getMessage());
                        }
                    }
                },
            ],
            'additional_files.*' => 'nullable|file|max:5120', // 額外檔案大小限制
            'name' => 'required_without:manifest_file|string', // 如果沒有 manifest，必須提供插件名稱
            'version' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    if ($value && !preg_match('/^\d+\.\d+\.\d+$/', $value)) {
                        $fail('版本號必須符合語義化版本格式（例如：1.0.0），這是插件系統版本管理的基本要求。');
                    }
                },
            ],
            'description' => 'nullable|string', // 描述不影響運作，不設長度限制
            'author' => 'nullable|string', // 作者不影響運作，不設長度限制
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => [
                    'code' => 'PLUGIN_VALIDATION_ERROR',
                    'message' => '插件不符合運作規則，無法上傳',
                    'reason' => '以下問題導致插件無法正常運作：',
                    'details' => $validator->errors()
                ]
            ], 422);
        }

        try {
            $result = $this->pluginService->publishPlugin($request);
            return response()->json(['data' => $result], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'PLUGIN_OPERATION_ERROR',
                    'message' => '插件不符合運作規則，無法上傳',
                    'reason' => $e->getMessage()
                ]
            ], 400);
        }
    }

    #[OA\Get(
        path: '/plugins/{name}/download',
        tags: ['Plugins'],
        summary: 'Download latest version',
        description: 'Download the latest version of a plugin',
        parameters: [
            new OA\Parameter(
                name: 'name',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'format',
                in: 'query',
                description: 'Response format: zip or json',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['zip', 'json'], default: 'zip')
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Success'),
            new OA\Response(response: 404, description: 'Plugin not found'),
        ]
    )]
    #[OA\Get(
        path: '/plugins/{name}/versions/{version}/download',
        tags: ['Plugins'],
        summary: 'Download plugin version',
        description: 'Download plugin files as a ZIP archive or JSON with file URLs',
        parameters: [
            new OA\Parameter(
                name: 'name',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'version',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'format',
                in: 'query',
                description: 'Response format: zip or json',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['zip', 'json'], default: 'zip')
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Success'),
            new OA\Response(response: 404, description: 'Plugin version not found'),
        ]
    )]
    public function download(string $name, ?string $version = null, Request $request)
    {
        $plugin = Plugin::where('name', $name)->firstOrFail();

        if ($version === null) {
            $pluginVersion = $plugin->latestVersion;
            if (!$pluginVersion) {
                return response()->json([
                    'error' => [
                        'code' => 'NOT_FOUND',
                        'message' => 'No versions found for this plugin'
                    ]
                ], 404);
            }
        } else {
            $pluginVersion = $plugin->versions()
                ->where('version', $version)
                ->firstOrFail();
        }

        // Increment download count
        $pluginVersion->increment('download_count');

        $format = $request->get('format', 'zip');

        if ($format === 'json') {
            return $this->downloadAsJson($pluginVersion);
        }

        return $this->downloadAsZip($pluginVersion);
    }

    #[OA\Get(
        path: '/plugins/{name}/versions/{version}/files/{filename}',
        tags: ['Plugins'],
        summary: 'Download specific file',
        description: 'Download a specific file from a plugin version',
        parameters: [
            new OA\Parameter(
                name: 'name',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'version',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'filename',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Success'),
            new OA\Response(response: 404, description: 'File not found'),
        ]
    )]
    public function downloadFile(string $name, string $version, string $filename)
    {
        $plugin = Plugin::where('name', $name)->firstOrFail();
        $pluginVersion = $plugin->versions()
            ->where('version', $version)
            ->firstOrFail();

        $filePath = null;

        if ($filename === 'plugin.py') {
            $filePath = $pluginVersion->plugin_file_path;
        } elseif ($filename === 'manifest.json') {
            $filePath = $pluginVersion->manifest_file_path;
        } else {
            $file = $pluginVersion->files()->where('file_name', $filename)->first();
            if ($file) {
                $filePath = $file->file_path;
            }
        }

        $disk = Storage::disk('plugins');
        
        if (!$filePath || !$disk->exists($filePath)) {
            return response()->json([
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'File not found'
                ]
            ], 404);
        }

        // 如果是 S3 且有 CDN URL，重定向到 CDN；否則返回文件內容或下載
        $isS3 = StorageHelper::isS3($disk);
        
        if ($isS3) {
            $cdnUrl = $this->getCdnUrl($filePath);
            
            if ($cdnUrl) {
                // 重定向到 CDN URL
                return redirect($cdnUrl, 302);
            } else {
                // 如果沒有 CDN URL，直接返回文件內容
                $content = $disk->get($filePath);
                $mimeType = $disk->mimeType($filePath) ?? 'application/octet-stream';
                
                return response($content, 200)
                    ->header('Content-Type', $mimeType)
                    ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
            }
        } else {
            return $disk->download($filePath, $filename);
        }
    }

    #[OA\Get(
        path: '/plugins/search',
        tags: ['Plugins'],
        summary: 'Search plugins',
        description: 'Advanced search with multiple criteria',
        parameters: [
            new OA\Parameter(
                name: 'q',
                in: 'query',
                required: true,
                description: 'Search query',
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'field',
                in: 'query',
                description: 'Search field: name, description, author, or all',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['name', 'description', 'author', 'all'], default: 'all')
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1)
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 20, maximum: 100)
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Success'),
        ]
    )]
    public function search(Request $request): JsonResponse
    {
        $query = $request->get('q');
        $field = $request->get('field', 'all');

        if (!$query) {
            return response()->json([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Search query (q) is required'
                ]
            ], 422);
        }

        $pluginQuery = Plugin::with('latestVersion');

        switch ($field) {
            case 'name':
                $pluginQuery->where('name', 'like', "%{$query}%");
                break;
            case 'description':
                $pluginQuery->where('description', 'like', "%{$query}%");
                break;
            case 'author':
                $pluginQuery->where('author', 'like', "%{$query}%");
                break;
            default:
                $pluginQuery->where(function($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhere('description', 'like', "%{$query}%")
                      ->orWhere('author', 'like', "%{$query}%");
                });
        }

        $perPage = min($request->get('per_page', 20), 100);
        $plugins = $pluginQuery->paginate($perPage);

        $data = $plugins->map(function ($plugin) {
            return [
                'id' => $plugin->id,
                'name' => $plugin->name,
                'description' => $plugin->description,
                'author' => $plugin->author,
                'latest_version' => $plugin->latestVersion->version ?? null,
                'versions_count' => $plugin->versions()->count(),
                'created_at' => $plugin->created_at?->toIso8601String(),
                'updated_at' => $plugin->updated_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $plugins->currentPage(),
                'per_page' => $plugins->perPage(),
                'total' => $plugins->total(),
                'last_page' => $plugins->lastPage(),
            ]
        ]);
    }

    #[OA\Get(
        path: '/plugins/index',
        tags: ['Plugins'],
        summary: 'Get plugin index',
        description: 'Get a simplified index similar to index.json format',
        responses: [
            new OA\Response(response: 200, description: 'Success'),
        ]
    )]
    public function indexJson(): JsonResponse
    {
        $plugins = Plugin::with('versions')->get();

        $index = [];
        foreach ($plugins as $plugin) {
            // Get all versions and sort in PHP (compatible with all databases)
            $versionModels = $plugin->versions;
            $versions = $versionModels->pluck('version')->toArray();
            
            // Sort versions using SemVer comparison
            usort($versions, function ($a, $b) {
                return version_compare($b, $a); // Descending order
            });

            $index[$plugin->name] = [
                'versions' => $versions,
                'latest' => $plugin->latestVersion->version ?? ($versions[0] ?? null)
            ];
        }

        return response()->json(['data' => $index]);
    }

    /**
     * Generate CDN URL for a file path.
     *
     * @param string $filePath
     * @return string|null
     */
    protected function getCdnUrl(string $filePath): ?string
    {
        $cdnUrl = env('CDN_URL');
        
        if (!$cdnUrl) {
            return null;
        }
        
        // 確保 CDN URL 以 / 結尾
        $cdnUrl = rtrim($cdnUrl, '/') . '/';
        
        // 移除路徑中的前導斜線（如果有的話）
        $filePath = ltrim($filePath, '/');
        
        return $cdnUrl . $filePath;
    }

    /**
     * Download plugin version as ZIP file.
     */
    protected function downloadAsZip(PluginVersion $pluginVersion)
    {
        $zipPath = storage_path('app/temp/' . uniqid() . '.zip');
        $tempDir = dirname($zipPath);
        
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return response()->json([
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to create ZIP file'
                ]
            ], 500);
        }

        $disk = Storage::disk('plugins');
        $isS3 = StorageHelper::isS3($disk);

        // Add plugin.py
        if ($disk->exists($pluginVersion->plugin_file_path)) {
            if ($isS3) {
                $content = $disk->get($pluginVersion->plugin_file_path);
                $zip->addFromString('plugin.py', $content);
            } else {
                $pluginPath = $disk->path($pluginVersion->plugin_file_path);
                if (file_exists($pluginPath)) {
                    $zip->addFile($pluginPath, 'plugin.py');
                }
            }
        }

        // Add manifest.json if exists
        if ($pluginVersion->manifest_file_path && $disk->exists($pluginVersion->manifest_file_path)) {
            if ($isS3) {
                $content = $disk->get($pluginVersion->manifest_file_path);
                $zip->addFromString('manifest.json', $content);
            } else {
                $manifestPath = $disk->path($pluginVersion->manifest_file_path);
                if (file_exists($manifestPath)) {
                    $zip->addFile($manifestPath, 'manifest.json');
                }
            }
        }

        // Add additional files
        foreach ($pluginVersion->files as $file) {
            if ($disk->exists($file->file_path)) {
                if ($isS3) {
                    $content = $disk->get($file->file_path);
                    $zip->addFromString($file->file_name, $content);
                } else {
                    $filePath = $disk->path($file->file_path);
                    if (file_exists($filePath)) {
                        $zip->addFile($filePath, $file->file_name);
                    }
                }
            }
        }

        $zip->close();

        return response()->download($zipPath, 
            "{$pluginVersion->plugin->name}-{$pluginVersion->version}.zip")
            ->deleteFileAfterSend(true);
    }

    /**
     * Download plugin version as JSON with file URLs.
     */
    protected function downloadAsJson(PluginVersion $pluginVersion): JsonResponse
    {
        $files = [];
        $disk = Storage::disk('plugins');
        $isS3 = StorageHelper::isS3($disk);

        // Add plugin.py
        if ($disk->exists($pluginVersion->plugin_file_path)) {
            if ($isS3) {
                $content = $disk->get($pluginVersion->plugin_file_path);
                $size = strlen($content);
                $checksum = hash('sha256', $content);
            } else {
                $pluginPath = $disk->path($pluginVersion->plugin_file_path);
                $size = filesize($pluginPath);
                $checksum = hash_file('sha256', $pluginPath);
            }
            
            // 如果使用 S3 且有 CDN URL，使用 CDN URL；否則使用 API 路由
            $fileUrl = null;
            if ($isS3) {
                $cdnUrl = $this->getCdnUrl($pluginVersion->plugin_file_path);
                if ($cdnUrl) {
                    $fileUrl = $cdnUrl;
                }
            }
            
            if (!$fileUrl) {
                $fileUrl = route('plugins.downloadFile', [
                    'name' => $pluginVersion->plugin->name,
                    'version' => $pluginVersion->version,
                    'filename' => 'plugin.py'
                ]);
            }
            
            $files[] = [
                'name' => 'plugin.py',
                'url' => $fileUrl,
                'size' => $size,
                'checksum' => $checksum,
            ];
        }

        // Add manifest.json if exists
        if ($pluginVersion->manifest_file_path && $disk->exists($pluginVersion->manifest_file_path)) {
            if ($isS3) {
                $content = $disk->get($pluginVersion->manifest_file_path);
                $size = strlen($content);
                $checksum = hash('sha256', $content);
            } else {
                $manifestPath = $disk->path($pluginVersion->manifest_file_path);
                $size = filesize($manifestPath);
                $checksum = hash_file('sha256', $manifestPath);
            }
            
            // 如果使用 S3 且有 CDN URL，使用 CDN URL；否則使用 API 路由
            $fileUrl = null;
            if ($isS3) {
                $cdnUrl = $this->getCdnUrl($pluginVersion->manifest_file_path);
                if ($cdnUrl) {
                    $fileUrl = $cdnUrl;
                }
            }
            
            if (!$fileUrl) {
                $fileUrl = route('plugins.downloadFile', [
                    'name' => $pluginVersion->plugin->name,
                    'version' => $pluginVersion->version,
                    'filename' => 'manifest.json'
                ]);
            }
            
            $files[] = [
                'name' => 'manifest.json',
                'url' => $fileUrl,
                'size' => $size,
                'checksum' => $checksum,
            ];
        }

        // Add additional files
        foreach ($pluginVersion->files as $file) {
            if ($disk->exists($file->file_path)) {
                if ($isS3) {
                    $content = $disk->get($file->file_path);
                    $checksum = hash('sha256', $content);
                    $size = strlen($content);
                } else {
                    $filePath = $disk->path($file->file_path);
                    $checksum = hash_file('sha256', $filePath);
                    $size = $file->file_size ?? filesize($filePath);
                }
                
                // 如果使用 S3 且有 CDN URL，使用 CDN URL；否則使用 API 路由
                $fileUrl = null;
                if ($isS3) {
                    $cdnUrl = $this->getCdnUrl($file->file_path);
                    if ($cdnUrl) {
                        $fileUrl = $cdnUrl;
                    }
                }
                
                if (!$fileUrl) {
                    $fileUrl = route('plugins.downloadFile', [
                        'name' => $pluginVersion->plugin->name,
                        'version' => $pluginVersion->version,
                        'filename' => $file->file_name
                    ]);
                }
                
                $files[] = [
                    'name' => $file->file_name,
                    'url' => $fileUrl,
                    'size' => $size,
                    'checksum' => $checksum,
                ];
            }
        }

        return response()->json([
            'data' => [
                'plugin_name' => $pluginVersion->plugin->name,
                'version' => $pluginVersion->version,
                'files' => $files,
                'zip_url' => route('api.plugins.version.download', [
                    'name' => $pluginVersion->plugin->name,
                    'version' => $pluginVersion->version
                ]) . '?format=zip'
            ]
        ]);
    }
}

