#!/bin/bash
set -e
echo "[+] Starting fully automated Bedrock Web Panel + Server installation..."

# Install required packages
apt update -y
apt install -y nginx php php-fpm php-cli php-curl php-mbstring php-zip ufw netcat sudo unzip wget git &>/dev/null || useradd -m -s /bin/bash minecraft

# Stop/Remove Apache
if systemctl is-active --quiet apache2; then
    systemctl stop apache2
    systemctl disable apache2
    apt purge -y apache2*
fi

# Setup Minecraft server
mkdir -p /home/minecraft/Server /home/minecraft/backups /home/minecraft/Server/logs
chown -R minecraft:minecraft /home/minecraft

cd /home/minecraft/Server
wget -q "https://www.minecraft.net/bedrockdedicatedserver/bin-linux/bedrock-server-1.21.131.1.zip" -O bedrock-server.zip
unzip -o bedrock-server.zip
rm bedrock-server.zip
chmod +x bedrock_server
chown -R minecraft:minecraft .

# Create systemd service
cat > /etc/systemd/system/bedrock.service << 'EOF'
[Unit]
Description=Minecraft Bedrock Server
After=network.target

[Service]
User=minecraft
WorkingDirectory=/home/minecraft/Server
ExecStart=/home/minecraft/Server/bedrock_server
Restart=on-failure
RestartSec=10
StandardOutput=append:/home/minecraft/Server/logs/bedrock.log
StandardError=append:/home/minecraft/Server/logs/error.log
LimitNOFILE=100000

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload
systemctl enable bedrock

# Sudoers
cat > /etc/sudoers.d/bedrock-panel << 'EOF'
www-data ALL=(root) NOPASSWD: \
    /bin/systemctl start bedrock, \
    /bin/systemctl stop bedrock, \
    /bin/systemctl restart bedrock, \
    /bin/systemctl status bedrock
EOF
chmod 440 /etc/sudoers.d/bedrock-panel

# Web panel files
rm -rf /var/www/panel
mkdir -p /var/www/panel
cd /var/www/panel

for file in index.php login.php logout.php control.php status.php logs.php rcon.php players.php backup.php restore.php files.php upload.php delete.php csrf.php sysinfo.php; do
    wget -q "https://raw.githubusercontent.com/NewVPN09/bedrock-web-panel/main/web/$file"
done
mkdir -p assets
wget -q "https://raw.githubusercontent.com/NewVPN09/bedrock-web-panel/main/web/assets/style.css" -O assets/style.css
chown -R www-data:www-data /var/www/panel

# Create config.php
HASH=$(php -r "echo password_hash('mika', PASSWORD_DEFAULT);")
cat > /var/www/panel/config.php << EOF
<?php
define('ADMIN_USER', 'admin');
define('ADMIN_PASS_HASH', '$HASH');
define('LOG_FILE', '/home/minecraft/Server/logs/bedrock.log');
EOF
chown www-data:www-data /var/www/panel/config.php
chmod 640 /var/www/panel/config.php

# Nginx config
cat > /etc/nginx/sites-available/panel << 'EOF'
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
}
EOF

ln -sf /etc/nginx/sites-available/panel /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl restart nginx

# Firewall
ufw allow 80/tcp
ufw allow 19132/udp
ufw --force enable

systemctl start bedrock

echo "[✔] Installation complete!"
