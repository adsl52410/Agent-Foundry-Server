# 使用官方 PHP Apache 映像
FROM php:8.4-apache

# 设置工作目录
WORKDIR /var/www

# 安装系统依赖
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    locales \
    zip \
    jpegoptim optipng pngquant gifsicle \
    vim \
    unzip \
    git \
    curl \
    libonig-dev \
    libxml2-dev \
    pkg-config \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# 安装 PHP 扩展
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd zip

# 安装 Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 复制现有的应用程序代码到容器中
COPY . /var/www

# 设置正确的权限
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage

# 启用 Apache 模块
RUN a2enmod rewrite

# 配置 Apache 虚拟主机
RUN { \
    echo '<VirtualHost *:80>'; \
    echo '    ServerAdmin webmaster@localhost'; \
    echo '    DocumentRoot /var/www/public'; \
    echo ''; \
    echo '    <Directory /var/www/public>'; \
    echo '        Options Indexes FollowSymLinks'; \
    echo '        AllowOverride All'; \
    echo '        Require all granted'; \
    echo '    </Directory>'; \
    echo ''; \
    echo '    ErrorLog ${APACHE_LOG_DIR}/error.log'; \
    echo '    CustomLog ${APACHE_LOG_DIR}/access.log combined'; \
    echo '</VirtualHost>'; \
    } > /etc/apache2/sites-available/000-default.conf

# 修改 PHP 的上传限制为 1G
RUN echo "upload_max_filesize = 1G" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 1G" >> /usr/local/etc/php/conf.d/uploads.ini

# 修改 Memory Limit
RUN echo "LimitRequestBody 1073741824" >> /etc/apache2/apache2.conf


# 暴露端口
EXPOSE 80

# 启动 Apache
CMD ["apache2-foreground"]
