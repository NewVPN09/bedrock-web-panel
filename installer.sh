# Move to a safe directory
cd /root

# Update packages
apt update -y
apt upgrade -y

# Install required packages
apt install -y nginx php php-fpm php-cli php-curl php-mbstring php-zip ufw sudo netcat unzip wget git

# Create minecraft user if it doesn't exist
if ! id -u minecraft &>/dev/null; then
    useradd -m -s /bin/bash minecraft
fi

# Create Server folder & logs
mkdir -p /home/minecraft/Server/logs
chown -R minecraft:minecraft /home/minecraft/Server
touch /home/minecraft/Server/logs/bedrock.log

# Remove old panel if exists
rm -rf /var/www/panel /etc/sudoers.d/bedrock-panel /etc/systemd/system/bedrock.service

# Clone web panel
mkdir -p /var/www/panel
cd /var/www/panel
curl -fsSL https://raw.githubusercontent.com/NewVPN09/bedrock-web-panel/main/web/config.php -o config.php

# Overwrite config.php with auto credentials
cat > /var/www/panel/config.php << 'EOF'
<?php
define('ADMIN_USER', 'admin');
define('ADMIN_PASS_HASH', password_hash('mika', PASSWORD_DEFAULT));
define('LOG_FILE', '/home/minecraft/Server/logs/bedrock.log');
EOF

# Set permissions
chown -R www-data:www-data /var/www/panel
chmod -R 755 /var/www/panel

# Nginx configuration for panel
cat > /etc/nginx/sites-available/panel << 'EOF'
server {
    listen 80;
    server_name _;

    root /var/www/panel;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$args;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php-fpm.sock;
    }

    location ~ /\. {
        deny all;
    }
}
EOF

# Enable site & reload Nginx
ln -sf /etc/nginx/sites-available/panel /etc/nginx/sites-enabled/panel
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl restart nginx
systemctl enable nginx

# Ensure PHP-FPM is running
systemctl restart php*-fpm
systemctl enable php*-fpm

# Enable firewall
ufw allow 80/tcp
ufw allow 19132/udp
ufw --force enable

echo "[✓] Nginx + PHP + panel setup complete!"
echo "Open http://YOUR_SERVER_IP/ and login with admin / mika"
