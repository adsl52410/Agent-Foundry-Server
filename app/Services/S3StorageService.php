<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use App\Helpers\StorageHelper;
use Aws\S3\S3Client;

/**
 * S3 儲存服務實作
 * 用於 Plugin Registry Server 的檔案儲存
 * 
 * 參考: scripts/s3-storage-service.py
 */
class S3StorageService
{
    protected $disk;
    protected $bucketName;
    protected $region;
    protected $rootPath;
    protected $s3Client;

    /**
     * 初始化 S3 儲存服務
     *
     * @param string|null $diskName 存儲 disk 名稱，默認為 'plugins'
     */
    public function __construct(?string $diskName = 'plugins')
    {
        $this->disk = Storage::disk($diskName);
        
        // 檢查是否使用 S3（使用改進後的檢測方法，支持配置檢查）
        if (!StorageHelper::isS3($this->disk, $diskName)) {
            throw new Exception('S3StorageService requires S3 disk configuration');
        }

        // 獲取配置
        $config = config('filesystems.disks.' . $diskName);
        $this->bucketName = $config['bucket'] ?? null;
        $this->region = $config['region'] ?? null;
        $this->rootPath = $config['root'] ?? '';
        
        // 驗證必要的配置
        if (!$this->bucketName) {
            throw new Exception('S3StorageService requires AWS_BUCKET configuration');
        }
        
        // 嘗試獲取或創建 S3 客戶端
        $this->s3Client = $this->getS3Client($diskName, $config);
    }
    
    /**
     * 獲取 S3 客戶端
     * 優先從適配器獲取，如果失敗則從配置創建
     *
     * @param string $diskName
     * @param array $config
     * @return S3Client
     */
    protected function getS3Client(string $diskName, array $config): S3Client
    {
        // 首先嘗試從適配器獲取客戶端
        $adapter = StorageHelper::getAdapter($this->disk);
        if ($adapter instanceof AwsS3Adapter) {
            try {
                return $adapter->getClient();
            } catch (\Exception $e) {
                // 如果獲取失敗，繼續使用配置創建
            }
        }
        
        // 從配置創建 S3 客戶端
        $clientConfig = [
            'version' => 'latest',
            'region' => $this->region ?? 'ap-northeast-1',
        ];
        
        // 添加憑證（如果提供）
        if (!empty($config['key']) && !empty($config['secret'])) {
            $clientConfig['credentials'] = [
                'key' => $config['key'],
                'secret' => $config['secret'],
            ];
        }
        
        // 添加端點（如果提供）
        if (!empty($config['endpoint'])) {
            $clientConfig['endpoint'] = $config['endpoint'];
        }
        
        // 添加路徑樣式端點配置
        if (isset($config['use_path_style_endpoint'])) {
            $clientConfig['use_path_style_endpoint'] = $config['use_path_style_endpoint'];
        }
        
        return new S3Client($clientConfig);
    }

