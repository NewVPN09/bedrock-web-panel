#!/bin/bash
set -e

echo "[+] Starting fully automated Bedrock Web Panel + Server installation..."

# -------------------------
# 1️⃣ Install required packages
# -------------------------
apt update -y
apt install -y nginx php php-fpm php-cli php-curl php-mbstring php-zip ufw netcat sudo unzip wget git id unzip &>/dev/null || useradd -m -s /bin/bash minecraft

# -------------------------
# 2️⃣ Stop/Remove Apache if exists
# -------------------------
if systemctl is-active --quiet apache2; then
    echo "[!] Apache detected, removing..."
    systemctl stop apache2
    systemctl disable apache2
    apt purge apache2 apache2-utils apache2-bin apache2.2-common -y
fi

# -------------------------
# 3️⃣ Setup Minecraft server folder
# -------------------------
mkdir -p /home/minecraft/Server
mkdir -p /home/minecraft/backups
mkdir -p /home/minecraft/Server/logs
chown -R minecraft:minecraft /home/minecraft

# -------------------------
# 4️⃣ Download latest Bedrock server
# -------------------------
echo "[+] Downloading latest Minecraft Bedrock server..."
cd /home/minecraft/Server
BEDROCK_URL="https://www.minecraft.net/bedrockdedicatedserver/bin-linux/bedrock-server-1.21.131.1.zip"
wget -q "$BEDROCK_URL" -O bedrock-server.zip
unzip -o bedrock-server.zip
rm bedrock-server.zip
chmod +x bedrock_server
chown -R minecraft:minecraft /home/minecraft/Server

# -------------------------
# 5️⃣ Setup systemd service
# -------------------------
cat > /etc/systemd/system/bedrock.service <<EOL
[Unit]
Description=Minecraft Bedrock Server
After=network.target

[Service]
User=minecraft
WorkingDirectory=/home/minecraft/Server
ExecStart=/home/minecraft/Server/bedrock_server
ExecStop=/bin/bash -c 'echo stop | /usr/bin/nc -u 127.0.0.1 19132'
Restart=on-failure
RestartSec=10
StandardOutput=append:/home/minecraft/Server/logs/bedrock.log
StandardError=append:/home/minecraft/Server/logs/error.log
LimitNOFILE=100000

[Install]
WantedBy=multi-user.target
EOL

systemctl daemon-reload
systemctl enable bedrock

# -------------------------
# 6️⃣ Setup sudoers for web panel
# -------------------------
cat > /etc/sudoers.d/bedrock-panel <<EOL
www-data ALL=(root) NOPASSWD: /bin/systemctl start bedrock, /bin/systemctl stop bedrock, /bin/systemctl restart bedrock, /bin/systemctl status bedrock
EOL
chmod 440 /etc/sudoers.d/bedrock-panel

# -------------------------
# 7️⃣ Setup web panel
# -------------------------
rm -rf /var/www/panel
mkdir -p /var/www/panel
cd /var/www/panel

# Download panel files from GitHub
echo "[+] Downloading panel files..."
wget -q https://raw.githubusercontent.com/NewVPN09/bedrock-web-panel/main/web/index.php
wget -q https://raw.githubusercontent.com/NewVPN09/bedrock-web-panel/main/web/login.php
wget -q https://raw.githubusercontent.com/NewVPN09/bedrock-web-panel/main/web/logout.php
wget -q https://raw.githubusercontent.com/NewVPN09/bedrock-web-panel/main/web/control.php
wget -q https://raw.githubusercontent.com/NewVPN09/bedrock-web-panel/main/web/status.php
wget -q https://raw.githubusercontent.com/NewVPN09/bedrock-web-panel/main/web/logs.php
wget -q https://raw.githubusercontent.com/NewVPN09/bedrock-web-panel/main/web/csrf.php
wget -q https://raw.githubusercontent.com/NewVPN09/bedrock-web-panel/main/web/assets/style.css

chown -R www-data:www-data /var/www/panel

# -------------------------
# 8️⃣ Create config.php with admin/mika
# -------------------------
HASH=$(php -r "echo password_hash('mika', PASSWORD_DEFAULT);")
cat > /var/www/panel/config.php <<EOL
<?php
define('ADMIN_USER', 'admin');
define('ADMIN_PASS_HASH', '$HASH');
define('LOG_FILE', '/home/minecraft/Server/logs/bedrock.log');
EOL

chown www-data:www-data /var/www/panel/config.php
chmod 640 /var/www/panel/config.php

# -------------------------
# 9️⃣ Configure Nginx for panel
# -------------------------
NGINX_CONF="/etc/nginx/sites-available/panel"
cat > $NGINX_CONF <<EOL
server {
    listen 80;
    server_name _;

    root /var/www/panel;
    index index.php;

    location / {
        try_files \$uri \$uri/ /index.php?\$args;
    }

    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php-fpm.sock;
    }

    location ~ /\. {
        deny all;
    }
}
EOL

ln -sf /etc/nginx/sites-available/panel /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl enable nginx
systemctl restart nginx

# -------------------------
# 🔟 Setup firewall
# -------------------------
ufw allow 80/tcp
ufw allow 19132/udp
ufw --force enable

# -------------------------
# 1️⃣1️⃣ Start Bedrock server
# -------------------------
systemctl start bedrock

# -------------------------
echo "[✓] Installation complete!"
echo "Web panel: http://YOUR_SERVER_IP/"
echo "Login -> Username: admin | Password: mika"
echo "Minecraft Bedrock server installed at /home/minecraft/Server/"
echo "UDP Port: 19132"
