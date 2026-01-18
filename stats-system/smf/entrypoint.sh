#!/bin/bash
set -e

# Install PHP extensions if not present
if ! php -m | grep -q mysqli; then
    apt-get update && apt-get install -y \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        libzip-dev \
        libicu-dev \
        libonig-dev \
        unzip \
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
            opcache
fi

# Enable Apache modules
a2enmod rewrite headers expires 2>/dev/null || true

# PHP configuration
cat > /usr/local/etc/php/conf.d/smf.ini <<EOF
upload_max_filesize = 64M
post_max_size = 64M
max_execution_time = 300
memory_limit = 256M
date.timezone = UTC
EOF

# Apache configuration
cat > /etc/apache2/conf-available/smf.conf <<EOF
<Directory /var/www/html>
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
EOF
a2enconf smf 2>/dev/null || true

# Download SMF if not present
if [ ! -f /var/www/html/index.php ]; then
    echo "Downloading SMF 2.1.4..."
    cd /var/www/html
    
    # Try multiple download sources
    if wget -q --timeout=30 "https://download.simplemachines.org/index.php/smf_2-1-4_install.tar.gz" -O /tmp/smf.tar.gz 2>/dev/null; then
        echo "Downloaded from simplemachines.org"
    elif wget -q --timeout=30 --user-agent="Mozilla/5.0" "https://sourceforge.net/projects/simplemachines/files/smf/SMF%202.1.4/smf_2-1-4_install.tar.gz/download" -O /tmp/smf.tar.gz 2>/dev/null; then
        echo "Downloaded from SourceForge"
    else
        echo "Creating minimal SMF placeholder..."
        cat > /var/www/html/index.php <<'PHPEOF'
<!DOCTYPE html>
<html>
<head>
    <title>MOHAA Stats Forum</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #1a1a2e; color: #eee; }
        .container { max-width: 800px; margin: 0 auto; text-align: center; padding-top: 50px; }
        h1 { color: #e94560; }
        .card { background: #16213e; border-radius: 10px; padding: 30px; margin: 20px 0; }
        a { color: #0f3460; background: #e94560; padding: 10px 20px; border-radius: 5px; text-decoration: none; }
        .status { color: #4ecca3; }
        .api-check { margin-top: 20px; padding: 15px; background: #0f3460; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎮 MOHAA Stats Forum</h1>
        <div class="card">
            <h2>SMF Installation Pending</h2>
            <p>Please download SMF 2.1.4 manually from:</p>
            <p><a href="https://www.simplemachines.org/download/" target="_blank">SimpleMachines.org</a></p>
            <p>Then extract to /var/www/html/</p>
        </div>
        <div class="api-check">
            <h3>API Status</h3>
            <?php
            $api_url = getenv('MOHAA_API_URL') ?: 'http://172.17.0.1:8080';
            $health = @file_get_contents($api_url . '/health');
            if ($health) {
                echo '<p class="status">✅ MOHAA Stats API: Connected</p>';
                echo '<p>API URL: ' . htmlspecialchars($api_url) . '</p>';
            } else {
                echo '<p style="color:#ff6b6b">❌ MOHAA Stats API: Not reachable</p>';
            }
            ?>
        </div>
    </div>
</body>
</html>
PHPEOF
    fi
    
    # If we got the tarball, extract it
    if [ -f /tmp/smf.tar.gz ]; then
        file /tmp/smf.tar.gz
        if file /tmp/smf.tar.gz | grep -q "gzip"; then
            tar -xzf /tmp/smf.tar.gz -C /var/www/html
            rm /tmp/smf.tar.gz
            echo "SMF extracted successfully!"
        else
            echo "Downloaded file is not a valid gzip archive"
            rm /tmp/smf.tar.gz
        fi
    fi
    
    # Set permissions
    chown -R www-data:www-data /var/www/html
    chmod -R 755 /var/www/html
    
    # Set writable directories for SMF
    for dir in attachments avatars cache Packages Smileys Themes; do
        if [ -d /var/www/html/$dir ]; then
            chmod -R 777 /var/www/html/$dir
        fi
    done
    
    # Settings files
    for file in Settings.php Settings_bak.php; do
        if [ -f /var/www/html/$file ]; then
            chmod 666 /var/www/html/$file
        fi
    done
fi

# Create custom directory
mkdir -p /var/www/html/custom
chown www-data:www-data /var/www/html/custom

# Copy custom files if available
if [ -f /entrypoint.sh ] && [ -d /var/www/html/custom ]; then
    # Link custom dashboard as the main page if SMF is not installed
    if [ ! -f /var/www/html/SSI.php ] && [ -f /var/www/html/custom/dashboard.php ]; then
        ln -sf custom/dashboard.php /var/www/html/index.php 2>/dev/null || true
    fi
fi

echo "Starting Apache..."
exec apache2-foreground
