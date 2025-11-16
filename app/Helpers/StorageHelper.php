<?php

namespace App\Helpers;

use Illuminate\Contracts\Filesystem\Filesystem;
use League\Flysystem\AwsS3v3\AwsS3Adapter;

/**
 * Storage 輔助類
 * 提供跨 Laravel 版本的適配器獲取方法
 */
class StorageHelper
{
    /**
     * 安全地獲取存儲適配器
     * 支持 Laravel 11+ 和舊版本
     *
     * @param Filesystem $disk
     * @return mixed|null
     */
    public static function getAdapter(Filesystem $disk)
    {
        try {
            // 嘗試 Laravel 11+ 的方法
            if (method_exists($disk, 'getAdapter')) {
                return $disk->getAdapter();
            }
            
            // 嘗試舊版本的方法
            if (method_exists($disk, 'getDriver')) {
                $driver = $disk->getDriver();
                if (method_exists($driver, 'getAdapter')) {
                    return $driver->getAdapter();
                }
            }
            
            // 嘗試使用反射
            $reflection = new \ReflectionClass($disk);
            if ($reflection->hasProperty('adapter')) {
                $property = $reflection->getProperty('adapter');
                $property->setAccessible(true);
                return $property->getValue($disk);
            }
            
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * 檢查是否使用 S3 存儲
     *
     * @param Filesystem $disk
     * @param string|null $diskName 可選的 disk 名稱，用於檢查配置
     * @return bool
     */
    public static function isS3(Filesystem $disk, ?string $diskName = null): bool
    {
        // 首先嘗試通過適配器檢測
        $adapter = self::getAdapter($disk);
        if ($adapter instanceof AwsS3Adapter) {
            return true;
        }
        
        // 如果適配器檢測失敗，檢查配置中的 driver
        if ($diskName) {
            $config = config("filesystems.disks.{$diskName}");
            if (isset($config['driver']) && $config['driver'] === 's3') {
                return true;
            }
        }
        
        // 最後檢查環境變數（對於 plugins disk）
        if ($diskName === 'plugins') {
            return env('PLUGIN_STORAGE_DISK', 'local') === 's3';
        }
        
        return false;
    }
}

