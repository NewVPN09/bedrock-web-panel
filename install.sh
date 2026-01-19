#!/bin/bash
set -e

echo "[+] Starting automated Bedrock Web Panel + Server installation..."

# --- Update & install dependencies ---
apt update -y
apt upgrade -y
apt install -y nginx php php-fpm php-cli php-curl php-mbstring php-zip ufw sudo netcat-openbsd unzip git

# --- Create minecraft user if it doesn't exist ---
if ! id -u minecraft &>/dev/null; then
    useradd -m -s /bin/bash minecraft
    echo "[+] Created user 'minecraft'"
else
    echo "[i] User 'minecraft' already exists, skipping creation"
fi

# --- Clean previous installation ---
rm -rf /var/www/panel /etc/sudoers.d/bedrock-panel /etc/systemd/system/bedrock.service
mkdir -p /var/www/panel

# --- Clone the panel repo ---
git clone https://github.com/NewVPN09/bedrock-web-panel.git /var/www/panel
cd /var/www/panel

# --- Create config.php with default admin credentials ---
cat > /var/www/panel/config.php << 'EOF'
<?php
define('ADMIN_USER', 'admin');
define('ADMIN_PASS_HASH', password_hash('mika', PASSWORD_DEFAULT));
define('LOG_FILE', '/home/minecraft/Server/logs/bedrock.log');
EOF

# --- Create logs folder ---
mkdir -p /home/minecraft/Server/logs
touch /home/minecraft/Server/logs/bedrock.log
chown -R minecraft:minecraft /home/minecraft/Server/logs

# --- Set permissions ---
chown -R www-data:www-data /var/www/panel
chmod -R 755 /var/www/panel

# --- Nginx configuration ---
cat > /etc/nginx/sites-available/panel << 'EOF'
server {
    listen 80;
    server_name _;

    root /var/www/panel;
    index index.php index.html;

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

# --- Enable site ---
ln -sf /etc/nginx/sites-available/panel /etc/nginx/sites-enabled/panel
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl restart nginx
systemctl enable nginx

# --- Ensure PHP-FPM is running ---
systemctl restart php8.2-fpm
systemctl enable php8.2-fpm

# --- Firewall ---
ufw allow 80/tcp
ufw allow 19132/udp
ufw --force enable

echo "[✓] Panel setup complete!"
echo "Open http://YOUR_SERVER_IP/ and login with admin / mika"