    /**
     * 上傳插件檔案到 S3
     *
     * @param mixed $fileContent 檔案內容（可以是文件資源、字符串或 UploadedFile）
     * @param string $pluginName 插件名稱
     * @param string $version 版本號
     * @param string $filename 檔案名稱
     * @param array|null $metadata 額外的元資料
     * @return string S3 key (檔案路徑)
     * @throws Exception
     */
    public function uploadPluginFile($fileContent, string $pluginName, string $version, string $filename, ?array $metadata = null): string
    {
        // 調試日誌：記錄實際傳入的參數
        Log::info('S3StorageService::uploadPluginFile called', [
            'pluginName' => $pluginName,
            'version' => $version,
            'filename' => $filename,
            'rootPath' => $this->rootPath,
        ]);
        
        $key = $this->buildKey($pluginName, $version, $filename);
        
        Log::info('S3StorageService::buildKey result', [
            'key' => $key,
            'fullKey' => $this->rootPath ? rtrim($this->rootPath, '/') . '/' . ltrim($key, '/') : $key,
        ]);

        try {
            // 處理不同的文件類型，獲取文件內容
            $content = null;
            if (is_resource($fileContent)) {
                // 文件資源
                $content = stream_get_contents($fileContent);
            } elseif (is_string($fileContent) && file_exists($fileContent)) {
                // 文件路徑
                $content = file_get_contents($fileContent);
            } elseif (is_object($fileContent) && method_exists($fileContent, 'getContent')) {
                // UploadedFile with getContent()
                $content = $fileContent->getContent();
            } elseif (is_object($fileContent) && method_exists($fileContent, 'getRealPath')) {
                // UploadedFile with getRealPath()
                $content = file_get_contents($fileContent->getRealPath());
            } else {
                // 字符串內容
                $content = (string) $fileContent;
            }

            // 使用 S3 客戶端直接上傳以支持元數據和加密
            // 構建完整的 S3 key，處理 rootPath 和 key 的拼接
            // 如果 rootPath 存在，確保正確拼接（避免重複的斜線）
            $fullKey = $this->rootPath 
                ? rtrim($this->rootPath, '/') . '/' . ltrim($key, '/')
                : $key;
            
            $params = [
                'Bucket' => $this->bucketName,
                'Key' => $fullKey,
                'Body' => $content,
                'ContentType' => $this->getContentType($filename),
                'ServerSideEncryption' => 'AES256',
            ];

            // 添加元資料
            if ($metadata) {
                $params['Metadata'] = array_map('strval', $metadata);
            }

            $this->s3Client->putObject($params);

            return $key;
        } catch (\Exception $e) {
            Log::error('Failed to upload file to S3', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            throw new Exception("Failed to upload file to S3: " . $e->getMessage());
        }
    }

    /**
     * 從 S3 下載插件檔案
     *
     * @param string $pluginName 插件名稱
     * @param string $version 版本號
     * @param string $filename 檔案名稱
     * @return string 檔案內容
     * @throws Exception
     */
    public function downloadPluginFile(string $pluginName, string $version, string $filename): string
    {
        $key = $this->buildKey($pluginName, $version, $filename);

        try {
            if (!$this->disk->exists($key)) {
                throw new Exception("File not found: {$key}");
            }

            return $this->disk->get($key);
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'not found') !== false) {
                throw new Exception("File not found: {$key}");
            }
            throw new Exception("Failed to download file from S3: " . $e->getMessage());
        }
    }

    /**
     * 從 S3 刪除插件檔案
     *
     * @param string $pluginName 插件名稱
     * @param string $version 版本號
     * @param string $filename 檔案名稱
     * @return bool 是否成功刪除
     * @throws Exception
     */
    public function deletePluginFile(string $pluginName, string $version, string $filename): bool
    {
        $key = $this->buildKey($pluginName, $version, $filename);

        try {
            return $this->disk->delete($key);
        } catch (\Exception $e) {
            throw new Exception("Failed to delete file from S3: " . $e->getMessage());
        }
    }

    /**
     * 刪除整個插件版本的所有檔案
     *
     * @param string $pluginName 插件名稱
     * @param string $version 版本號
     * @return bool 是否成功刪除
     * @throws Exception
     */
    public function deletePluginVersion(string $pluginName, string $version): bool
    {
        $prefix = $this->buildKey($pluginName, $version, '');

        try {
            // 列出所有檔案
            $files = $this->disk->files($prefix, true);

            // 刪除所有檔案
            if (!empty($files)) {
                return $this->disk->delete($files);
            }

            return true;
        } catch (\Exception $e) {
            throw new Exception("Failed to delete plugin version: " . $e->getMessage());
        }
    }

    /**
     * 生成預簽名下載 URL
     *
     * @param string $pluginName 插件名稱
     * @param string $version 版本號
     * @param string $filename 檔案名稱
     * @param int $expiresIn URL 過期時間（秒），默認 3600
     * @return string 預簽名 URL
     * @throws Exception
     */
    public function getPresignedDownloadUrl(string $pluginName, string $version, string $filename, int $expiresIn = 3600): string
    {
        $key = $this->buildKey($pluginName, $version, $filename);

        try {
            return $this->disk->temporaryUrl($key, now()->addSeconds($expiresIn));
        } catch (\Exception $e) {
            throw new Exception("Failed to generate presigned URL: " . $e->getMessage());
        }
    }

    /**
     * 生成預簽名上傳 URL（用於直接上傳）
     *
     * @param string $pluginName 插件名稱
     * @param string $version 版本號
     * @param string $filename 檔案名稱
     * @param int $expiresIn URL 過期時間（秒），默認 3600
     * @return string 預簽名 URL
     * @throws Exception
     */
    public function getPresignedUploadUrl(string $pluginName, string $version, string $filename, int $expiresIn = 3600): string
    {
        $key = $this->buildKey($pluginName, $version, $filename);
        $contentType = $this->getContentType($filename);

        try {
            $fullKey = $this->rootPath 
                ? rtrim($this->rootPath, '/') . '/' . ltrim($key, '/')
                : $key;
            
            $command = $this->s3Client->getCommand('PutObject', [
                'Bucket' => $this->bucketName,
                'Key' => $fullKey,
                'ContentType' => $contentType,
                'ServerSideEncryption' => 'AES256',
            ]);

            $request = $this->s3Client->createPresignedRequest($command, "+{$expiresIn} seconds");
            return (string) $request->getUri();
        } catch (\Exception $e) {
            throw new Exception("Failed to generate presigned upload URL: " . $e->getMessage());
        }
    }

    /**
     * 檢查檔案是否存在
     *
     * @param string $pluginName 插件名稱
     * @param string $version 版本號
     * @param string $filename 檔案名稱
     * @return bool 檔案是否存在
     */
    public function fileExists(string $pluginName, string $version, string $filename): bool
    {
        $key = $this->buildKey($pluginName, $version, $filename);
        return $this->disk->exists($key);
    }

    /**
     * 獲取檔案元資料
     *
     * @param string $pluginName 插件名稱
     * @param string $version 版本號
     * @param string $filename 檔案名稱
     * @return array 檔案元資料
     * @throws Exception
     */
    public function getFileMetadata(string $pluginName, string $version, string $filename): array
    {
        $key = $this->buildKey($pluginName, $version, $filename);

        try {
            if (!$this->disk->exists($key)) {
                throw new Exception("File not found: {$key}");
            }

            $size = strlen($this->disk->get($key));
            $mimeType = $this->disk->mimeType($key) ?? $this->getContentType($filename);
            $lastModified = $this->disk->lastModified($key);

            return [
                'size' => $size,
                'content_type' => $mimeType,
                'last_modified' => date('c', $lastModified),
                'etag' => md5($this->disk->get($key)),
                'metadata' => [],
            ];
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'not found') !== false) {
                throw new Exception("File not found: {$key}");
            }
            throw new Exception("Failed to get file metadata: " . $e->getMessage());
        }
    }

    /**
     * 列出插件檔案
     *
     * @param string $pluginName 插件名稱
     * @param string|null $version 版本號（可選，如果提供則只列出該版本的檔案）
     * @return array 檔案列表
     * @throws Exception
     */
    public function listPluginFiles(string $pluginName, ?string $version = null): array
    {
        $prefix = $version 
            ? $this->buildKey($pluginName, $version, '')
            : "plugins/{$pluginName}/";

        try {
            $files = $this->disk->files($prefix, true);
            $result = [];

            foreach ($files as $file) {
                $result[] = [
                    'key' => $file,
                    'size' => strlen($this->disk->get($file)),
                    'last_modified' => date('c', $this->disk->lastModified($file)),
                ];
            }

            return $result;
        } catch (\Exception $e) {
            throw new Exception("Failed to list files: " . $e->getMessage());
        }
    }

    /**
     * 計算檔案雜湊值
     *
     * @param string $pluginName 插件名稱
     * @param string $version 版本號
     * @param string $filename 檔案名稱
     * @param string $algorithm 雜湊演算法（sha256, md5），默認 sha256
     * @return string 雜湊值（hex 字串）
     * @throws Exception
     */
    public function calculateFileHash(string $pluginName, string $version, string $filename, string $algorithm = 'sha256'): string
    {
        $fileContent = $this->downloadPluginFile($pluginName, $version, $filename);

        switch ($algorithm) {
            case 'sha256':
                return hash('sha256', $fileContent);
            case 'md5':
                return md5($fileContent);
            default:
                throw new Exception("Unsupported algorithm: {$algorithm}");
        }
    }

    /**
     * 構建 S3 key（檔案路徑）
     *
     * @param string $pluginName 插件名稱
     * @param string $version 版本號
     * @param string $filename 檔案名稱
     * @return string S3 key
     */
    protected function buildKey(string $pluginName, string $version, string $filename): string
    {
        $key = "plugins/{$pluginName}/{$version}";
        if ($filename) {
            $key .= "/{$filename}";
        }
        return $key;
    }

    /**
     * 根據副檔名判斷 Content-Type
     *
     * @param string $filename 檔案名稱
     * @return string Content-Type
     */
    protected function getContentType(string $filename): string
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $contentTypes = [
            'py' => 'text/x-python',
            'json' => 'application/json',
            'txt' => 'text/plain',
            'md' => 'text/markdown',
            'zip' => 'application/zip',
            'tar' => 'application/x-tar',
            'gz' => 'application/gzip',
            'sig' => 'application/pgp-signature',
        ];

        return $contentTypes[$ext] ?? 'application/octet-stream';
    }

    /**
     * 獲取 CDN URL（如果配置了）
     *
     * @param string $pluginName 插件名稱
     * @param string $version 版本號
     * @param string $filename 檔案名稱
     * @return string|null CDN URL 或 null
     */
    public function getCdnUrl(string $pluginName, string $version, string $filename): ?string
    {
        $cdnUrl = env('CDN_URL');
        
        if (!$cdnUrl) {
            return null;
        }

        $key = $this->buildKey($pluginName, $version, $filename);
        $cdnUrl = rtrim($cdnUrl, '/') . '/';
        $key = ltrim($key, '/');

        return $cdnUrl . $key;
    }
}

