# Plugin Registry Server - Laravel API

## 📋 專案概述

這是使用 Laravel 框架開發的 Plugin Registry Server API，提供插件管理、版本控制、用戶認證等功能。

## 🚀 快速開始

### 1. 安裝依賴

```bash
composer install
```

### 2. 設置環境變數

複製 `.env.example` 為 `.env` 並配置：

```bash
cp .env.example .env
php artisan key:generate
```

### 3. 配置資料庫

在 `.env` 中設置資料庫連接：

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=plugin_registry
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 4. 配置 S3（可選）

如果要使用 S3 儲存：

```env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your_key
AWS_SECRET_ACCESS_KEY=your_secret
AWS_DEFAULT_REGION=ap-northeast-1
AWS_BUCKET=plugin-registry-storage
```

### 5. 執行資料庫遷移

```bash
php artisan migrate
```

### 6. 啟動開發伺服器

```bash
php artisan serve
```

API 將在 `http://localhost:8000` 運行。

## 📁 專案結構

```
api/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       └── Api/
│   │           ├── AuthController.php      # 認證控制器
│   │           ├── PluginController.php    # 插件控制器
│   │           ├── PluginVersionController.php  # 版本控制器
│   │           ├── SearchController.php    # 搜尋控制器
│   │           └── StatsController.php    # 統計控制器
│   ├── Models/
│   │   ├── User.php
│   │   ├── Plugin.php
│   │   ├── PluginVersion.php
│   │   ├── PluginReview.php
│   │   ├── PluginDependency.php
│   │   └── ApiKey.php
│   └── Services/
│       └── StorageService.php              # S3 儲存服務
├── database/
│   └── migrations/                          # 資料庫遷移
├── routes/
│   └── api.php                              # API 路由
└── config/
    ├── jwt.php                              # JWT 配置
    └── filesystems.php                      # 檔案系統配置
```

## 🔌 API 端點

### 認證 API

- `POST /api/v1/auth/register` - 註冊
- `POST /api/v1/auth/login` - 登入
- `POST /api/v1/auth/refresh` - 刷新 Token
- `POST /api/v1/auth/logout` - 登出（需要認證）
- `GET /api/v1/auth/me` - 獲取當前用戶（需要認證）

### 插件 API

- `GET /api/v1/plugins` - 列出所有插件
- `GET /api/v1/plugins/{plugin}` - 獲取插件詳情
- `POST /api/v1/plugins` - 發布新插件（需要認證）
- `PUT /api/v1/plugins/{plugin}` - 更新插件（需要認證）
- `DELETE /api/v1/plugins/{plugin}` - 刪除插件（需要認證）

### 版本 API

- `GET /api/v1/plugins/{plugin}/versions` - 獲取所有版本
- `GET /api/v1/plugins/{plugin}/versions/{version}` - 獲取特定版本
- `POST /api/v1/plugins/{plugin}/versions` - 發布新版本（需要認證）
- `GET /api/v1/plugins/{plugin}/versions/{version}/download` - 下載插件（需要認證）

### 搜尋 API

- `GET /api/v1/search` - 進階搜尋

### 統計 API

- `GET /api/v1/stats` - 平台統計

## 🔐 認證

API 使用 JWT (JSON Web Token) 進行認證。

### 使用方式

在請求頭中添加：

```
Authorization: Bearer {token}
```

### 獲取 Token

```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password"
  }'
```

回應：

```json
{
  "success": true,
  "data": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "token_type": "bearer",
    "expires_in": 3600,
    "user": {...}
  }
}
```

## 📦 資料庫結構

- **users** - 用戶表
- **plugins** - 插件表
- **plugin_versions** - 插件版本表
- **plugin_reviews** - 插件評論表
- **plugin_dependencies** - 插件依賴表
- **api_keys** - API 金鑰表

## 🛠️ 開發

### 執行測試

```bash
php artisan test
```

### 資料庫遷移

```bash
# 建立遷移
php artisan make:migration create_table_name

# 執行遷移
php artisan migrate

# 回滾遷移
php artisan migrate:rollback
```

### 建立模型

```bash
php artisan make:model ModelName
```

### 建立控制器

```bash
php artisan make:controller Api/ControllerName
```

## 📚 相關文件

- [Laravel 文檔](https://laravel.com/docs)
- [JWT Auth 文檔](https://jwt-auth.readthedocs.io/)
- [AWS S3 SDK](https://docs.aws.amazon.com/sdk-for-php/)

## 🔗 與 Agent-Foundry 整合

Agent-Foundry CLI 可以通過以下方式使用此 API：

```python
# 設置註冊表 URL
REMOTE_REGISTRY_URL = "https://registry.agent-foundry.org/api/v1"

# 下載插件
response = requests.get(
    f"{REMOTE_REGISTRY_URL}/plugins/{name}/versions/{version}/download",
    headers={"Authorization": f"Bearer {token}"}
)
```

---

**開發中** - 更多功能即將推出！
