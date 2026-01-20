#!/bin/bash
set -e

echo "[+] Starting automated Bedrock Web Panel + Server installation..."

# --- Must run as root ---
if [ "$EUID" -ne 0 ]; then
  echo "[-] Please run as root"
  exit 1
fi

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

# --- Create server folders ---
mkdir -p /home/minecraft/Server/logs
touch /home/minecraft/Server/logs/bedrock.log

# --- Fix ownership ---
chown -R minecraft:minecraft /home/minecraft
chown -R www-data:www-data /var/www/panel

# --- Fix permissions ---
chmod -R 755 /var/www/panel
chmod -R 755 /home/minecraft

# --- Make scripts executable ---
chmod +x /home/minecraft/manage_screen.sh
chmod +x /home/minecraft/install_Server.sh
chmod +x /home/minecraft/uninstall.sh

# --- Give web panel sudo permission without password ---
cat > /etc/sudoers.d/bedrock-panel << 'EOF'
www-data ALL=(minecraft) NOPASSWD: /home/minecraft/manage_screen.sh
www-data ALL=(minecraft) NOPASSWD: /home/minecraft/install_Server.sh
www-data ALL=(minecraft) NOPASSWD: /home/minecraft/uninstall.sh
EOF

chmod 440 /etc/sudoers.d/bedrock-panel

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
systemctl restart php8.2-fpm || systemctl restart php-fpm
systemctl enable php8.2-fpm || systemctl enable php-fpm

# --- Firewall ---
ufw allow 80/tcp
ufw allow 19132/udp
ufw --force enable

echo ""
echo "[✓] Panel setup complete!"
echo "====================================="
echo "Open: http://YOUR_SERVER_IP/"
echo "Login: admin"
echo "Password: mika"
echo "====================================="
