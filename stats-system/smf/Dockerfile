# SMF Forum with PHP 8.2
FROM php:8.2-apache

# Install PHP extensions required by SMF
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    unzip \
    curl \
    wget \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        gd \
        mysqli \
        pdo \
        pdo_mysql \
        zip \
        intl \
        mbstring \
        opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache modules
RUN a2enmod rewrite headers expires

# PHP configuration
RUN { \
    echo 'upload_max_filesize = 64M'; \
    echo 'post_max_size = 64M'; \
    echo 'max_execution_time = 300'; \
    echo 'memory_limit = 256M'; \
    echo 'date.timezone = UTC'; \
} > /usr/local/etc/php/conf.d/smf.ini

# Set working directory
WORKDIR /var/www/html

# Download SMF from SourceForge mirror
RUN curl -L -o /tmp/smf.tar.gz "https://sourceforge.net/projects/simplemachines/files/smf/SMF%202.1.4/smf_2-1-4_install.tar.gz/download" \
    && tar -xzf /tmp/smf.tar.gz -C /var/www/html \
    && rm /tmp/smf.tar.gz

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/attachments \
    && chmod -R 777 /var/www/html/avatars \
    && chmod -R 777 /var/www/html/cache \
    && chmod -R 777 /var/www/html/Packages \
    && chmod -R 777 /var/www/html/Smileys \
    && chmod -R 777 /var/www/html/Themes \
    && chmod 666 /var/www/html/Settings.php \
    && chmod 666 /var/www/html/Settings_bak.php

# Apache configuration for SMF
RUN { \
    echo '<Directory /var/www/html>'; \
    echo '    Options Indexes FollowSymLinks'; \
    echo '    AllowOverride All'; \
    echo '    Require all granted'; \
    echo '</Directory>'; \
} > /etc/apache2/conf-available/smf.conf \
    && a2enconf smf

# Create custom directory for MOHAA integration
RUN mkdir -p /var/www/html/custom /var/www/html/Packages/mohaa \
    && chown -R www-data:www-data /var/www/html/custom /var/www/html/Packages/mohaa

# Expose port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
