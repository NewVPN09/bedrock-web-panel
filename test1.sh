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

# --- Create minecraft user ---
if ! id -u minecraft &>/dev/null; then
    useradd -m -s /bin/bash minecraft
fi

# --- Clean previous install ---
rm -rf /var/www/panel
mkdir -p /var/www/panel
mkdir -p /home/minecraft

# --- Clone panel ---
git clone https://github.com/NewVPN09/bedrock-web-panel.git /var/www/panel

# --- Copy scripts to /home/minecraft ---
for f in manage_screen.sh install_Server.sh uninstall.sh; do
    if [ -f "/var/www/panel/$f" ]; then
        cp "/var/www/panel/$f" "/home/minecraft/$f"
    fi
done

# --- Make scripts executable (ONLY IF THEY EXIST) ---
for f in manage_screen.sh install_Server.sh uninstall.sh; do
    if [ -f "/home/minecraft/$f" ]; then
        chmod +x "/home/minecraft/$f"
        echo "[✓] Enabled: $f"
    else
        echo "[!] Missing: $f (skipped)"
    fi
done

# --- Ownership ---
chown -R minecraft:minecraft /home/minecraft
chown -R www-data:www-data /var/www/panel

# --- Permissions ---
chmod -R 755 /home/minecraft
chmod -R 755 /var/www/panel

# --- Create server logs ---
mkdir -p /home/minecraft/Server/logs
touch /home/minecraft/Server/logs/bedrock.log
chown -R minecraft:minecraft /home/minecraft/Server

# --- Create config.php ---
cat > /var/www/panel/config.php << 'EOF'
<?php
define('ADMIN_USER', 'admin');
define('ADMIN_PASS_HASH', password_hash('mika', PASSWORD_DEFAULT));
define('LOG_FILE', '/home/minecraft/Server/logs/bedrock.log');
EOF

# --- Sudo permission ---
cat > /etc/sudoers.d/bedrock-panel << 'EOF'
www-data ALL=(minecraft) NOPASSWD: /home/minecraft/manage_screen.sh
www-data ALL=(minecraft) NOPASSWD: /home/minecraft/install_Server.sh
www-data ALL=(minecraft) NOPASSWD: /home/minecraft/uninstall.sh
EOF
chmod 440 /etc/sudoers.d/bedrock-panel

# --- Nginx ---
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

ln -sf /etc/nginx/sites-available/panel /etc/nginx/sites-enabled/panel
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl restart nginx
systemctl enable nginx

# --- PHP ---
systemctl restart php8.2-fpm || systemctl restart php-fpm
systemctl enable php8.2-fpm || systemctl enable php-fpm

# --- Firewall ---
ufw allow 80/tcp
ufw allow 19132/udp
ufw --force enable

echo ""
echo "[✓] INSTALL COMPLETE"
echo "================================"
echo "Open: http://YOUR_SERVER_IP/"
echo "Login: admin"
echo "Pass: mika"
echo "================================"
